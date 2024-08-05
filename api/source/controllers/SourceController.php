<?php

namespace core\modules\api\source\controllers;

use common\models\structure\source\Source;
use yii\helpers\ArrayHelper;

class SourceController extends \yii\rest\Controller
{
    /**
     * @return array
     */
    public function actionGetList()
    {
        return Source::getList();
    }

    /**
     * @return array
     */
    public function actionGetListByOfferId($offerId)
    {
        $res = Source::find()->joinWith('offer')->where(['offer_id' => $offerId])->all();
        return ArrayHelper::map($res, 'id', 'name');
    }
}
