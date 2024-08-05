<?php

namespace core\modules\api\currency\controllers;

use common\models\currency\Currency;
use common\models\geo\country\Country;
use yii\helpers\ArrayHelper;

class CurrencyController extends \yii\rest\Controller
{
    /**
     * @return array
     */
    public function actionGetByCountryId()
    {
        $post = \Yii::$app->request->post();
        if (!empty($post['countries']) && !in_array('all', $post['countries'])) {
            return Currency::getListByCountries($post['countries']);
        }
        return Currency::getList();
    }

    /**
     * @return array
     */
    public function actionGetList()
    {
        return Currency::getList();
    }

    /**
     * @return array ['country_id' => ['currency_id' => 'currency_iso', ...], ...];
     */
    public function actionGetGroupByCountryId()
    {
        $query = Country::find()->select([
            Country::tableName() . '.id as country_id',
            Currency::tableName() . '.id as currency_id',
            Currency::tableName() . '.iso'
        ]);
        if ($post = \Yii::$app->request->post('countries')) {
            $query->where(['in', Country::tableName() . '.id', $post]);
        }
        $countries = $query->joinWith('currencies')->asArray()->all();
        return ArrayHelper::map($countries, 'currency_id', 'iso', 'country_id');
    }

    /**
     * @return array
     */
    public function actionGetRate()
    {
        $countries = Currency::find()->all();
        return ArrayHelper::map($countries, 'id', 'rate');
    }
}
