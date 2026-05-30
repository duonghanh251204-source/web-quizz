"""
app/ui/streamlit_app.py — Giao diện người dùng Streamlit
"""
from __future__ import annotations

import json
import os
import time

import requests
import streamlit as st
from dotenv import load_dotenv

load_dotenv()

API_BASE = os.getenv("API_BASE_URL", "http://localhost:8000")

# ---------------------------------------------------------------------------
# Page config
# ---------------------------------------------------------------------------
st.set_page_config(
    page_title="Quiz Generator AI",
    page_icon="🧠",
    layout="wide",
    initial_sidebar_state="expanded",
)

# ---------------------------------------------------------------------------
# Custom CSS
# ---------------------------------------------------------------------------
st.markdown("""
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    * { font-family: 'Inter', sans-serif; }

    .main { background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; }

    .stApp { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); }

    .hero-title {
        font-size: 2.8rem; font-weight: 700; text-align: center;
        background: linear-gradient(90deg, #a78bfa, #60a5fa, #34d399);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 0.3rem;
    }
    .hero-sub {
        text-align: center; color: #94a3b8; font-size: 1.1rem; margin-bottom: 2rem;
    }
    .card {
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem;
        backdrop-filter: blur(10px);
    }
    .q-card {
        background: rgba(167,139,250,0.08); border-left: 4px solid #a78bfa;
        border-radius: 12px; padding: 1.2rem; margin-bottom: 1rem;
    }
    .q-num { color: #a78bfa; font-weight: 700; font-size: 0.85rem; }
    .q-text { color: #f1f5f9; font-size: 1rem; font-weight: 500; margin: 0.4rem 0; }
    .opt { color: #cbd5e1; font-size: 0.9rem; margin: 0.2rem 0; }
    .opt-correct { color: #34d399; font-weight: 600; }
    .explain { color: #94a3b8; font-size: 0.85rem; margin-top: 0.5rem; font-style: italic; }
    .badge {
        display: inline-block; padding: 2px 10px; border-radius: 999px;
        font-size: 0.75rem; font-weight: 600; margin-right: 6px;
    }
    .badge-easy   { background: #052e16; color: #34d399; }
    .badge-medium { background: #1c1917; color: #f59e0b; }
    .badge-hard   { background: #1c0505; color: #f87171; }
    .stat-box {
        text-align: center; background: rgba(255,255,255,0.05);
        border-radius: 12px; padding: 1rem;
    }
    .stat-num { font-size: 2rem; font-weight: 700; color: #a78bfa; }
    .stat-label { font-size: 0.8rem; color: #94a3b8; }
</style>
""", unsafe_allow_html=True)

# ---------------------------------------------------------------------------
# Sidebar — Cấu hình
# ---------------------------------------------------------------------------
with st.sidebar:
    st.markdown("### ⚙️ Cấu hình")
    num_questions = st.slider("Số câu hỏi", 5, 50, 10, 5)
    difficulty = st.selectbox("Độ khó", ["easy", "medium", "hard"], index=1,
                              format_func=lambda x: {"easy":"Dễ","medium":"Trung bình","hard":"Khó"}[x])
    language = st.radio("Ngôn ngữ output", ["vi", "en"],
                        format_func=lambda x: "🇻🇳 Tiếng Việt" if x == "vi" else "🇬🇧 English")
    topic_hint = st.text_input("Chủ đề tập trung (tuỳ chọn)", placeholder="VD: lập trình hướng đối tượng")
    auto_review = st.toggle("🤖 Tự động review AI", value=True,
                            help="LLM sẽ tự kiểm tra và cải thiện câu hỏi sau khi sinh")
    export_fmt = st.selectbox("Định dạng tải về", ["json", "excel", "txt"])

    st.markdown("---")
    st.markdown("**Provider LLM:**")
    provider = os.getenv("LLM_PROVIDER", "gemini")
    color_map = {"gemini": "🟢", "openai": "🔵", "ollama": "🟡"}
    st.markdown(f"{color_map.get(provider, '⚪')} `{provider.upper()}`")

# ---------------------------------------------------------------------------
# Main content
# ---------------------------------------------------------------------------
st.markdown('<div class="hero-title">🧠 Quiz Generator AI</div>', unsafe_allow_html=True)
st.markdown('<div class="hero-sub">Tự động sinh câu hỏi trắc nghiệm chất lượng từ tài liệu của bạn</div>',
            unsafe_allow_html=True)

# State init
if "session_id" not in st.session_state:
    st.session_state.session_id = None
if "questions" not in st.session_state:
    st.session_state.questions = []
if "doc_info" not in st.session_state:
    st.session_state.doc_info = {}

# ---------------------------------------------------------------------------
# Step 1: Upload
# ---------------------------------------------------------------------------
st.markdown('<div class="card">', unsafe_allow_html=True)
st.markdown("### 📄 Bước 1 — Tải lên tài liệu")
uploaded = st.file_uploader(
    "Kéo thả hoặc chọn file",
    type=["pdf", "docx", "txt"],
    help="Hỗ trợ PDF, Word (.docx), Text. Tối đa 20MB",
)
st.markdown('</div>', unsafe_allow_html=True)

if uploaded:
    col1, col2 = st.columns([3, 1])
    with col1:
        st.info(f"📎 `{uploaded.name}` — {uploaded.size / 1024:.1f} KB")
    with col2:
        upload_btn = st.button("🚀 Xử lý file", use_container_width=True, type="primary")

    if upload_btn:
        with st.spinner("⏳ Đang trích xuất & chunk văn bản..."):
            try:
                resp = requests.post(
                    f"{API_BASE}/upload",
                    files={"file": (uploaded.name, uploaded.getvalue(), uploaded.type)},
                    timeout=60,
                )
                if resp.status_code == 200:
                    data = resp.json()
                    st.session_state.session_id = data["session_id"]
                    st.session_state.doc_info = data
                    st.session_state.questions = []
                    st.success(f"✅ Xử lý xong! **{data['total_chunks']} chunks** | Phương pháp: `{data['extraction_method']}`")
                else:
                    st.error(f"❌ Lỗi: {resp.json().get('detail', resp.text)}")
            except requests.exceptions.ConnectionError:
                st.error("❌ Không kết nối được API. Hãy chạy: `uvicorn app.api.main:app --reload`")

# ---------------------------------------------------------------------------
# Step 2: Generate
# ---------------------------------------------------------------------------
if st.session_state.session_id:
    st.markdown('<div class="card">', unsafe_allow_html=True)
    st.markdown("### ✨ Bước 2 — Sinh câu hỏi")
    info = st.session_state.doc_info
    st.markdown(f"📘 **{info.get('title', info.get('filename',''))}** — {info.get('total_chunks', 0)} chunks")

    gen_btn = st.button("🎯 Sinh câu hỏi", use_container_width=True, type="primary")
    st.markdown('</div>', unsafe_allow_html=True)

    if gen_btn:
        progress = st.progress(0, text="Khởi động LLM...")
        start = time.time()
        try:
            resp = requests.post(
                f"{API_BASE}/generate",
                json={
                    "session_id": st.session_state.session_id,
                    "num_questions": num_questions,
                    "difficulty": difficulty,
                    "topic_hint": topic_hint,
                    "language": language,
                    "auto_review": auto_review,
                },
                timeout=300,
            )
            progress.progress(90, text="Đang xử lý kết quả...")
            if resp.status_code == 200:
                data = resp.json()
                st.session_state.questions = data["questions"]
                elapsed = time.time() - start
                progress.progress(100, text=f"Hoàn thành trong {elapsed:.1f}s")
                st.success(f"✅ Sinh được **{len(data['questions'])} câu hỏi** trong {elapsed:.1f}s")
            else:
                progress.empty()
                st.error(f"❌ Lỗi: {resp.json().get('detail', resp.text)}")
        except Exception as e:
            progress.empty()
            st.error(f"❌ Lỗi: {e}")

# ---------------------------------------------------------------------------
# Step 3: Preview & Download
# ---------------------------------------------------------------------------
if st.session_state.questions:
    questions = st.session_state.questions
    st.markdown("---")

    # Stats
    c1, c2, c3, c4 = st.columns(4)
    bloom_counts = {}
    for q in questions:
        bl = q.get("bloom_level", "?")
        bloom_counts[bl] = bloom_counts.get(bl, 0) + 1

    with c1:
        st.markdown(f'<div class="stat-box"><div class="stat-num">{len(questions)}</div><div class="stat-label">Tổng câu hỏi</div></div>', unsafe_allow_html=True)
    with c2:
        diff_emoji = {"easy": "🟢", "medium": "🟡", "hard": "🔴"}.get(difficulty, "⚪")
        st.markdown(f'<div class="stat-box"><div class="stat-num">{diff_emoji}</div><div class="stat-label">{difficulty.capitalize()}</div></div>', unsafe_allow_html=True)
    with c3:
        most_bloom = max(bloom_counts, key=bloom_counts.get) if bloom_counts else "N/A"
        st.markdown(f'<div class="stat-box"><div class="stat-num">🎓</div><div class="stat-label">Bloom: {most_bloom}</div></div>', unsafe_allow_html=True)
    with c4:
        st.markdown(f'<div class="stat-box"><div class="stat-num">📥</div><div class="stat-label">Sẵn sàng tải</div></div>', unsafe_allow_html=True)

    st.markdown("### 📋 Xem trước câu hỏi")

    # Search
    search = st.text_input("🔍 Tìm kiếm câu hỏi", placeholder="Nhập từ khoá...")
    filtered = [q for q in questions if search.lower() in q.get("question","").lower()] if search else questions

    for i, q in enumerate(filtered, 1):
        opts = q.get("options", {})
        correct = q.get("correct_answer", "")
        diff_badge = q.get("difficulty", difficulty)
        bloom = q.get("bloom_level", "")

        opts_html = ""
        for k, v in opts.items():
            cls = "opt-correct" if k == correct else "opt"
            icon = "✓ " if k == correct else "   "
            opts_html += f'<div class="{cls}">{icon}<b>{k}.</b> {v}</div>'

        badge_class = f"badge-{diff_badge}"
        st.markdown(f"""
        <div class="q-card">
            <div class="q-num">Câu {i}</div>
            <div class="q-text">{q.get("question","")}</div>
            {opts_html}
            <div class="explain">💡 {q.get("explanation","")}</div>
            <div style="margin-top:0.5rem">
                <span class="badge {badge_class}">{diff_badge}</span>
                <span class="badge" style="background:#1e3a5f;color:#60a5fa;">🎓 {bloom}</span>
                {"<span class='badge' style='background:#14332e;color:#34d399;'>📌 " + q.get("source_hint","") + "</span>" if q.get("source_hint") else ""}
            </div>
        </div>
        """, unsafe_allow_html=True)

    # Download
    st.markdown("### 📥 Tải xuống")
    dl_btn = st.button(f"⬇️ Tải file .{export_fmt.upper()}", use_container_width=True, type="primary")
    if dl_btn:
        sid = st.session_state.session_id
        try:
            resp = requests.get(f"{API_BASE}/download/{sid}?format={export_fmt}", timeout=30)
            if resp.status_code == 200:
                mime_map = {
                    "json": "application/json",
                    "excel": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "txt": "text/plain",
                }
                ext_map = {"json": "json", "excel": "xlsx", "txt": "txt"}
                st.download_button(
                    label=f"💾 Lưu quiz.{ext_map[export_fmt]}",
                    data=resp.content,
                    file_name=f"quiz.{ext_map[export_fmt]}",
                    mime=mime_map[export_fmt],
                )
            else:
                st.error(f"Lỗi tải file: {resp.text}")
        except Exception as e:
            st.error(f"Lỗi: {e}")

# Footer
st.markdown("---")
st.markdown(
    '<p style="text-align:center;color:#475569;font-size:0.8rem;">Quiz Generator AI · Powered by LLM · '
    'FastAPI + Streamlit</p>',
    unsafe_allow_html=True
)
