<?php

use yii\db\Migration;
use app\models\User;

/**
 * Handles the creation of table `{{%document}}`.
 */
class m190612_170000_add_admin_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $model = User::find()->where(['username' => 'admin'])->one();
        if (empty($model)) {
            $user = new User();
            $user->username = 'admin';
            $user->email = 'admin@test.ru';
            $user->setPassword(getenv('USER_ADMIN_PASSWORD'));
            $user->generateAuthKey();
            $user->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }
}
