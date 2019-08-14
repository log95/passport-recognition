#!/usr/bin/env python
import pika
import uuid
import base64
import json
import os
from subprocess import check_output

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
            channel.queue_declare(queue='image_recognition')

            channel.basic_consume(
                queue='image_recognition', on_message_callback=RabbitMqWorker.recognizeNames, auto_ack=True)

            print('Waiting for messages.')
            channel.start_consuming()
        except Exception as e:
            print("Got Exception!")
            print(e)

    def recognizeNames(ch, method, properties, body):
        try:
            print('Received message')

            idFile = properties.headers['id']

            decodedData = json.loads(body)
            if (decodedData['error']):
                msg = {'error': decodedData['error']}
            else:
                msg = {
                    'name': RabbitMqWorker.recognizeEncodedImages(decodedData['name']),
                    'surname': RabbitMqWorker.recognizeEncodedImages(decodedData['surname']),
                    'patronymic': RabbitMqWorker.recognizeEncodedImages(decodedData['patronymic']),
                    'error': ''
                }

            print(msg)

            connection = RabbitMqWorker.getConnection()
            channel = connection.channel()
            channel.queue_declare(queue='text_analyze')

            channel.basic_publish(exchange='',
                                  routing_key='text_analyze',
                                  body=json.dumps(msg),
                                  properties=pika.BasicProperties(headers={'id': idFile})
                                  )


        except Exception as e:
            print("Got Exception!")
            print(e)


    def recognizeEncodedImages(encodedImages):
        resultNames = []
        for encodedFileContent in encodedImages:
            decodedFileContent = base64.b64decode(encodedFileContent)
            filePath = RabbitMqWorker.getUniqueFilePath()
            filePtr = open(filePath, 'w+b')
            filePtr.write(decodedFileContent)
            filePtr.close()

            recognizedName = check_output(["tesseract", filePath, "stdout", "-l", "rus"], universal_newlines=True)
            resultNames.append(RabbitMqWorker.cleanString(recognizedName))

        return resultNames

    def cleanString(s):
        return s.strip(' \t\n\r')

    def getUniqueFilePath():
        return '/tmp/' + str(uuid.uuid4()) + '.png'


RabbitMqWorker.listenQueue()


