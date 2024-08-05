<?php

namespace core\modules\api\country\controllers;

use common\models\geo\country\Country;
use common\models\structure\offer\Offer;
use yii\helpers\ArrayHelper;

class CountryController extends \yii\rest\Controller
{
    /**
     * @return array
     */
    public function actionGetList()
    {
        return Country::getList();
    }

    /**
     * @return array
     */
    public function actionGetListByOfferId($offerId)
    {
        $offer = Offer::find()
            ->joinWith('countries')
            ->joinWith('bids')
            ->where([Offer::tableName() . '.id' => $offerId])->one();

        $country = [];
        $country = array_merge($country, $offer->countries);
        foreach ($offer->bids as $bid) {
            $country = array_merge($country, $bid->country);
        }

        $list = ArrayHelper::map($country, 'id', 'iso');
        if (in_array('all', $country)) {
            $list = ["all" => '[WW] All countries'] + $list;
        }
        return $list;
    }
}
