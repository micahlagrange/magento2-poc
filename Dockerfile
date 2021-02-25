FROM 875988342918.dkr.ecr.us-east-1.amazonaws.com/webscale-php-fpm:php-7.4 AS compiled

WORKDIR /app

COPY auth.json composer.* /app/
COPY patches /app/patches

RUN composer install --no-dev

COPY . /app/

RUN chmod +x bin/magento
RUN php -d memory_limit=-1 bin/magento setup:di:compile
RUN bin/magento setup:static-content:deploy -fj 16

# Second stage, sans build time secrets and packages
FROM 875988342918.dkr.ecr.us-east-1.amazonaws.com/webscale-php-fpm:php-7.4

WORKDIR /var/www/html
ENV MAGE_MODE=production

COPY --chown=33:33 --from=compiled /app/vendor /var/www/html/vendor
COPY --chown=33:33 --from=compiled /app/generated /var/www/html/generated
COPY --chown=33:33 --from=compiled /app/pub/static /var/www/html/pub/static
COPY --chown=33:33 app /var/www/html/app
COPY --chown=33:33 bin /var/www/html/bin
COPY --chown=33:33 lib /var/www/html/lib
COPY --chown=33:33 pub /var/www/html/pub
COPY --chown=33:33 setup /var/www/html/setup
COPY --chown=33:33 composer.* /var/www/html/

EXPOSE 9000
CMD ["php-fpm"]

