FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite proxy proxy_http headers

# Install system dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy only requirements first for better caching
COPY requirements.txt .

# Create virtual environment and install Python dependencies
RUN python3 -m venv /opt/venv && \
    . /opt/venv/bin/activate && \
    pip install --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Copy project files
COPY . .

# Apache config
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chmod +x start.sh && \
    chown -R www-data:www-data /var/www/html && \
    mkdir -p uploads && \
    chmod 777 uploads

# Railway uses PORT environment variable
ENV PORT=8080
EXPOSE ${PORT}

CMD ["bash", "start.sh"]
