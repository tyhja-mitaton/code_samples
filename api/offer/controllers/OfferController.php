<?php

namespace core\modules\api\offer\controllers;


use common\models\structure\offer\Offer;

class OfferController extends \yii\rest\Controller
{
    /**
     * @return array
     */
    public function actionGetList()
    {
        return Offer::getList();
    }
}
