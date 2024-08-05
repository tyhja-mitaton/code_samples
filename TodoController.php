<?php


namespace app\modules\structure\controllers;


use app\models\search\Todo as TodoSearch;
use app\models\Todo;
use app\models\user\UserPermission;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

class TodoController extends \yii\web\Controller
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
                        'actions' => ['rename', 'create', 'check', 'delete', 'resort', 'index'],
                        'allow' => true,
                        'roles' => [UserPermission::PERMISSION_APPLICATIONS]
                    ]
                ]
            ]
        ];
    }

    /**
     * Lists all models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TodoSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionRename()
    {
        $id = \Yii::$app->request->post('id');
        $name = \Yii::$app->request->post('name');
        $todo = $this->findModel($id);
        $todo->name = $name;
        $todo->save(false);
    }

    public function actionCreate()
    {
        $appId = \Yii::$app->request->post('id');
        $name = \Yii::$app->request->post('name');
        $todo = new Todo();
        $todo->application_id = $appId;
        $todo->name = $name;
        $priority = Todo::find()->where(['application_id' => $appId])->count();
        $todo->priority = $priority;
        if($todo->save(false)) {
            $this->redirect(Url::to(['application/view', 'id' => $appId]));
        }
    }

    public function actionCheck()
    {
        $id = \Yii::$app->request->post('id');
        $isChecked = \Yii::$app->request->post('isChecked');
        $todo = $this->findModel($id);
        $todo->is_done = $isChecked;
        $todo->save(false);
    }

    public function actionDelete()
    {
        $id = \Yii::$app->request->post('id');
        $appId = \Yii::$app->request->post('appId');
        $todo = $this->findModel($id);
        if($todo->delete() !== false) {
            $this->redirect(Url::to(['application/view', 'id' => $appId]));
        }
    }

    public function actionResort()
    {
        $id = \Yii::$app->request->post('id');
        $appId = \Yii::$app->request->post('appId');
        $priority = \Yii::$app->request->post('priority');
        $todo = $this->findModel($id);
        if ($priority > $todo->priority) {
            Todo::updateAllCounters(
                ['priority' => -1],
                ['and', ['application_id' => $appId], ['>=', 'priority', $todo->priority], ['<=', 'priority', $priority]]
            );
        } elseif ($priority < $todo->priority) {
            Todo::updateAllCounters(
                ['priority' => 1],
                ['and', ['application_id' => $appId], ['<=', 'priority', $todo->priority], ['>=', 'priority', $priority]]
            );
        }
        if($todo->priority != $priority) {
            $todo->updateAttributes(['priority' => $priority]);
        }
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Todo
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Todo::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}