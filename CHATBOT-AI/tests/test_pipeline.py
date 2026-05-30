"""
tests/test_pipeline.py — Unit + Integration tests
"""
import json
import os
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

import pytest


# ---------------------------------------------------------------------------
# Test: document_processor
# ---------------------------------------------------------------------------
TXT_CONTENT = b"""
Chuong 1: Gioi thieu ve Tri tue Nhan tao

Tri tue Nhan tao (AI) la nganh khoa hoc nghien cuu cach tao ra cac may tinh
co kha nang suy nghi va hoc hoi nhu con nguoi.

Machine Learning la mot nhanh cua AI, cho phep may tinh tu hoc tu du lieu
ma khong can lap trinh cu the.

Deep Learning su dung mang than kinh nhan tao (ANN) de xu ly du lieu phuc tap.
"""


def test_txt_extraction():
    from app.core.document_processor import process_document
    doc = process_document(TXT_CONTENT, "test.txt", chunk_size=200, chunk_overlap=20)
    assert doc.source_file == "test.txt"
    assert len(doc.chunks) > 0
    assert doc.extraction_method == "plain-text"
    print(f"[OK] TXT: {len(doc.chunks)} chunks extracted")


def test_chunk_content_not_empty():
    from app.core.document_processor import process_document
    doc = process_document(TXT_CONTENT, "test.txt", chunk_size=300, chunk_overlap=50)
    for chunk in doc.chunks:
        assert len(chunk.content.strip()) > 0


def test_chunk_metadata():
    from app.core.document_processor import process_document
    doc = process_document(TXT_CONTENT, "test.txt", chunk_size=200, chunk_overlap=20)
    for i, chunk in enumerate(doc.chunks):
        assert chunk.chunk_index == i
        assert chunk.total_chunks == len(doc.chunks)
        assert chunk.source_file == "test.txt"


# ---------------------------------------------------------------------------
# Test: prompt_templates
# ---------------------------------------------------------------------------

def test_build_quiz_prompt():
    from app.core.prompt_templates import build_quiz_prompt
    prompt = build_quiz_prompt("Nội dung test.", num_questions=3, difficulty="easy", language="vi")
    assert "3" in prompt
    assert "JSON" in prompt
    assert "easy" in prompt
    print("[OK] Prompt template valid")


def test_review_prompt():
    from app.core.prompt_templates import build_review_prompt
    sample = json.dumps([{"question": "Test?", "correct_answer": "A"}])
    prompt = build_review_prompt(sample)
    assert "JSON" in prompt


# ---------------------------------------------------------------------------
# Test: llm_service (mock)
# ---------------------------------------------------------------------------

def test_extract_json_array():
    """Test _extract_json với các format khác nhau."""
    from app.core.llm_service import _extract_json

    # Clean JSON
    raw = '[{"question": "Q1?", "correct_answer": "A"}]'
    result = _extract_json(raw)
    assert isinstance(result, list)

    # JSON bọc trong text
    raw2 = 'Đây là kết quả:\n```json\n[{"question": "Q2?"}]\n```'
    result2 = _extract_json(raw2)
    assert isinstance(result2, list)

    print("[OK] JSON extraction works")


def test_extract_json_object():
    from app.core.llm_service import _extract_json
    raw = '{"questions": [{"question": "Q?", "correct_answer": "B"}]}'
    result = _extract_json(raw)
    assert "questions" in result


# ---------------------------------------------------------------------------
# Test: API (integration — cần server chạy)
# ---------------------------------------------------------------------------

@pytest.mark.skipif(
    os.getenv("RUN_API_TESTS") != "1",
    reason="Cần API server. Chạy với RUN_API_TESTS=1"
)
def test_api_health():
    import httpx
    r = httpx.get("http://localhost:8000/health")
    assert r.status_code == 200
    assert r.json()["status"] == "ok"


@pytest.mark.skipif(
    os.getenv("RUN_API_TESTS") != "1",
    reason="Cần API server. Chạy với RUN_API_TESTS=1"
)
def test_api_upload_and_generate():
    import httpx
    # Upload
    r = httpx.post(
        "http://localhost:8000/upload",
        files={"file": ("test.txt", TXT_CONTENT, "text/plain")},
        timeout=30,
    )
    assert r.status_code == 200
    sid = r.json()["session_id"]
    assert sid

    # Generate
    r2 = httpx.post(
        "http://localhost:8000/generate",
        json={"session_id": sid, "num_questions": 3, "difficulty": "easy", "auto_review": False},
        timeout=120,
    )
    assert r2.status_code == 200
    data = r2.json()
    assert len(data["questions"]) > 0
    print(f"[OK] API test: {len(data['questions'])} questions generated")


if __name__ == "__main__":
    test_txt_extraction()
    test_chunk_content_not_empty()
    test_chunk_metadata()
    test_build_quiz_prompt()
    test_review_prompt()
    test_extract_json_array()
    test_extract_json_object()
    print("\n[PASS] All unit tests passed!")
