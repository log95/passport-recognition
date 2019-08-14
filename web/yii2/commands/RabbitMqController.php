<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\queue\RabbitMq;

class RabbitMqController extends Controller
{
    public function actionIndex()
    {
        RabbitMq::listenDocumentRecognition();

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
