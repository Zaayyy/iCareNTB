FROM php:8.2-apache

# Install SQLite dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Create data directory and set permissions for www-data
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/data

# Support dynamic PORT environment variable (Render, Railway, Fly.io, Cloud Run)
COPY <<'EOF' /usr/local/bin/start-container
#!/bin/bash
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80

CMD ["/usr/local/bin/start-container"]

