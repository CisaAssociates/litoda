FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite proxy proxy_http

# Install system dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install Python dependencies
RUN pip3 install --no-cache-dir -r requirements.txt

# Apache config
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chmod +x start.sh && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["bash", "start.sh"]
