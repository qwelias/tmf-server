FROM php:7-alpine

RUN apk update && apk add curl

RUN curl 'http://www.undef.name/.downloads/xaseco-1.16.zip' -o archive.zip && \
    unzip archive.zip && rm archive.zip && \
    mv xaseco-1.16/* . && rm -rf xaseco-1.16 && \
    mv newinstall/*.xml . && mv newinstall/*.php includes

COPY php7 ./

CMD ["php", "aseco.php", "TMF"]
