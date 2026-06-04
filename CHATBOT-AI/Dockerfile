FROM python:3.11-slim

# Thiết lập thư mục làm việc
WORKDIR /app

# Cài đặt các thư viện hệ thống cần thiết (nếu có)
RUN apt-get update && apt-get install -y \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copy và cài đặt requirements
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy toàn bộ mã nguồn
COPY . .

# Expose cổng 8000
EXPOSE 8000

# Chạy ứng dụng FastAPI
CMD ["uvicorn", "app.api.main:app", "--host", "0.0.0.0", "--port", "8000"]
