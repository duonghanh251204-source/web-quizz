"""
app/api/main.py — FastAPI backend
Endpoints: upload, generate, download
"""
from __future__ import annotations

import asyncio
import io
import json
import math
import os
import uuid
from pathlib import Path
from typing import List, Optional

import pandas as pd
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import StreamingResponse
from loguru import logger
from pydantic import BaseModel

from app.core.document_processor import process_document
from app.core.llm_service import generate_questions
from app.core.prompt_templates import SYSTEM_PROMPT, build_quiz_prompt, build_review_prompt

app = FastAPI(
    title="Quiz Generator AI",
    description="Hệ thống tự động sinh câu hỏi trắc nghiệm từ tài liệu",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# In-memory storage (production nên dùng Redis/DB)
_sessions: dict = {}


# ---------------------------------------------------------------------------
# Schemas
# ---------------------------------------------------------------------------

class GenerateRequest(BaseModel):
    session_id: str
    num_questions: int = 10
    difficulty: str = "medium"     # easy | medium | hard
    topic_hint: str = ""
    language: str = "vi"           # vi | en
    auto_review: bool = False      # Tắt mặc định — tốn thêm 1 lần gọi LLM


class QuestionItem(BaseModel):
    question: str
    options: dict
    correct_answer: str
    explanation: str = ""
    difficulty: str
    bloom_level: str
    source_hint: str = ""
    evidence_quote: str = ""
    reasoning: str = ""
    confidence_score: int = 0
    grounding_status: str = "unknown"


class GenerateResponse(BaseModel):
    session_id: str
    questions: List[dict]
    total_chunks_used: int
    source_file: str


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@app.get("/health")
def health():
    return {"status": "ok", "provider": os.getenv("LLM_PROVIDER", "gemini")}


@app.post("/upload")
async def upload_file(file: UploadFile = File(...)):
    """Bước 1: Upload và xử lý tài liệu."""
    MAX_MB = int(os.getenv("MAX_FILE_SIZE_MB", 20))
    content = await file.read()

    if len(content) > MAX_MB * 1024 * 1024:
        raise HTTPException(413, f"File quá lớn. Tối đa {MAX_MB}MB")

    allowed_ext = {".pdf", ".docx", ".doc", ".txt"}
    ext = Path(file.filename or "").suffix.lower()
    if ext not in allowed_ext:
        raise HTTPException(400, f"Định dạng không hỗ trợ: {ext}. Hỗ trợ: {allowed_ext}")

    chunk_size  = int(os.getenv("MAX_CHUNK_SIZE", 2000))
    chunk_overlap = int(os.getenv("CHUNK_OVERLAP", 200))

    try:
        doc = process_document(content, file.filename or "upload", chunk_size, chunk_overlap)
    except Exception as e:
        raise HTTPException(422, f"Lỗi xử lý tài liệu: {e}")

    session_id = str(uuid.uuid4())
    _sessions[session_id] = {
        "doc": doc,
        "questions": [],
    }

    return {
        "session_id": session_id,
        "filename": file.filename,
        "total_chunks": len(doc.chunks),
        "extraction_method": doc.extraction_method,
        "title": doc.title,
    }


# ---------------------------------------------------------------------------
# Parallel batch helpers
# ---------------------------------------------------------------------------

def _split_context_segments(text: str, num_batches: int) -> List[str]:
    """Chia text thành num_batches đoạn chồng lên nhau để tối đa đa dạng câu hỏi."""
    if num_batches <= 1:
        return [text]
    total = len(text)
    seg_len = max(1000, total // num_batches)
    overlap = max(200, seg_len // 5)          # 20% overlap để tránh mất ngữ cảnh
    segments = []
    for i in range(num_batches):
        start = max(0, i * seg_len - (i * overlap))
        end   = min(total, start + seg_len + overlap)
        seg   = text[start:end].strip()
        if seg:
            segments.append(seg)
    # Nếu ít đoạn hơn dự kiến, lặp lại toàn bộ text cho các batch còn lại
    while len(segments) < num_batches:
        segments.append(text)
    return segments


def _deduplicate_questions(questions: List[dict]) -> List[dict]:
    """Loại câu hỏi trùng lặp dựa trên 60 ký tự đầu tiên (chuẩn hoá)."""
    seen: set = set()
    unique: List[dict] = []
    for q in questions:
        key = (q.get("question", "") or "")[:60].strip().lower()
        if key and key not in seen:
            seen.add(key)
            unique.append(q)
    return unique


def _generate_single_sync(
    context: str,
    num_q: int,
    difficulty: str,
    topic_hint: str,
    language: str,
) -> List[dict]:
    """Gọi LLM đồng bộ cho 1 batch — dùng trong asyncio.to_thread."""
    from app.core.prompt_templates import generate_quiz_pipeline
    
    def _llm_call(prompt):
        return generate_questions(SYSTEM_PROMPT, prompt, return_raw=True)
    
    # Dùng _llm_call (yêu cầu trả về text thô) để pipeline tự validate_questions
    # Nếu generate_questions hiện trả về Dict thay vì String thì cần xử lý thêm.
    # Trong PRX, giả định generate_questions tự parse hoặc mình override:
    
    prompt = build_quiz_prompt(
        text_chunk=context,
        num_questions=num_q,
        difficulty=difficulty,
        topic_hint=topic_hint,
        language=language,
    )
    # Vì generate_questions có thể đã tự trả về JSON list (theo code cũ)
    # nhưng generate_quiz_pipeline lại mong đợi llm_call_func trả string.
    # Tuy nhiên, nếu hàm generate_questions trả List[dict], ta convert list->json
    # để prompt_templates.validate_questions load lại.
    res = generate_questions(SYSTEM_PROMPT, prompt)
    if isinstance(res, list):
        res_str = json.dumps(res)
    else:
        res_str = str(res)
        
    from app.core.prompt_templates import validate_questions
    validated = validate_questions(res_str, context)
    validated.sort(key=lambda x: x.get('confidence_score', 0))
    return validated


# ---------------------------------------------------------------------------
# /generate endpoint
# ---------------------------------------------------------------------------

@app.post("/generate", response_model=GenerateResponse)
async def generate(req: GenerateRequest):
    """Bước 2: Sinh câu hỏi từ tài liệu đã upload.

    Khi num_questions > BATCH_SIZE, tự động chia thành nhiều batch
    và chạy song song (asyncio.gather) để tiết kiệm thời gian.
    """
    session = _sessions.get(req.session_id)
    if not session:
        raise HTTPException(404, "Session không tồn tại. Hãy upload file trước.")

    doc = session["doc"]
    chunks = doc.chunks
    if not chunks:
        raise HTTPException(422, "Tài liệu không có nội dung sau khi xử lý.")

    # --- Gom context ---
    MAX_CONTEXT_CHARS = max(8000, min(30000, int(os.getenv("MAX_CONTEXT_CHARS", "20000"))))
    combined_text = ""
    for chunk in chunks:
        if len(combined_text) + len(chunk.content) > MAX_CONTEXT_CHARS:
            break
        combined_text += chunk.content + "\n\n"
    combined_text = combined_text.strip()

    # --- Parallel batch ---
    BATCH_SIZE = max(5, int(os.getenv("BATCH_SIZE", "10")))
    num_q      = req.num_questions

    if num_q <= BATCH_SIZE:
        # --- Single call (hành vi cũ) ---
        logger.info(f"Single batch: {num_q} câu")
        try:
            all_questions = await asyncio.to_thread(
                _generate_single_sync,
                combined_text, num_q,
                req.difficulty, req.topic_hint, req.language,
            )
        except Exception as e:
            logger.error(f"Generate thất bại: {e}")
            raise HTTPException(500, f"Lỗi sinh câu hỏi: {e}")
    else:
        # --- Parallel batches ---
        num_batches  = math.ceil(num_q / BATCH_SIZE)
        # Phân phối câu đều vào các batch, batch cuối nhận phần dư
        base, extra  = divmod(num_q, num_batches)
        batch_sizes  = [base + (1 if i < extra else 0) for i in range(num_batches)]
        segments     = _split_context_segments(combined_text, num_batches)

        logger.info(f"Parallel batch: {num_q} câu → {num_batches} batch × ~{BATCH_SIZE} câu, chạy song song")

        tasks = [
            asyncio.to_thread(
                _generate_single_sync,
                segments[i], batch_sizes[i],
                req.difficulty, req.topic_hint, req.language,
            )
            for i in range(num_batches)
        ]

        results = await asyncio.gather(*tasks, return_exceptions=True)

        all_questions: List[dict] = []
        for idx, r in enumerate(results):
            if isinstance(r, Exception):
                logger.warning(f"Batch {idx+1} thất bại: {r}")
            elif isinstance(r, list):
                logger.success(f"Batch {idx+1}: thu được {len(r)} câu")
                all_questions.extend(r)

        if not all_questions:
            raise HTTPException(500, "Tất cả batch đều thất bại. Thử lại sau.")

        # Loại trùng và giới hạn đúng số câu yêu cầu
        all_questions = _deduplicate_questions(all_questions)
        all_questions = all_questions[:num_q]
        logger.info(f"Sau dedup: {len(all_questions)} câu hỏi hợp lệ")

    session["questions"] = all_questions

    return GenerateResponse(
        session_id=req.session_id,
        questions=all_questions,
        total_chunks_used=len(chunks),
        source_file=doc.source_file,
    )


@app.get("/download/{session_id}")
def download(session_id: str, format: str = "json"):
    """Bước 3: Tải xuống kết quả (json | excel | txt)."""
    session = _sessions.get(session_id)
    if not session or not session["questions"]:
        raise HTTPException(404, "Không có câu hỏi để tải. Hãy generate trước.")

    questions = session["questions"]

    if format == "json":
        content = json.dumps(questions, ensure_ascii=False, indent=2).encode("utf-8")
        return StreamingResponse(
            io.BytesIO(content),
            media_type="application/json",
            headers={"Content-Disposition": "attachment; filename=quiz.json"},
        )

    elif format == "excel":
        rows = []
        for i, q in enumerate(questions, 1):
            opts = q.get("options", {})
            rows.append({
                "STT": i,
                "Câu hỏi": q.get("question", ""),
                "A": opts.get("A", ""),
                "B": opts.get("B", ""),
                "C": opts.get("C", ""),
                "D": opts.get("D", ""),
                "Đáp án đúng": q.get("correct_answer", ""),
                "Giải thích": q.get("explanation", ""),
                "Độ khó": q.get("difficulty", ""),
                "Bloom Level": q.get("bloom_level", ""),
                "Nguồn": q.get("source_hint", ""),
            })
        df = pd.DataFrame(rows)
        buf = io.BytesIO()
        with pd.ExcelWriter(buf, engine="openpyxl") as writer:
            df.to_excel(writer, index=False, sheet_name="Quiz")
        buf.seek(0)
        return StreamingResponse(
            buf,
            media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            headers={"Content-Disposition": "attachment; filename=quiz.xlsx"},
        )

    elif format == "txt":
        lines = []
        for i, q in enumerate(questions, 1):
            opts = q.get("options", {})
            lines.append(f"Câu {i}: {q.get('question', '')}")
            for k, v in opts.items():
                marker = "✓" if k == q.get("correct_answer") else " "
            for k, v in opts.items():
                lines.append(f"  {k}. {v}")
            lines.append(f"  → Đáp án: {q.get('correct_answer', '')}")
            lines.append(f"  Giải thích: {q.get('explanation', '')}")
            lines.append("")
        content = "\n".join(lines).encode("utf-8")
        return StreamingResponse(
            io.BytesIO(content),
            media_type="text/plain; charset=utf-8",
            headers={"Content-Disposition": "attachment; filename=quiz.txt"},
        )

    else:
        raise HTTPException(400, f"Format không hỗ trợ: {format}. Dùng: json | excel | txt")
