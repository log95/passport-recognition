#!/usr/bin/env python
import pika
import json
import os
import pymysql
import pymysql.cursors
import re

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
            channel.queue_declare(queue='text_analyze')

            channel.basic_consume(
                queue='text_analyze', on_message_callback=RabbitMqWorker.analyzeNames, auto_ack=True)

            print('Waiting for messages.')
            channel.start_consuming()
        except Exception as e:
            print('Got exception')
            print(e)

    def analyzeNames(ch, method, properties, body):
        try:
            print('Received message')

            idFile = properties.headers['id']

            decodedData = json.loads(body)
            if (decodedData['error']):
                msg = {'error': decodedData['error'], 'id': idFile}
            else:
                msg = {
                    'id': idFile,
                    'error': '',
                    'name': RabbitMqWorker.chooseBetterName('name', decodedData['name']),
                    'surname': RabbitMqWorker.chooseBetterName('surname', decodedData['surname']),
                    'patronymic': RabbitMqWorker.chooseBetterName('patronymic', decodedData['patronymic'])
                }

            print(msg)

            connection = RabbitMqWorker.getConnection()
            channel = connection.channel()
            channel.queue_declare(queue='result_image_recognition')

            channel.basic_publish(exchange='',
                                  routing_key='result_image_recognition',
                                  body=json.dumps(msg)
                                  )


        except Exception as e:
            print('Got exception')
            print(e)


    def chooseBetterName(tableName, names):
        names = RabbitMqWorker.filterNames(names)
        betterName = names[0]
        connection = RabbitMqWorker.getDbConnection()
        try:
            with connection.cursor() as cursor:
                sql = "SELECT `name` FROM `" + tableName + "` WHERE `name` IN %s"
                cursor.execute(sql, (tuple(names),))
                nameInDb = cursor.fetchone()
                print("Result from query: ")
                print(nameInDb)
                if (nameInDb):
                    print("in db better = " + nameInDb[0])
                    betterName = nameInDb[0]
        finally:
            connection.close()

        return betterName


    def getDbConnection():
        dbHost = 'analysis-db'
        dbName = os.environ['ANALYSIS_DB_NAME']
        dbUser = os.environ['ANALYSIS_DB_USERNAME']
        dbPasswd = os.environ['ANALYSIS_DB_PASSWORD']
        return pymysql.connect(dbHost, dbUser, dbPasswd, dbName)

    def filterNames(names):
        filteredNames = []
        for name in names:
            nameMatches = re.search("([А-Я]+)", name)
            filteredName = '' if nameMatches is None else nameMatches.group(0)
            filteredNames.append(filteredName)

        return filteredNames



RabbitMqWorker.listenQueue()


