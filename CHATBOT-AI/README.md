# Quiz Generator AI

Hệ thống tự động sinh câu hỏi trắc nghiệm từ tài liệu (PDF, Word, TXT) sử dụng LLM.

## Cài đặt nhanh

### 1. Tạo môi trường ảo Python

```bash
python -m venv venv
venv\Scripts\activate        # Windows
# source venv/bin/activate   # macOS/Linux
```

### 2. Cài dependencies

```bash
pip install -r requirements.txt
```

### 3. Cấu hình API key

```bash
copy .env.example .env
# Mở .env và điền GEMINI_API_KEY hoặc OPENAI_API_KEY
```

### 4. Chạy Backend (Terminal 1)

```bash
uvicorn app.api.main:app --reload --port 8000
```

Swagger UI: http://localhost:8000/docs

### 5. Chạy Frontend (Terminal 2)

```bash
streamlit run app/ui/streamlit_app.py
```

Mở trình duyệt: http://localhost:8501

---

## Cấu trúc thư mục

```
quiz_generator/
├── app/
│   ├── core/
│   │   ├── document_processor.py   # Trích xuất PDF/DOCX/TXT + chunking
│   │   ├── llm_service.py          # Gọi Gemini / OpenAI / Ollama
│   │   └── prompt_templates.py     # Prompt engineering
│   ├── api/
│   │   └── main.py                 # FastAPI backend
│   └── ui/
│       └── streamlit_app.py        # Giao diện Streamlit
├── tests/
│   └── test_pipeline.py
├── .env.example
├── requirements.txt
└── README.md
```

## Chạy Tests

```bash
# Unit tests
python tests/test_pipeline.py

# Với pytest
pytest tests/ -v

# Integration tests (cần API đang chạy)
RUN_API_TESTS=1 pytest tests/ -v
```

## Các tính năng

| Tính năng | Mô tả |
|-----------|-------|
| 📄 Multi-format | PDF (kể cả scan), DOCX, TXT |
| 🤖 Multi-LLM | Gemini, OpenAI GPT-4o-mini, Ollama (local) |
| 🔄 OCR fallback | Tự động OCR nếu PDF không có text |
| ✂️ Smart chunking | Chia nhỏ có overlap, giữ ngữ cảnh |
| 🎯 Prompt engineering | Few-shot + JSON mode + self-review |
| 📊 Export | JSON, Excel, TXT |
| 🌐 Song ngữ | Tiếng Việt và English |

## Cấu hình .env

```env
LLM_PROVIDER=gemini          # openai | gemini | ollama
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-1.5-flash
MAX_CHUNK_SIZE=2000
CHUNK_OVERLAP=200
DEFAULT_NUM_QUESTIONS=10
```
