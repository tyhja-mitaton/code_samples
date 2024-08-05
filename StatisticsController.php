<?php

namespace app\modules\statistics\controllers;

use app\models\geo\Country;
use app\models\search\Statistics;
use app\models\search\StatisticsHourly;
use cheatsheet\Time;
use Yii;

class StatisticsController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $searchModel = new Statistics([
            'dateRange' => date('Y-m-d', time() - Time::SECONDS_IN_A_DAY * 5) .
                ' - ' . date('Y-m-d', time())
        ]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $queryParams = isset(Yii::$app->request->queryParams[$searchModel->formName()]) ? Yii::$app->request->queryParams[$searchModel->formName()] : [];
        $searchModelHourly = new StatisticsHourly();
        $statisticsHourly = $searchModel->group_by == 'date' ? $searchModelHourly->search([$searchModelHourly->formName() => $queryParams]) : [];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'countryList' => Country::getList(true, false),
            'subIdList' => Statistics::getSubIdList(),
            'keywordList' => Statistics::getKeywordList(),
            'statisticsHourlyBatch' => $statisticsHourly,
        ]);
    }

}