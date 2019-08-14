FROM jjanzic/docker-python3-opencv

WORKDIR /app/scripts

RUN pip install imutils && \
    pip install pika

COPY ./start.sh /app/
RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]