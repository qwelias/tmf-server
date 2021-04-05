FROM php:5.6-alpine

RUN apk update && apk add curl
RUN docker-php-ext-install mysql mysqli pdo pdo_mysql

RUN curl 'http://www.undef.name/.downloads/xaseco-1.16.zip' -o archive.zip && \
    unzip archive.zip && rm archive.zip && \
    mv xaseco-1.16/* . && rm -rf xaseco-1.16 && \
    mv newinstall/*.xml . && mv newinstall/*.php includes

# COPY php7 ./

CMD ["php", "aseco.php", "TMF"]
