FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql 2>/dev/null || true

# Copy portal files
COPY MyPortal.php /var/www/html/captiveportal/
COPY index.php    /var/www/html/captiveportal/
COPY helper.php   /var/www/html/captiveportal/
COPY config.json  /var/www/html/captiveportal/

# Create logs directory with proper permissions
RUN mkdir -p /var/www/html/captiveportal/logs &&     chown -R www-data:www-data /var/www/html/captiveportal

# Apache config for captive portal directory
RUN echo '<Directory /var/www/html/captiveportal>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
<Directory /var/www/html>
    RedirectMatch 301 ^/$ /captiveportal/
</Directory>' > /etc/apache2/conf-available/captiveportal.conf &&     a2enconf captiveportal

EXPOSE 80

CMD ["apache2-foreground"]
