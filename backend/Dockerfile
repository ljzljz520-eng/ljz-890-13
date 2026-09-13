FROM php:8.2-apache

# 安装 PDO MySQL 扩展
RUN docker-php-ext-install pdo pdo_mysql

# 配置 PHP 上传限制
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/uploads.ini

# 启用 Apache rewrite 模块
RUN a2enmod rewrite

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . /var/www/html/

# 创建上传目录在public内并设置权限
RUN mkdir -p /var/www/html/public/uploads && \
    chown -R www-data:www-data /var/www/html/public/uploads && \
    chmod -R 755 /var/www/html/public/uploads

# 设置 DocumentRoot 为 public 目录
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 配置 Apache 允许 .htaccess
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/memorial.conf && \
    a2enconf memorial

# 创建 .htaccess 文件用于 URL 重写
RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteCond %{REQUEST_FILENAME} !-d\n\
RewriteRule ^(.*)$ index.php [QSA,L]' > /var/www/html/public/.htaccess

# 创建启动脚本
RUN echo '#!/bin/bash\n\
echo "Waiting for database..."\n\
sleep 5\n\
echo "Running data initializer..."\n\
php /var/www/html/init-data.php\n\
echo "Starting Apache..."\n\
apache2-foreground' > /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

# 暴露端口
EXPOSE 80

# 使用启动脚本
CMD ["/usr/local/bin/start.sh"]
