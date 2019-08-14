FROM richarvey/nginx-php-fpm

WORKDIR /var/www/app

RUN rm -Rf /etc/nginx/sites-enabled/*

ADD nginx/site.conf /etc/nginx/sites-available/site.conf

RUN ln -s /etc/nginx/sites-available/site.conf /etc/nginx/sites-enabled/site.conf

# For RabbitMq
RUN docker-php-ext-install bcmath sockets

COPY ./start-yii2.sh /
RUN chmod +x /start-yii2.sh

CMD ["/start-yii2.sh"]