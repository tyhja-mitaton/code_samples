<?php

namespace app\modules\structure\comments\controllers;

use app\components\traits\AccessTrait;
use app\models\comment_group\CommentGroup;
use app\models\comment_group\enums\Priority;
use app\models\comment_group\enums\Status;
use app\models\user\User;
use Yii;
use app\models\comment\Comment;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\search\CommentGroup as CommentGroupSearch;

class CommentController extends Controller
{
    use AccessTrait;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $searchGroupModel = new CommentGroupSearch();
        $groupProvider = $searchGroupModel->search(Yii::$app->request->queryParams, Yii::$app->user->identity);

        return $this->render('index', [
            'searchGroupModel' => $searchGroupModel,
            'groupProvider' => $groupProvider,
        ]);
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($model->owner_id);

        $status = Status::array();
        $priority = Priority::array();

        return $this->render('view', [
            'model' => $model,
            'status' => $status,
            'priority' => $priority,
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Comment();
        $this->setOwner($model);

        $status = Status::array();
        $priority = Priority::array();
        $commentGroupList = Comment::getGroupList(Yii::$app->user->identity);
        $activeList = User::getActiveList();

        if (!$this->checkCanCreateByTrial(Comment::class, User::TRIAL['comment'])) {
            return $this->redirect(Yii::$app->request->referrer);
        }

        if ($model->load(Yii::$app->request->post())) {
            if(empty($model->status)) {$model->status = $model->group->status;}
            if(empty($model->priority)) {$model->priority = $model->group->priority;}
            if($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', compact('model', 'status', 'priority', 'commentGroupList', 'activeList'));
    }

    /**
     * @param $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($model->owner_id);

        $status = Status::array();
        $priority = Priority::array();
        $commentGroupList = Comment::getGroupList(Yii::$app->user->identity);
        $activeList = User::getActiveList();

        if ($model->load(Yii::$app->request->post())) {
            if(empty($model->status)) {$model->status = $model->group->status;}
            if(empty($model->priority)) {$model->priority = $model->group->priority;}
            if($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', compact('model', 'status', 'priority', 'commentGroupList', 'activeList'));
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($model->owner_id);

        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * @param $id
     * @param $keyFile
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionImageDelete($id, $keyFile = null)
    {
        $model = $this->findModel($id);
        $this->checkAccess($model->owner_id);

        $model->deleteImage($keyFile);

        return $this->redirect(['update', 'id' => $model->id]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionClone($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($model->owner_id);
        $clonedModel = clone $model;
        $clonedModel->id = null;
        $clonedModel->isNewRecord = true;

        if($clonedModel->save(false)) {
            return $this->redirect(['view', 'id' => $clonedModel->id]);
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * @param $groupId
     * @return string|\yii\web\Response
     */
    public function actionGetComments($groupId)
    {
        Yii::$app->params['bsDependencyEnabled'] = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $groupModel = CommentGroup::findOne($groupId);
        $newComment = new Comment();
        $newComment->group_id = $groupId;
        $newComment->status = $groupModel->status;
        $newComment->priority = $groupModel->priority;
        $newComment->owner_id = $groupModel->owner_id;

        if ($newComment->load(Yii::$app->request->post())) {
            $newComment->save();

            return $this->redirect(['index']);
        }

        return $this->renderAjax('_comments', [
            'groupModel' => $groupModel,
            'newComment' => $newComment,
        ]);
    }

    /**
     * @param $id
     * @return Comment|null
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Comment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
