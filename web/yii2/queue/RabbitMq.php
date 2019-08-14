<?php

namespace app\queue;

use app\models\Document;
use Yii;
use yii\db\AfterSaveEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq
{
    public static function getConnection()
    {
        return new AMQPStreamConnection(
            getenv('QUEUE_SERVER_HOST'),
            getenv('QUEUE_SERVER_PORT'),
            getenv('QUEUE_SERVER_USERNAME'),
            getenv('QUEUE_SERVER_PASSWORD')
        );
    }

    public static function sendDocumentForRecognition(AfterSaveEvent $event)
    {
        try {
            /** @var Document $document */
            $document = $event->sender;

            $fileFullPath = $_SERVER['DOCUMENT_ROOT'] . $document->file;
            $fileInfo = pathinfo($fileFullPath);
            $fileExtension = $fileInfo['extension'];

            $connection = self::getConnection();
            $channel = $connection->channel();
            $channel->queue_declare('image_processing', false, false, false, false);

            $msg = new AMQPMessage(file_get_contents($fileFullPath));
            $msg->set(
                'application_headers',
                array(
                    'id' => array('I', $document->id),
                    'file_extension' => array('S', $fileExtension),
                )
            );
            $channel->basic_publish($msg, '', 'image_processing');
            $channel->close();
            $connection->close();

        } catch (\Throwable $e) {
            Yii::error($e->getMessage());
        }
    }

    public static function setDocumentResult(AMQPMessage $msg)
    {
        try {
            $dataJson = $msg->getBody();
            $data = json_decode($dataJson);

            echo "Received message.";

            $idDocument = $data->id;
            if (!$idDocument) {
                return;
            }

            $document = Document::findOne($idDocument);

            if ($data->error) {
                $document->status = Document::STATUS_ERROR;
                $document->description = $data->error;
            } else {
                $document->status = Document::STATUS_SUCCESS;
                $document->name = self::formatName($data->name);
                $document->surname = self::formatName($data->surname);
                $document->patronymic = self::formatName($data->patronymic);
            }

            $document->save();

        } catch (\Throwable $e) {
            Yii::error($e->getMessage());
        }
    }

    private static function formatName(string $name) : string
    {
        if (strlen($name) <= 2) {
            return $name;
        }

        $firstLetter = mb_strtoupper(mb_substr($name, 0, 1));
        $otherLetters = mb_strtolower(mb_substr($name, 1));
        return $firstLetter . $otherLetters;
    }

    public static function listenDocumentRecognition()
    {
        $connection = self::getConnection();
        $channel = $connection->channel();
        $channel->queue_declare('result_image_recognition', false, false, false, false);

        $channel->basic_consume(
            'result_image_recognition',
            '',
            false,
            true,
            false,
            false,
            [
                self::class,
                'setDocumentResult'
            ]
        );

        echo "Waiting messages.";

        while (count($channel->callbacks)) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }
}