<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=yii2-db;dbname=' . getenv('DB_NAME'),
    'username' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD'),
    'charset' => 'utf8',
];
