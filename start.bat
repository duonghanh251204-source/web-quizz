@echo off
echo ========================================================
echo  PRX AI Quiz - KHOI DONG HE THONG
echo ========================================================
echo.
echo He thong PHP MVC (PRX) chay qua XAMPP Apache.
echo Vui long dam bao Apache va MySQL dang chay trong XAMPP Control Panel.
echo Truy cap: http://localhost/PRX
echo.
echo --------------------------------------------------------
echo  Khoi dong CHATBOT-AI Python FastAPI
echo  (Chi can thiet neu AI_PROVIDER=chatbot_ai trong .env)
echo --------------------------------------------------------
echo.

cd /d "%~dp0CHATBOT-AI"

IF NOT EXIST "venv\Scripts\activate.bat" (
    echo [!] Chua co virtual environment.
    echo [i] Chay cac lenh sau de cai dat:
    echo     python -m venv venv
    echo     venv\Scripts\activate
    echo     pip install -r requirements.txt
    echo.
    pause
    exit /b 1
)

echo [+] Dang khoi dong FastAPI tai http://localhost:8000 ...
echo [i] Nhan Ctrl+C de dung server.
echo.
call venv\Scripts\activate.bat && uvicorn app.api.main:app --reload --port 8000

pause
