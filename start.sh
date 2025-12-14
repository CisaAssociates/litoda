#!/bin/bash

PORT=${PORT:-80}

# Update Apache port to Railway port
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

echo "🚀 Starting Python Face Recognition API..."
gunicorn face_recognition_system:app \
  --bind 0.0.0.0:5000 \
  --timeout 180 \
  --workers 1 \
  --log-level debug \
  --access-logfile - \
  --error-logfile - &

echo "🌐 Starting Apache Web Server..."
apache2-foreground
