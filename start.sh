#!/bin/bash
set -e  # Exit immediately if any command fails

echo "========================================"
echo "LITODA APPLICATION STARTUP"
echo "========================================"

# Use PORT environment variable provided by Railway, default to 80
PORT=${PORT:-80}
echo "Configuring Apache to listen on port: $PORT"

# Configure Apache to listen on the correct port
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

echo "========================================"
echo "STARTING PYTHON FACE RECOGNITION SYSTEM"
echo "========================================"

# Change to application directory
cd /var/www/html

# Verify face_recognition_system.py exists
if [ ! -f "face_recognition_system.py" ]; then
    echo "ERROR: face_recognition_system.py not found in /var/www/html"
    echo "Current directory contents:"
    ls -la
    exit 1
fi

# Activate Python virtual environment
echo "Activating Python virtual environment..."
source /opt/venv/bin/activate

# Verify gunicorn is installed
if ! command -v gunicorn &> /dev/null; then
    echo "ERROR: gunicorn not found in virtual environment"
    echo "Installing gunicorn..."
    pip install gunicorn
fi

# Start Gunicorn with Python backend
echo "Starting Gunicorn on 127.0.0.1:5000..."
gunicorn face_recognition_system:app \
    --bind 127.0.0.1:5000 \
    --workers 1 \
    --timeout 120 \
    --daemon \
    --access-logfile /tmp/gunicorn-access.log \
    --error-logfile /tmp/gunicorn-error.log \
    --log-level info

# Wait for Python to start
echo "Waiting for Python server to start..."
sleep 5

# Verify Python server is running
if curl -f http://127.0.0.1:5000/api/face/health > /dev/null 2>&1; then
    echo "✓ Python Face Recognition System started successfully"
else
    echo "ERROR: Python server failed to start!"
    echo ""
    echo "=== GUNICORN ERROR LOGS ==="
    cat /tmp/gunicorn-error.log 2>/dev/null || echo "No error log found"
    echo ""
    echo "=== GUNICORN ACCESS LOGS ==="
    cat /tmp/gunicorn-access.log 2>/dev/null || echo "No access log found"
    exit 1
fi

echo "========================================"
echo "STARTING APACHE WEB SERVER"
echo "========================================"
echo "Apache will listen on port: $PORT"

# Start Apache in foreground (this keeps the container running)
exec apache2-foreground
