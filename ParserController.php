<?php

namespace app\commands\application;

use app\components\bernard\Bernard;
use app\components\bernard\Queue;
use app\models\application\Application;
use yii\console\Controller;

class ParserController extends Controller
{
    public function actionStore($id = null)
    {
        if (empty($id)) {
            $applications = Application::find()
                ->andWhere(['IN', 'status', Application::statusesForParsing()])
                ->andWhere(['<=', 'next_time_parse', time()])
                ->orderBy(['next_time_parse' => SORT_ASC])
                ->all();

            //shuffle($applications);

            foreach ($applications as $application) {
                /** @var $application Application */
                echo $application->id . date("Y-m-d H:i:s", $application->next_time_parse) . "\r\n";
                Bernard::produce(Queue::QUEUE_APPLICATION_PARSE, [
                    'id' => $application->id,
                ]);
            }
        } else {
            Bernard::produce(Queue::QUEUE_APPLICATION_PARSE, [
                'id' => $id,
            ]);
        }
    }

    public function actionParse($id, $isForce = false)
    {
        $application = Application::findOne($id);
        if (!empty($application)) {
            $application->parse($isForce);
        }
    }

    public function actionDebug()
    {
        $applications = Application::find()
            ->andWhere(['IN', 'status', Application::statusesForParsing()])
            ->andWhere(['<=', 'next_time_parse', time()])
            ->orderBy(['next_time_parse' => SORT_ASC])
            ->all();
        echo count($applications ). "\r\n";
        foreach ($applications as $application) {
            /** @var $application Application */
            echo $application->id . " " . date("Y-m-d H:i:s", $application->next_time_parse) . "\r\n";
        }
    }
}
