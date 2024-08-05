<?php


namespace app\modules\api\controllers;


use app\modules\api\forms\DeeplinkGenerator;

class NamingController extends \app\components\ApiController
{
    public function accessRules()
    {
        return [
            [
                'actions' => ['generate'],
                'allow' => true,
                'roles' => ['@'],
            ],
        ];
    }

    public function verbs()
    {
        return [
            'deeplink' => ['POST'],
        ];
    }

    /**
     * @api {post} /api/naming/generate Generate
     * @apiVersion 1.0.0
     * @apiName naming_generate
     * @apiGroup AC. Generator
     * @apiDescription Generates naming
     *
     * @apiHeader {String} Authorization Bearer token
     *
     * @apiParam {string} name Campaign name
     * @apiParam {string} url URL
     * @apiParam {integer} application_id Application ID
     *
     * @apiSuccess {boolean=true} success Status of request response
     * @apiSuccess {Data[]} data
     * @apiSuccess {string} data.naming Naming
     *
     * @apiError {boolean=false} success Status of request response
     * @apiError {Error[]} error
     * @apiError {string} error.name Error name
     * @apiError {string} error.message Error detail message
     * @apiError {integer} error.status Error status code
     * @apiError {Details[]} error.details Error detail list of information
     * @apiError {string} error.details.field Request param related to error
     * @apiError {string} error.details.message Error message related to request param
     *
     * @apiSuccessExample {json} Success-Response:
     * HTTP/1.1 200
     *   {
     *       "success": true,
     *       "data": {
     *           "naming": "==QbvNmLlx2Zv92Z"
     *       }
     *   }
     *
     * @apiErrorExample {json} Error-Response:
     * HTTP/1.1 200
     *   {
     *       "success": false,
     *       "error": {
     *           "name": "Forbidden",
     *           "message": "Access denied",
     *           "code": 0,
     *           "status": 403,
     *           "type": "yii\\web\\ForbiddenHttpException"
     *       }
     *  }
     *
     * @apiErrorExample {json} Error-Response:
     * HTTP/1.1 200
     *   {
     *       "success": false,
     *       "error": {
     *           "name": "Forbidden",
     *           "message": "Login Required",
     *           "code": 0,
     *           "status": 403,
     *           "type": "yii\\web\\ForbiddenHttpException"
     *       }
     *  }
     */
    public function actionGenerate()
    {
        $generatorForm = new DeeplinkGenerator(\Yii::$app->user->identity);
        $generatorForm->load(\Yii::$app->request->post(), '');

        return ['naming' => $generatorForm->generate()];
    }

}
