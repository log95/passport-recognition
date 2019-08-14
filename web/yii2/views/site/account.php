<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \yii\base\Model */

use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;
use app\models\Document;

$this->title = 'Аккаунт';
?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>
<?= $form->field($model, 'imageFiles[]')->fileInput(['multiple' => true])->label('Файлы') ?>
    <button>Сохранить</button>
    <br>
    <br>
    <br>
<?php ActiveForm::end() ?>

<?php
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        'surname',
        'name',
        'patronymic',

        [
            'label' => 'Файл',
            'format' => 'raw',
            'value' => function($data){
                return Html::img($data->file,[
                    'alt' => 'Файл',
                    'style' => 'width:40px;'
                ]);
            },
        ],

        [
            'label' => 'Статус',
            'format' => 'raw',
            'value' => function($data) {
                return Document::getStatusName($data->status);
            },
        ],

        [
            'attribute' => 'created_at',
            'format' =>  ['date', 'dd.MM.Y'],
        ],
    ],
]);
?>