#!/bin/bash

# Get port from Railway (defaults to 8080 if not set)
PORT=${PORT:-8080}

echo "🔧 Configuring Apache for port $PORT..."

# Update Apache to listen on Railway's port
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

echo "🚀 Starting Python Face Recognition API on port 5000..."

# Activate virtual environment and start gunicorn in background
. /opt/venv/bin/activate && \
gunicorn face_recognition_system:app \
  --bind 0.0.0.0:5000 \
  --timeout 180 \
  --workers 1 \
  --threads 2 \
  --log-level info \
  --access-logfile - \
  --error-logfile - &

# Wait a bit for Python to start
sleep 3

echo "✅ Python API started"
echo "🌐 Starting Apache Web Server on port $PORT..."

# Start Apache in foreground
apache2-foreground
