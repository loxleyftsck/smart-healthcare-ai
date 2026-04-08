# Production Setup Guide — SmartHealth AI

This guide outlines the steps to deploy the SmartHealth AI platform for production on a Windows-based host (or Linux).

## 📊 1. Database (MySQL 8.0)
We recommend switching from SQLite to MySQL for production scaling.

1. **Install MySQL 8.0+**
2. **Enable PHP Extensions**: 
   Edit your `php.ini` and ensure the following are uncommented:
   ```ini
   extension=mysqli
   extension=pdo_mysql
   extension=zip
   extension=fileinfo
   extension=intl
   ```
3. **Configure .env**:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smart_health_ai
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

## 🧠 2. AI Triage Microservice (Local Mistral)
Since we are using local Mistral via Ollama:

1. **Keep Ollama Running**:
   Ensure `ollama serve` is running as a background service.
2. **Process Management**:
   Use **PM2** (via Node.js) or a Windows Service Wrapper to keep the Python service alive:
   ```bash
   cd ai-triage-service
   pm2 start "python -m uvicorn main:app --port 8001" --name triage-service
   ```

## 🔒 3. Environment Hardening
For production, update `smart-health-ai/.env`:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://yourdomain.com`
- `SANCTUM_STATEFUL_DOMAINS=yourdomain.com`

## 📡 4. Web Server (Nginx)
Use Nginx as a reverse proxy for both Laravel and the Python Microservice.

**Sample Nginx Config snippet:**
```nginx
server {
    listen 80;
    server_name medical.example.com;
    root /path/to/smart-health-ai/public;

    # Laravel Backend
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Python AI Microservice Proxy (Externalized)
    location /ai-triage/ {
        proxy_pass http://127.0.0.1:8001/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

## 📈 5. Health Monitoring
Access the built-in health endpoint:
- `GET /api/health`
This will return a 200 OK only if BOTH the database and the AI microservice are responding.
