<?php

namespace app\modules\reporting\controllers;

use app\models\user\enums\UserRole;
use app\models\user\User;
use Yii;
use app\components\traits\FlashAlert;
use app\models\search\Counteragent as CounteragentSearch;
use app\models\structure\counteragent\Counteragent;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ReportingController extends Controller
{
    use FlashAlert;

    /**
     * @return array[]
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'ajax' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CounteragentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->user->identity, true);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return array|bool[]|string
     */
    public function actionCreate()
    {
        $model = new Counteragent();

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if ($post && isset($post['Counteragent']['ord_token']) && isset($post['Counteragent']['ord_provider'])) {
            $model->linkOwner($user);
            $model->is_reporting_person = true;

            $result = [];
            if ($model->load($post) && $model->validate('ord_token')) {
                $model->is_active = $model->checkIsFirstCounteragent();

                if ($model->is_foreign) {
                    $model->foreign_oksm_country_code = $model->country->oksm ?? null;
                }

                if ($model->save()) {
                    $result = ['finish' => true];
                }else{
                    $result = ['error' => $model->ordResponse ?? $model->getErrors()]; // TODO такую конструкцию нужно обернуть в общий метод. Мб еще что-то дописать
                }
            } else {
                $result = ['error' => $model->getErrors()];
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $result;
            }
            return $this->render('view', [
                'model' => $model
            ]);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * @param $id
     * @return array|bool[]|string
     * @throws NotFoundHttpException
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if ($post && isset($post['Counteragent']['ord_token']) && isset($post['Counteragent']['ord_provider'])) {
            $model->linkOwner($user);
            $model->is_reporting_person = true;

            if ($model->load($post)) {
                if ($model->is_foreign) {
                    $model->foreign_oksm_country_code = $model->country->oksm ?? null;
                }

                if ($model->save()) {
                    $result = ['finish' => true];
                } else {
                    $result = ['error' => $model->ordResponse ?? $model->getErrors()];
                }
            } else {
                $result = ['error' => $model->getErrors()];
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $result;
            }

            return $this->render('view', [
                'model' => $model
            ]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @param $id
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $rowsAffected = $model->softDelete();
        if ($model->is_active && (int)$rowsAffected > 0) {
            Yii::$app->session->setFlash('alert', [
                'title' => 'Необходимо установить новое отчетное лицо',
                'body' => ''
            ]);
            return $this->redirect(Url::to(['/user']));
        }
        return $this->redirect(['index']);
    }

    /**
     * Get counteragent from ord
     * @return array
     */
    function actionGetCounteragentFromOrd()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new Counteragent();
        $model->linkOwner($user);
        $model->is_reporting_person = true;

        if ($model->load($post) && $model->validate('ord_token')) {
            return $model->getOrdData();
        }
        return ['error' => $model->ordResponse ?? $model->getErrors()];
    }

    public function actionRestore($id)
    {
        $this->findModel($id, false)->restore();

        return $this->redirect(['index']);
    }

    /**
     * @param $id
     * @return array|ActiveRecord|null
     * @throws NotFoundHttpException
     */
    protected function findModel($id, bool $hideArchived = true)
    {
        if (($model = Counteragent::findByUserRole(Yii::$app->user->identity, true, $hideArchived)->andWhere(['id' => $id])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
