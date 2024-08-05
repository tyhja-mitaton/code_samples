<?php
namespace app\commands\consumers;


use app\components\Asana;
use app\models\application\Application;
use app\models\release\Release;
use app\models\release\ReleaseLine;
use app\models\structure\PublishingAccount;
use app\models\user\User;
use yii\helpers\Html;
use yii\helpers\Url;

class ReleaseConsumer extends AbstractConsumer
{
    public function process($data)
    {
        $applications = $this->getApplications($data['text']);
        $comment = $this->prepareComment($data['text'], $applications);

        $this->setDonorAccounts($comment, $applications);

        $this->saveRelease($data['pinned_message_id'], $comment, $applications);
    }

    private function getApplications($text)
    {
        preg_match_all('/(https:\/\/app.asana.com\/0\/[\d]+\/([\d]+))/', $text, $match);
        $taskIds = $match[2];
        $applications = Application::findAll(['asana_gid' => $taskIds]);
        foreach ($applications as $application) {
            $index = array_search($application->asana_gid, $taskIds);
            unset($taskIds[$index]);
        }

        if (!empty($taskIds)) {
            $olimobUser = User::findOne(['alias' => \Yii::$app->params['olimob']['alias']]);
            if (!empty($olimobUser)) {
                foreach ($taskIds as $id) {
                    $result = (new Asana($olimobUser))->loadOneTask($id);
                    if ($result instanceof Application) {
                        $applications[] = $result;
                    }
                }
            }
        }

        return $applications;
    }

    private function setDonorAccounts($text, $applications)
    {
        $lines = explode("\r", $text);
        /**
         * @var Application[] $applications
         */
        foreach ($applications as $application) {
            $line = $this->searchTextLine($lines, $application);
            $application->donor_account_id = $this->detectAccount($line);
            $application->save(false);
        }
    }

    private function searchTextLine($lines, Application $application)
    {
        foreach ($lines as $line) {
            if (strpos($line, $application->asana_gid) !== false) {
                return $line;
            }
        }
        return false;
    }

    private function detectAccount($line)
    {
        $patterns = [
            'на акк в ',
            'на акк ',
            'на консоль ',
            'на наш корп' ,
            'на корп ',
            'на аккаунт ',
        ];
        $name = null;
        foreach ($patterns as $pattern) {
            if (strpos($line, $pattern) !== false) {
                $line = explode($pattern, $line);
                $name = $line[1];
                break;
            }
        }

        if (!empty($name)) {
            return PublishingAccount::getByName($name)->id;
        }
        return null;
    }

    private function prepareComment($text, $applications)
    {
        $text = preg_replace('/(https:\/\/app.asana.com\/0\/[\d]+\/[\d]+)/', Html::a('📋 asana', '$1', ['target' => '_blank']), $text);
        foreach ($applications as $application) {
            $text = str_replace($application->name, Html::a('#' . $application->id . ' ' . $application->name, Url::to(['/structure/application/view', 'id' => $application->id]), ['target' => '_blank']), $text);
        }

        return $text;
    }

    private function saveRelease($id, $text, $applications)
    {
        if (!empty($applications)) {
            $release = Release::findOne(['message_id' => $id]);
            if (empty($release)) {
                $release = Release::create(time(), $text, $id);
            } else {
                $release->comments = $text;
                $release->save(false);
                ReleaseLine::deleteAll(['release_id' => $release->id]);
            }

            foreach ($applications as $application) {
                ReleaseLine::create($release->id, $application->id);
            }
        }
    }
}
