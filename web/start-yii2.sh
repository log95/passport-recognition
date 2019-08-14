#!/usr/bin/env bash

# Создаём пустые файлы для логов. Их нет, так как файлы в .gitignore
# Устанавливаем зависимости проекта
# Выполняем миграции бд
# Манипулируем папками и файлами внутри /var/www/app, так как это volume
# и мы не можем ничего делать внутри Dockerfile
# Ждём пока поднимется RabbitMQ и после запускаем скрипт по обработке сообщений из очереди
# В конце запускаем скрипт от базового образа

cd /var/www/app && \
    touch log/frontend-access.log && \
    touch log/frontend-error.log && \
    touch log/queue.log && \
    composer install && \
    chmod +x yii && \
    ./wait-for-it.sh yii2-db:3306 --strict --timeout=100 -- \
    ./yii migrate --interactive=0 && \
    chown nginx:nginx -R /var/www/app && \
    chmod +x wait-for-it.sh && \
    ./wait-for-it.sh queue:5672 --strict --timeout=100 -- \
    nohup php /var/www/app/yii rabbit-mq >> /var/www/app/log/queue.log 2>&1 & \
    /start.sh