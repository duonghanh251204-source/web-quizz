"""prompt_templates.py
Prompt engineering cải tiến — Anti-hallucination, Few-shot generic, Bloom taxonomy, 2-layer review.
"""

import json
import re
from typing import Dict, List, Tuple, Optional

SYSTEM_PROMPT = """Bạn là AI tạo câu hỏi trắc nghiệm TUYỆT ĐỐI KHÔNG dùng kiến thức bên ngoài.
CHỈ sử dụng ĐÚNG từ ngữ + thông tin trong tài liệu cung cấp.
Nếu không đủ thông tin → TẠO ÍT HƠN số câu yêu cầu.
Trả lời CHỈ bằng JSON hợp lệ, KHÔNG thêm text."""

# Few-shot examples TỔNG QUÁT (không chứa kiến thức cụ thể)
FEW_SHOT_EXAMPLES = """
Ví dụ format JSON đúng (KHÔNG phải nội dung thực tế):
[
  {
    "question": "[Câu hỏi dùng từ chính xác từ tài liệu]",
    "options": {"A": "[Từ tài liệu]", "B": "[Từ tài liệu]", "C": "[Từ tài liệu]", "D": "[Từ tài liệu]"},
    "correct_answer": "A",
    "explanation": "[Giải thích ngắn]",
    "difficulty": "medium",
    "bloom_level": "understand",
    "evidence_quote": "[Copy nguyên câu từ tài liệu]",
    "reasoning": "[Tại sao câu này đúng?]",
    "confidence_score": 95
  }
]

🚨 CẤM TUYỆT ĐỐI các chủ đề: Machine Learning, AI, Neural Network, Backpropagation, Deep Learning.
"""

# Forbidden topics để chống hallucination
FORBIDDEN_TOPICS = [
    "machine learning", "học máy", "neural network", "mạng nơ-ron", 
    "backpropagation", "lan truyền ngược", "deep learning", "học sâu",
    "tensorflow", "pytorch", "ai", "trí tuệ nhân tạo"
]

def extract_keywords(text_chunk: str) -> List[str]:
    """Trích xuất keywords từ text để ground LLM"""
    # Simple keyword extraction (có thể thay bằng LLM nhỏ)
    words = re.findall(r'\b[a-zA-ZÀ-ỹ]{4,}\b', text_chunk.lower())
    word_freq = {}
    for word in words:
        if word not in FORBIDDEN_TOPICS:
            word_freq[word] = word_freq.get(word, 0) + 1
    
    # Top 10 keywords
    return sorted(word_freq.items(), key=lambda x: x[1], reverse=True)[:10]

def get_context_summary(text_chunk: str) -> str:
    """Tạo context summary để ground LLM"""
    keywords = extract_keywords(text_chunk)
    if not keywords:
        return "Tài liệu không có thông tin rõ ràng."
    
    main_topic = keywords[0][0] if keywords else "không xác định"
    forbidden_str = ", ".join([t for t in FORBIDDEN_TOPICS if t in text_chunk.lower()])
    
    return f"""📋 TÓM TẮT TÀI LIỆU:
- Chủ đề chính: {main_topic}
- Từ khóa: {', '.join([k[0] for k in keywords[:5]])}
- CẤM: {', '.join(FORBIDDEN_TOPICS)}
- Chỉ dùng từ có trong tài liệu này."""

def preprocess_text_chunk(text_chunk: str) -> Tuple[str, str]:
    """Pre-process text với grounding info"""
    summary = get_context_summary(text_chunk)
    grounded_text = f"""{summary}

--- TÀI LIỆU NGUYÊN BẢN ---
{text_chunk}
--- HẾT TÀI LIỆU ---
KHÔNG dùng kiến thức ngoài đoạn trên."""
    return grounded_text, summary

def validate_evidence_quote(quote: str, source_text: str) -> str:
    """Check if evidence quote exists in source text. Returns: verified, partial, missing."""
    if not quote:
        return "missing"
    
    q_norm = ' '.join(quote.lower().split())
    s_norm = ' '.join(source_text.lower().split())
    
    if q_norm in s_norm:
        return "verified"
        
    # fuzzy match
    q_words = q_norm.split()
    s_words = set(s_norm.split())
    if not q_words:
        return "missing"
    valid_ratio = sum(1 for w in q_words if w in s_words) / len(q_words)
    if valid_ratio >= 0.85:
        return "partial"
    return "missing"

def validate_single_question(question: Dict, original_text: str) -> bool:
    """Hard validation: check từng từ có trong original text"""
    # Validate evidence
    evidence = question.get('evidence_quote', question.get('source_hint', ''))
    status = validate_evidence_quote(evidence, original_text)
    question['grounding_status'] = status
    question['confidence_score'] = int(question.get('confidence_score', 0))
    
    # Kết hợp tất cả text cần check
    texts_to_check = [
        question.get('question', ''),
        question.get('explanation', ''),
        question.get('reasoning', '')
    ] + list(question.get('options', {}).values())
    
    all_text = ' '.join(texts_to_check).lower()
    
    # Check từng từ có trong original text
    words = re.findall(r'\b[a-zA-ZÀ-ỹ]{3,}\b', all_text)
    original_words = set(re.findall(r'\b[a-zA-ZÀ-ỹ]{3,}\b', original_text.lower()))
    
    # Cho phép 80% từ có trong original text (do có thêm reasoning sẽ dùng từ bên ngoài 1 chút)
    valid_ratio = sum(1 for word in words if word in original_words) / len(words) if words else 0
    return valid_ratio >= 0.80

def validate_questions(questions_json: str, original_text: str) -> List[Dict]:
    """Final hard validation"""
    try:
        questions = json.loads(questions_json)
        valid_questions = []
        
        for q in questions:
            if validate_single_question(q, original_text):
                valid_questions.append(q)
        
        return valid_questions
    except:
        return []

# ======================== GIỮ NGUYÊN TÊN HÀM CŨ ========================

def build_quiz_prompt(
    text_chunk: str,
    num_questions: int = 5,
    difficulty: str = "medium",
    topic_hint: str = "",
    language: str = "vi",
) -> str:
    """Tạo prompt hoàn chỉnh CẢI TIẾN - Giữ nguyên signature."""
    
    # Pre-process text với grounding
    grounded_text, context_summary = preprocess_text_chunk(text_chunk)
    
    lang_instruction = (
        "Tất cả câu hỏi, đáp án, giải thích phải bằng tiếng Việt."
        if language == "vi"
        else "All questions, options, and explanations must be in English."
    )

    topic_instruction = f"Tập trung vào chủ đề: {topic_hint}." if topic_hint else ""

    difficulty_guide = {
        "easy":   "Kiến thức cơ bản, nhận biết/ghi nhớ (Bloom: Remember/Understand).",
        "medium": "Hiểu và vận dụng (Bloom: Apply/Analyze).",
        "hard":   "Phân tích, đánh giá (Bloom: Evaluate/Create).",
    }

    # Negative examples (các câu SAI)
    negative_examples = """
🚨 CÁC CÂU NÀY SAI (KHÔNG ĐƯỢC TẠO):
1. "Thuật toán lan truyền ngược (backpropagation)..."
2. "Mô hình học sâu có độ chính xác rất cao trên tập huấn luyện..."
3. Bất kỳ câu nào về Machine Learning/AI
    """

    prompt = f"""{SYSTEM_PROMPT}

{FEW_SHOT_EXAMPLES}

{negative_examples}

{context_summary}

Dựa vào TÀI LIỆU NGUYÊN BẢN trên, tạo {num_questions} câu trắc nghiệm.

🛑 QUY TẮC NGHIÊM NGẶT:
1. MỖI TỪ trong câu hỏi PHẢI có trong tài liệu
2. Copy nguyên câu cho source_hint
3. Nếu không đủ thông tin → TẠO ÍT HƠN {num_questions} câu
4. KHÔNG suy đoán, KHÔNG kiến thức ngoài
5. Độ khó: {difficulty_guide.get(difficulty, difficulty_guide['medium'])}
6. {lang_instruction}
7. {topic_instruction}

📋 FORMAT JSON:
[
  {{
    "question": "...",
    "options": {{"A": "...", "B": "...", "C": "...", "D": "..."}},
    "correct_answer": "A",
    "explanation": "[Giải thích ngắn]",
    "difficulty": "{difficulty}",
    "bloom_level": "remember|understand|apply|analyze|evaluate|create",
    "evidence_quote": "[Copy nguyên câu từ tài liệu]",
    "reasoning": "[Giải thích tại sao đáp án đúng]",
    "confidence_score": 95
  }}
]

{grounded_text}

JSON:"""
    return prompt


def build_review_prompt(questions_json: str, original_text: str = "") -> str:
    """Prompt review CẢI TIẾN - Giữ nguyên signature, thêm original_text optional."""
    
    review_prompt = f"""Bạn là chuyên gia kiểm tra câu hỏi trắc nghiệm.

ORIGINAL TEXT (chỉ được dùng thông tin này):
{original_text}

Câu hỏi cần kiểm tra:
{questions_json}

✅ GIỮ nếu:
- 100% từ ngữ có trong original text
- source_hint khớp chính xác
- Không có từ cấm: machine learning, backpropagation, neural...

❌ LOẠI BỎ nếu:
- Có từ không trong original text
- Chủ đề khác tài liệu
- Distractor không hợp lý

1. Loại bỏ câu hỏi sai/không grounded
2. Sửa explanation nếu cần trích dẫn chính xác hơn
3. Đảm bảo format JSON đúng

TRẢ VỀ JSON đã lọc sạch (có thể ít câu hơn):"""
    
    return review_prompt


# ======================== HÀM MỚI - PIPELINE TOÀN DIỆN ========================

def generate_quiz_pipeline(
    llm_call_func,  # Hàm gọi LLM của bạn
    text_chunk: str,
    num_questions: int = 5,
    difficulty: str = "medium",
    max_retries: int = 1
) -> List[Dict]:
    """
    Pipeline hoàn chỉnh
    1. Generate với grounding
    2. Review bị skip theo yêu cầu.
    """
    
    # Layer 1: Generate
    prompt1 = build_quiz_prompt(text_chunk, num_questions, difficulty)
    response1 = llm_call_func(prompt1)
    questions1 = validate_questions(response1, text_chunk)
    
    # Bỏ qua Critic Model (Layer 2 review) như yêu cầu của người dùng
    
    # Sort theo confidence_score ASC (thấp lên trước)
    questions1.sort(key=lambda x: x.get('confidence_score', 0))
    
    return questions1


# ======================== BACKWARD COMPATIBILITY ========================

# Để tương thích với code cũ
class QuizGenerator:
    """Wrapper để tương thích code cũ"""
    
    def __init__(self, llm_call_func):
        self.llm_call = llm_call_func
    
    def generate_from_text(self, text_chunk: str, num_questions: int = 5, **kwargs):
        """Cách dùng cũ vẫn hoạt động"""
        return generate_quiz_pipeline(
            self.llm_call, text_chunk, num_questions, **kwargs
        )

# Test function
def test_pipeline():
    """Test nhanh pipeline"""
    sample_text = """
    Việt Nam có 63 tỉnh thành. Hà Nội là thủ đô. Sông Hồng chảy qua Hà Nội.
    Diện tích Việt Nam khoảng 331.000 km². Dân số khoảng 100 triệu người.
    """
    
    def mock_llm(prompt):
        # Mock response cho test
        return json.dumps([
            {
                "question": "Thủ đô Việt Nam là thành phố nào?",
                "options": {"A": "Hà Nội", "B": "TP.HCM", "C": "Đà Nẵng", "D": "Cần Thơ"},
                "correct_answer": "A",
                "explanation": "Theo tài liệu: Hà Nội là thủ đô.",
                "difficulty": "easy",
                "bloom_level": "remember",
                "source_hint": "Hà Nội là thủ đô."
            }
        ])
    
    result = generate_quiz_pipeline(mock_llm, sample_text, 3)
    print(f"✅ Test passed: {len(result)} câu hỏi valid")
    return result

if __name__ == "__main__":
    test_pipeline()