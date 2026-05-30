Đề tài xây dựng hệ thống sinh file trắc nghiệm từ tài liệu :
Mục tiêu chính: Xây dựng hệ thống tự động trích xuất nội dung từ file tài liệu (PDF, Word, Text) và sử dụng LLM để sinh ra bộ câu hỏi trắc nghiệm chất lượng cao.

Input: File tài liệu (.pdf, .docx, .txt) Output: File trắc nghiệm (.doxx, .json, .excel, .txt) với câu hỏi, đáp án, giải thích

Giai đoạn 1: Nghiên cứu & Thiết kế (Tuần 1)

Copy code
✅ Phân tích yêu cầu
✅ Nghiên cứu LLM phù hợp (OpenAI GPT, Gemini, Llama, HuggingFace)
✅ Thiết kế pipeline xử lý
✅ Chọn format output (JSON/Excel)
✅ Vẽ flowchart hệ thống

Giai đoạn 2: Xây dựng Pipeline Xử Lý Tài Liệu (Tuần 2)

Copy code
✅ Text Extraction
  - PDF: PyMuPDF, pdfplumber
  - DOCX: python-docx
  - TXT: built-in

✅ Text Preprocessing
  - Làm sạch text (remove noise, special chars)
  - Chunking (chia nhỏ theo segment)
  - Metadata extraction (title, chapter)

Giai đoạn 3: Tích Hợp LLM & Prompt Engineering (Tuần 3)


Giai đoạn 4: Xây Dựng Ứng Dụng Chính (Tuần 4)

Copy code
✅ Backend (FastAPI/Flask)
  - Upload file endpoint
  - Process pipeline
  - Generate questions
  - Download result

✅ Frontend (Streamlit/Gradio)
  - File upload
  - Config (số câu, độ khó, chủ đề)
  - Preview kết quả
  - Export options

Giai đoạn 5: Tối Ưu & Test (Tuần 5)

Copy code
✅ Quality Control
  - Đánh giá chất lượng câu hỏi (manual + auto)
  - Diversity check
  - Accuracy validation

✅ Performance Optimization
  - Batch processing
  - Caching
  - Async processing

✅ Testing
  - Unit test
  - Integration test
  - User acceptance test

🛠️ 3. TECH STACK ĐỀ XUẤT

Copy code
Core:
├── Python 3.10+
├── FastAPI/Flask (Backend)
├── Streamlit/Gradio (Frontend)
└── Pydantic (Data validation)

Document Processing:
├── PyMuPDF/pdfplumber (PDF)
├── python-docx (Word)
└── NLTK/spaCy (NLP preprocessing)

LLM:
├── OpenAI API (GPT-4o-mini)
├── Google Gemini API (free tier)
└── Ollama (local deployment)

Export:
├── pandas/openpyxl (Excel)
├── json (standard)
└── markdown (preview)

📁 4. CẤU TRÚC THƯ MỤC

Copy code
quiz_generator/
├── app/
│   ├── core/
│   │   ├── document_processor.py
│   │   ├── llm_service.py
│   │   └── prompt_templates.py
│   ├── api/
│   │   └── main.py
│   └── ui/
│       └── streamlit_app.py
├── tests/
├── data/
│   ├── sample_docs/
│   └── prompts/
├── requirements.txt
└── README.md

Điểm cần cải thiện / Rủi ro tiềm ẩn
Text Extraction từ PDF (Giai đoạn 2) — Đây là bottleneck lớn nhất
Nhiều PDF (scan, bảng phức tạp, multi-column, font lạ, hình ảnh chứa text) sẽ bị lỗi: text bị lẫn, thứ tự sai, mất heading, hoặc thành ký tự lạ.
PyMuPDF nhanh nhưng đôi khi thứ tự text không đúng. pdfplumber tốt hơn cho bảng nhưng chậm hơn.
Khuyến nghị:
Thêm fallback: Nếu text extraction kém → dùng OCR (pytesseract + pdf2image hoặc paddleocr).
Dùng Unstructured.io library (có thể integrate) hoặc pymupdf4llm để extract ra Markdown (giữ cấu trúc heading, bảng tốt hơn).
Luôn extract metadata (page number, heading level) để hỗ trợ citation sau này.


Chunking (Preprocessing) — Rất quan trọng cho chất lượng câu hỏi
Chỉ "chia nhỏ theo segment" là chưa đủ. Nếu chunk quá lớn → LLM quên chi tiết hoặc hallucinate. Quá nhỏ → mất ngữ cảnh.
Khuyến nghị nâng cấp:
Sử dụng RecursiveCharacterTextSplitter (LangChain) hoặc SemanticChunker (dùng embedding để chia theo ý nghĩa).
Thêm overlap (ví dụ 20-30%) giữa các chunk.
Giữ metadata (chapter, title, page) cho mỗi chunk → giúp LLM sinh câu hỏi chính xác và có giải thích trích nguồn.


Prompt Engineering (Giai đoạn 3) — Quyết định 70-80% chất lượng
Đây là phần bạn cần dành nhiều thời gian nhất.
Best practices hiện nay:
Sử dụng structured output (JSON mode của OpenAI/Gemini) để output luôn có format: question, options (a,b,c,d), correct_answer, explanation, difficulty, bloom_level (nếu muốn).
Few-shot prompting: Đưa 2-3 ví dụ câu hỏi tốt (có distractor plausibility, không trick question, theo Bloom taxonomy).
Role prompt: "Bạn là giáo viên chuyên môn cao, chuyên tạo câu trắc nghiệm chất lượng cho sinh viên đại học."
Yêu cầu: 4 options, 1 correct, 3 distractors hợp lý (không quá dễ đoán), kèm giải thích chi tiết trích từ tài liệu.
Thêm self-review: Sau khi sinh, prompt LLM kiểm tra lại (grammar, relevance, diversity).
Hỗ trợ độ khó (easy/medium/hard) và số lượng câu hỏi theo chủ đề.


Quality Control (Giai đoạn 5)
Manual test tốt nhưng tốn thời gian.
Auto evaluation: Dùng LLM khác (hoặc cùng model) để chấm điểm câu hỏi theo rubric (relevance, difficulty, distractor quality, factual accuracy).
Kiểm tra diversity: Tránh nhiều câu hỏi giống nhau về chủ đề.
Validation: So sánh đáp án sinh ra với ground truth (nếu có sample).

Các tính năng nên bổ sung (nâng cao)
RAG nhẹ: Embed chunk → vector store (Chroma hoặc FAISS) → retrieve relevant chunks trước khi sinh câu hỏi → giảm hallucination, tăng độ chính xác.
Hỗ trợ bảng biểu, công thức (nếu tài liệu có) → extract table riêng (Camelot hoặc pdfplumber).
Batch processing + progress bar (quan trọng khi file lớn).
Rate limit handling cho API (OpenAI/Gemini).
Logging lỗi extraction + fallback.
Export đẹp hơn: PDF với format quiz chuyên nghiệp (dùng reportlab hoặc WeasyPrint).