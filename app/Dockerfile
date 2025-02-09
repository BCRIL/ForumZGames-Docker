# Imagen base de PHP con Apache
FROM php:7.4-apache

# Instalar extensiones necesarias para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql

# Copiar los archivos de la aplicación
COPY ./ForumZGames /var/www/html/

# Configurar permisos para Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Exponer el puerto 80 para Apache
EXPOSE 80

# Iniciar Apache en modo foreground
CMD ["apache2-foreground"]
