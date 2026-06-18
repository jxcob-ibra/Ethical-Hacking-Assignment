FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    openssl \
    openssh-server \
    sudo \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite ssl

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create uploads directory with proper permissions
RUN mkdir -p /var/www/html/app/uploads && \
    chown -R www-data:www-data /var/www/html/app/uploads && \
    chmod -R 755 /var/www/html/app/uploads

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Create self-signed certificate for HTTPS lab mode
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/server.key \
    -out /etc/apache2/ssl/server.crt \
    -subj "/C=US/ST=Lab/L=Lab/O=MyEduConnect/CN=localhost" && \
  sed -i 's|SSLCertificateFile.*|SSLCertificateFile /etc/apache2/ssl/server.crt|' /etc/apache2/sites-available/default-ssl.conf && \
  sed -i 's|SSLCertificateKeyFile.*|SSLCertificateKeyFile /etc/apache2/ssl/server.key|' /etc/apache2/sites-available/default-ssl.conf && \
  a2ensite default-ssl

# Lab SSH account for weak credential demonstrations
RUN useradd -m -s /bin/bash student || true && \
    echo 'student:Str0ng!Lab#Pass_2026' | chpasswd && \
    mkdir -p /var/run/sshd && \
    sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    echo 'www-data ALL=(ALL) NOPASSWD: /var/www/html/scripts/enable_weak_ssh.sh, /var/www/html/scripts/disable_weak_ssh.sh, /var/www/html/scripts/enable_weak_sudo.sh, /var/www/html/scripts/disable_weak_sudo.sh' >> /etc/sudoers

# Configure PHP
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/upload-limit.ini && \
    echo "post_max_size=10M" >> /usr/local/etc/php/conf.d/upload-limit.ini

# Expose HTTP, HTTPS, and SSH ports
EXPOSE 80 443 22

# Start Apache and SSH in foreground
CMD ["/bin/bash", "-c", "service ssh start || true; exec apache2-foreground"]
