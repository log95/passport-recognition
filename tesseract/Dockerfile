FROM tesseractshadow/tesseract4re

WORKDIR /app/scripts

RUN apt-get update && \
    apt install python3-pip -y && \
    pip3 install pika

COPY ./start.sh /app/
RUN chmod +x /app/start.sh

# Prevent error: 'ascii' codec can't decode byte 0xd0 in position 0: ordinal not in range(128)
RUN apt-get install -y locales && \
    locale-gen en_US.UTF-8 && \
    update-locale LC_ALL=en_US.UTF-8 LANG=en_US.UTF-8
ENV LANG='en_US.UTF-8' LANGUAGE='en_US:en' LC_ALL='en_US.UTF-8'

CMD ["/app/start.sh"]