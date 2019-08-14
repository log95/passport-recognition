<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use yii\data\ActiveDataProvider;
use app\models\Document;
use app\models\UploadForm;
use yii\web\UploadedFile;
use yii\base\Event;
use app\queue\RabbitMq;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'account'],
                'rules' => [
                    [
                        'actions' => ['logout', 'account'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['account']);
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionAccount()
    {
        Event::on(
            Document::class,
            Document::EVENT_AFTER_INSERT,
            [
                RabbitMq::class,
                'sendDocumentForRecognition'
            ]
        );

        $idCurrentUser = Yii::$app->user->identity->getId();
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->imageFiles = UploadedFile::getInstances($model, 'imageFiles');
            $filePaths = $model->upload();
            if ($filePaths) {
                foreach ($filePaths as $filePath) {
                    $document = new Document();
                    $document->file = $filePath;
                    $document->user_id = $idCurrentUser;
                    if (!$document->save()) {
                        throw new \RuntimeException('Ошибка сохранения документа. ' . serialize($document->getErrors()));
                    }
                }
                $this->redirect(['account']);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => Document::find()->where(['user_id' => $idCurrentUser]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render(
            'account',
            [
                'dataProvider' => $dataProvider,
                'model' => $model,
            ]
        );
    }
}
