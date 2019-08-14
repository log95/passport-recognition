<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "document".
 *
 * @property int $id
 * @property int $user_id
 * @property string $file
 * @property string $surname
 * @property string $name
 * @property string $patronymic
 * @property int $status
 * @property string $description
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class Document extends \yii\db\ActiveRecord
{
    const STATUS_ERROR = 1;
    const STATUS_IN_PROCCESS = 2;
    const STATUS_SUCCESS = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'file'], 'required'],
            [['user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['file', 'surname', 'name', 'patronymic'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'file' => 'Файл',
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'patronymic' => 'Отчество',
            'status' => 'Статус',
            'description' => 'Описание',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public static function getStatusName(int $idStatus) : string
    {
        switch ($idStatus)
        {
            case self::STATUS_ERROR:
                return 'Ошибка';
            case self::STATUS_IN_PROCCESS:
                return 'Обработка';
            case self::STATUS_SUCCESS:
                return 'Готово';
            default:
                return 'Неизвестный статус';
        }
    }
}
