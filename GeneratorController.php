<?php


namespace app\modules\structure\controllers;


use app\components\DeeplinkDecoder;
use app\components\DeeplinkGenerator;
use app\models\StrictLink;
use app\models\user\UserPermission;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class GeneratorController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'go'],
                        'allow' => true,
                        'roles' => [UserPermission::PERMISSION_NAMING],
                    ],
                ]
            ],
        ];
    }


    public function actionIndex()
    {
        $generator = new DeeplinkGenerator(['user' => \Yii::$app->user->identity]);
        $parser = new DeeplinkDecoder(['user' => \Yii::$app->user->identity]);
        $action = \Yii::$app->request->post('action');
        $result = [];
        if ($generator->load(\Yii::$app->request->post()) && $action != 'parse') {
            $generatedData = $generator->generate();
            if(!empty($generatedData)) {
                $result['generated'] = $generatedData;
                $result['parsed'] = $generator->parse($result['generated']);
                $result['appsflyer'] = $generator->createAppsflyerUrl($result['generated']);
                if($generator->strict_naming) {
                    $generator->url = $result['parsed'];
                }
            } else {
                \Yii::$app->session->setFlash('alert', [
                    'title' => '[cannot generate naming]',
                    'body' => 'Url is out of whitelist'
                ]);
            }
        } elseif ($parser->load(\Yii::$app->request->post()) && $action == 'parse') {
            $result['parsed'] = $parser->parse();
            $generator->url = $result['parsed'];
            $generator->application_id = $parser->application_id;
            $result['generated'] = $generator->generate();
            $result['appsflyer'] = $generator->createAppsflyerUrl($result['generated']);
        }

        return $this->render('index', [
            'generator' => $generator,
            'parser' => $parser,
            'result' => $result,
            'applications' => $generator->getAvailableApplications(),
        ]);
    }

    public function actionGo($id)
    {
        $strictLink = StrictLink::findOne(['link_id' => $id]);
        if(!empty($strictLink)) {
            return $this->redirect($strictLink->redirect_to);
        } else {
            throw new NotFoundHttpException('Target URL not found');
        }
    }

}
