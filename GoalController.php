<?php

namespace app\modules\structure\controllers;

use app\models\structure\Goal;
use app\models\structure\Offer;
use app\models\user\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class GoalController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['create', 'update', 'delete', 'clone', 'restore'],
                        'allow' => true,
                        'roles' => [User::ROLE_MANAGER_SALES]
                    ],
                    [
                        'allow' => false,
                        'roles' => ['*'],
                    ],
                ]
            ],
        ];
    }

    /**
     * Creates a new Source model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id)
    {
        $offer = Offer::findOne($id);
        if (empty($offer)) {
            throw new NotFoundHttpException();
        }

        $model = new Goal();
        $model->offer_id = $id;

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return $this->redirect(['offer/view', 'id' => $id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'currencies' => \app\models\search\Currency::getDropodownList(),
        ]);
    }

    public function actionClone($id)
    {
        $model = $this->findModel($id);
        $newModel = new Goal();
        $newModel->setAttributes($model->attributes, false);
        $newModel->id = null;
        $newModel->save(false);

        return $this->redirect(['update', 'id' => $newModel->id]);
    }

    /**
     * Updates an existing Source model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost && Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['offer/view', 'id' => $model->offer_id]);
        }

        return $this->render('update', [
            'model' => $model,
            'currencies' => \app\models\search\Currency::getDropodownList(),
        ]);
    }

    /**
     * Deletes an existing Source model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        return $this->redirect(['offer/view', 'id' => $model->offer_id]);
    }

    public function actionRestore($id)
    {
        $model = $this->findModel($id);
        $model->is_deleted = 0;
        $model->save(false);

        return $this->redirect(['offer/view', 'id' => $model->offer_id]);
    }

    /**
     * Finds the Source model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Goal the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Goal::findOne($id)) !== null) {
            if (!$model->offer->isUserHasEditAccess(Yii::$app->user->identity)) {
                throw new ForbiddenHttpException();
            }
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
