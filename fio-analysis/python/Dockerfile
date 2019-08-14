FROM python:3

WORKDIR /app/scripts

COPY ./requirements.txt /usr/src/app/
RUN pip install --no-cache-dir -r /usr/src/app/requirements.txt

COPY ./start.sh /app/
RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]