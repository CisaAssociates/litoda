#!/bin/bash

# Use PORT environment variable provided by Railway, default to 80
PORT=${PORT:-80}

# Configure Apache to listen on the correct port
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

# Start Python backend in background
# We bind to 127.0.0.1:5000 so PHP can access it internally
echo "Starting Python Face Recognition System..."
gunicorn face_recognition_system:app --bind 127.0.0.1:5000 --timeout 120 --daemon

# Start Apache in foreground
echo "Starting Apache..."
apache2-foreground
