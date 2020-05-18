FROM forceedge01/php71cli-composer

WORKDIR '/app'
COPY composer.json .
RUN composer install
COPY . .

CMD ["vendor/bin/phpunit", "-c", "tests"]
