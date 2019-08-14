#!/usr/bin/env python
import pika
import uuid
import base64
import json
import image_processing
import os

class RabbitMqWorker:

    def getConnection():
        host = os.environ['QUEUE_SERVER_HOST']
        port = os.environ['QUEUE_SERVER_PORT']
        username = os.environ['QUEUE_SERVER_USERNAME']
        password = os.environ['QUEUE_SERVER_PASSWORD']

        credentials = pika.PlainCredentials(username, password)
        connection = pika.BlockingConnection(
            pika.ConnectionParameters(host=host, port=port, credentials=credentials))
        return connection

    def listenQueue():
        try:
            connection = RabbitMqWorker.getConnection()
            channel = connection.channel()
            channel.queue_declare(queue='image_processing')

            channel.basic_consume(
                queue='image_processing', on_message_callback=RabbitMqWorker.processImage, auto_ack=True)

            print('Waiting for messages.')
            channel.start_consuming()
        except Exception as e:
            print("Got Exception!")
            print(e)

    def processImage(ch, method, properties, body):
        try:
            print('Received message')

            idFile = properties.headers['id']
            fileExtension = properties.headers['file_extension']

            filePath = '/tmp/' + str(uuid.uuid4()) + '.' + fileExtension
            filePtr = open(filePath, 'w+b')
            filePtr.write(body)
            filePtr.close()

            passport = image_processing.Passport(filePath)
            isProcessSuccess = passport.processFullName()

            if (isProcessSuccess == False):
                msg = {'error': 'Error on image processing'}
            else:
                surnameFilePaths = passport.getProcessedSurnameFilePaths()
                nameFilePaths = passport.getProcessedNameFilePaths()
                patronymicFilePaths = passport.getProcessedPatronymicFilePaths()

                msg = {
                    'error': '',
                    'name': RabbitMqWorker.encodeFilesBase64(nameFilePaths),
                    'surname': RabbitMqWorker.encodeFilesBase64(surnameFilePaths),
                    'patronymic': RabbitMqWorker.encodeFilesBase64(patronymicFilePaths)
                }

            connection = RabbitMqWorker.getConnection()
            channel = connection.channel()
            channel.queue_declare(queue='image_recognition')

            channel.basic_publish(exchange='',
                                  routing_key='image_recognition',
                                  body=json.dumps(msg),
                                  properties=pika.BasicProperties(headers={'id': idFile})
                                  )
        except Exception as e:
            print("Got Exception!")
            print(e)

    def fileGetContents(filename):
        with open(filename, 'rb') as f:
            return f.read()

    # Explanation why base64 in json: https://stackoverflow.com/questions/37225035/serialize-in-json-a-base64-encoded-data
    def encodeFilesBase64(filePaths):
        resultEncoded = []
        for filePath in filePaths:
            resultEncoded.append(base64.b64encode(RabbitMqWorker.fileGetContents(filePath)).decode('utf-8'))
        return resultEncoded

# Start listen document for processing
RabbitMqWorker.listenQueue()
