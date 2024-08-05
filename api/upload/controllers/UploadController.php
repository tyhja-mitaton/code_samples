<?php

namespace core\modules\resources\controllers;

use common\models\Upload;
use yii\web\Response;

/**
 * UploadController implements the uploading file actions
 */
class UploadController extends \yii\rest\Controller
{
    /**
     * @return array
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON
        ];
        return $behaviors;
    }

    /**
     * @return string[][]
     */
    protected function verbs(): array
    {
        return [
            'upload' => ['post'],
        ];
    }

    /**
     * @return array|int[]
     */
    public function actionUpload(): array
    {
        $upload = new Upload();
        return $upload->uploadChunk(\Yii::$app->request->post());
    }
}