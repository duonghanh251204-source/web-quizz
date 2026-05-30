"""
llm_service.py
Wrapper thống nhất gọi Gemini / OpenAI / Ollama, parse JSON output, tự retry.
"""
from __future__ import annotations

import json
import os
import re
import time
from typing import Any, Dict, List, Optional

from dotenv import load_dotenv
from loguru import logger

load_dotenv()

PROVIDER = os.getenv("LLM_PROVIDER", "gemini").lower()


# ---------------------------------------------------------------------------
# JSON extraction helper
# ---------------------------------------------------------------------------

def _extract_json(text: str) -> Any:
    """Cố gắng parse JSON từ text thô (đề phòng LLM bọc trong markdown)."""
    # 1. Thử parse thẳng
    try:
        return json.loads(text.strip())
    except json.JSONDecodeError:
        pass

    # 2. Tìm JSON array trong text
    match = re.search(r'\[.*\]', text, re.DOTALL)
    if match:
        try:
            return json.loads(match.group())
        except json.JSONDecodeError:
            pass

    # 3. Tìm JSON object
    match = re.search(r'\{.*\}', text, re.DOTALL)
    if match:
        try:
            return json.loads(match.group())
        except json.JSONDecodeError:
            pass

    raise ValueError(f"Không parse được JSON từ output LLM:\n{text[:300]}")


# ---------------------------------------------------------------------------
# Provider wrappers
# ---------------------------------------------------------------------------
def _call_deepseek(system_prompt: str, user_prompt: str, max_tokens: int = 8192) -> str:
    from openai import OpenAI
    
    client = OpenAI(
        api_key=os.getenv("DEEPSEEK_API_KEY", ""),
        base_url="https://api.deepseek.com",
    )
    
    model_name = os.getenv("DEEPSEEK_MODEL", "deepseek-chat")
    
    resp = client.chat.completions.create(
        model=model_name,
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
        max_tokens=max_tokens,
        temperature=0.7,
        stream=False,
    )
    return resp.choices[0].message.content or ""

def _call_gemini(system_prompt: str, user_prompt: str, max_tokens: int = 8192) -> str:
    import google.generativeai as genai  # type: ignore
    api_key = os.getenv("GEMINI_API_KEY", "")
    model_name = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")
    genai.configure(api_key=api_key)
    model = genai.GenerativeModel(
        model_name=model_name,
        system_instruction=system_prompt,
    )
    resp = model.generate_content(
        user_prompt,
        generation_config=genai.GenerationConfig(
            max_output_tokens=max_tokens,
            temperature=0.7,
            response_mime_type="application/json",
        ),
    )
    return resp.text


def _call_openai(system_prompt: str, user_prompt: str, max_tokens: int = 4096) -> str:
    from openai import OpenAI  # type: ignore
    client = OpenAI(api_key=os.getenv("OPENAI_API_KEY", ""))
    model_name = os.getenv("OPENAI_MODEL", "gpt-4o-mini")
    resp = client.chat.completions.create(
        model=model_name,
        response_format={"type": "json_object"},
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user",   "content": user_prompt},
        ],
        max_tokens=max_tokens,
        temperature=0.7,
    )
    return resp.choices[0].message.content or ""


def _call_ollama(system_prompt: str, user_prompt: str, max_tokens: int = 4096) -> str:
    import httpx  # type: ignore
    base_url = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
    model_name = os.getenv("OLLAMA_MODEL", "llama3")
    payload = {
        "model": model_name,
        "messages": [
            {"role": "system",  "content": system_prompt},
            {"role": "user",    "content": user_prompt},
        ],
        "stream": False,
        "options": {"num_predict": max_tokens},
    }
    r = httpx.post(f"{base_url}/api/chat", json=payload, timeout=120)
    r.raise_for_status()
    return r.json()["message"]["content"]

def _call_openrouter(system_prompt: str, user_prompt: str, max_tokens: int = 4096) -> str:
    """OpenRouter - 1000+ models, OpenAI-compatible API."""
    from openai import OpenAI  # type: ignore
    
    client = OpenAI(
        base_url="https://openrouter.ai/api/v1",
        api_key=os.getenv("OPENROUTER_API_KEY", ""),
    )
    
    model_name = os.getenv("OPENROUTER_MODEL", "google/gemma-4-31b-it:free")
    
    resp = client.chat.completions.create(
        model=model_name,
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user",   "content": user_prompt},
        ],
        max_tokens=max_tokens,
        temperature=0.7,
        extra_body={
            "reasoning": {"enabled": True},  # ✅ Tính năng đặc biệt OpenRouter
        },
    )
    return resp.choices[0].message.content or ""

# ---------------------------------------------------------------------------
# Public API
# ---------------------------------------------------------------------------

def generate_questions(
    system_prompt: str,
    user_prompt: str,
    max_retries: int = 3,
) -> List[Dict]:
    """
    Gọi LLM và trả về list câu hỏi đã parse.
    Tự retry nếu parse JSON thất bại.
    """
    provider = PROVIDER
    for attempt in range(1, max_retries + 1):
        try:
            logger.info(f"Gọi LLM [{provider}] — lần {attempt}")
            if provider == "gemini":
                raw = _call_gemini(system_prompt, user_prompt)
            elif provider == "deepseek":  # ← THÊM DÒNG NÀY
                raw = _call_deepseek(system_prompt, user_prompt)
            elif provider == "openai":
                raw = _call_openai(system_prompt, user_prompt)
            elif provider == "openrouter":
                raw = _call_openrouter(system_prompt, user_prompt)
            elif provider == "ollama":
                raw = _call_ollama(system_prompt, user_prompt)
            else:
                raise ValueError(f"Provider không hỗ trợ: {provider}")

            result = _extract_json(raw)
            # Đảm bảo trả về list
            if isinstance(result, dict) and "questions" in result:
                result = result["questions"]
            if not isinstance(result, list):
                result = [result]
            logger.success(f"Sinh được {len(result)} câu hỏi")
            return result

        except Exception as e:
            logger.warning(f"Lần {attempt} thất bại: {e}")
            if attempt < max_retries:
                time.sleep(2 ** attempt)  # Exponential backoff
            else:
                raise RuntimeError(f"LLM thất bại sau {max_retries} lần: {e}") from e

    return []
