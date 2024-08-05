<?php

namespace core\modules\finance\controllers;

use common\models\currency\Currency;
use common\models\invoice\enums\Type;
use common\models\structure\advertiser\Advertiser;
use common\models\structure\offer\Offer;
use common\models\structure\source\Source;
use core\modules\resources\actions\ResourceUpdatePartialAction;
use Yii;
use common\models\invoice\Invoice;
use common\models\invoice\search\Invoice as InvoiceSearch;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class InvoiceController extends Controller
{
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

    public function actions()
    {
        return [
            'update-info' => [
                'class' => ResourceUpdatePartialAction::class,
                'modelClass' => Invoice::class,
                'modelId' => Yii::$app->request->get('id'),
                'view' => '_info',
                'isForm' => true,
            ],
            'cancel-save-info' => [
                'class' => ResourceUpdatePartialAction::class,
                'modelClass' => Invoice::class,
                'modelId' => Yii::$app->request->get('id'),
                'view' => '_info',
                'isForm' => false,
                'formAttributes' => ['number', 'date_payment', 'period', 'offer_id', 'source_id', 'advertiser_id', 'amount', 'currency_id'],
            ],
            'update-options' => [
                'class' => ResourceUpdatePartialAction::class,
                'modelClass' => Invoice::class,
                'modelId' => Yii::$app->request->get('id'),
                'view' => '_options',
                'isForm' => true,
            ],
            'cancel-save-options' => [
                'class' => ResourceUpdatePartialAction::class,
                'modelClass' => Invoice::class,
                'modelId' => Yii::$app->request->get('id'),
                'view' => '_options',
                'isForm' => false,
                'formAttributes' => ['status', 'comment'],
            ],
        ];
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceSearch();
        $type = Type::fromUrl(Yii::$app->request->get('type'));
        $params = ArrayHelper::merge(Yii::$app->request->queryParams, [$searchModel->formName() => ['type' => $type->value]]);
        $dataProvider = $searchModel->searchWithText($params);

        return $this->render('index', compact('searchModel', 'dataProvider', 'type'));
    }

    /**
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Invoice();
        $model->type = Type::fromUrl(Yii::$app->request->get('type'))->value;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'offerList' => Offer::getList(),
            'advertiserList' => Advertiser::getList(),
            'sourceList' => Source::getList(),
            'currencyList' => Currency::getList(),
        ]);
    }

    /**
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'offerList' => Offer::getList(),
            'advertiserList' => Advertiser::getList(),
            'sourceList' => Source::getList(),
            'currencyList' => Currency::getList(),
        ]);
    }

    /**
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $type = Type::tryFrom($model->type);
        $model->delete();

        return $this->redirect(['/finance/' . $type->toUrl() . '-invoice/index']);
    }

    /**
     * @param int $id ID
     * @return Invoice
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Invoice::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
    }
}
