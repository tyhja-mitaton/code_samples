<?php

namespace core\modules\api\naming\controllers;

use common\models\structure\naming\forms\NamingForm;

class NamingController extends \yii\rest\Controller
{
    public function actionIndex()
    {
        $model = new NamingForm();

        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return ['success' => $model];
        }

        if ($model->getErrors()) {;
            return ['errors' => $model->getErrors()];
        }

        return $model;
    }
}
