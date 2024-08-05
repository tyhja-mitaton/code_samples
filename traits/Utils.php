<?php

namespace app\models\proxy\traits;

use app\components\queue\CheckProxy;
use app\models\proxy\enums\ProxyStatus;
use app\models\proxy\enums\ProxyType;
use yii\httpclient\Client;
use Yii;
use app\components\LinkBuilder;
use yii\queue\Queue;

trait Utils
{
    public static function check($id = null)
    {
        if(empty($id)) {
            $ids = self::find()->select('id')->column();
            foreach ($ids as $id) {
                self::addTaskToQueue(new CheckProxy([
                    'id' => $id
                ]));
            }
        } else {
            self::addTaskToQueue(new CheckProxy([
                'id' => $id
            ]));
        }
    }

    public function getCurrentStatus()
    {
        return ProxyStatus::array()[$this->status];
    }

    public function requestData(Client $client)
    {
        $urlParts = parse_url(Yii::$app->params['proxyTestURL']);

        $client = new \GuzzleHttp\Client([
            'base_uri'    => (isset($urlParts['scheme']) ? $urlParts['scheme'] : 'http') . '://' . $urlParts['host'],
            'timeout'     => 60,
            'http_errors' => false,
            'verify' => false,
        ]);
        $params = [
            'allow_redirects' => true,
            'headers'         => [
                'User-Agent' => \Yii::$app->name,
            ],
            'proxy' => LinkBuilder::buildUrl([
                'scheme' => ProxyType::array()[$this->type],
                'user' => $this->username,
                'pass' => $this->password,
                'host' => $this->host,
                'port' => $this->port,
            ]),
        ];
        $response = $client->get(Yii::$app->params['proxyTestURL'], $params);
        if ($response->getStatusCode() == 200) {
            $this->changeStatus(ProxyStatus::Success);
            echo $this->host, "\r\n";
        } else {
            echo 'FAIL', "\r\n";
            $this->changeStatus(ProxyStatus::Fail);
        }
    }

    public function changeStatus(ProxyStatus $status): void
    {
        $this->updateAttributes(['status' => $status->value]);
    }

    public static function addTaskToQueue($job): void
    {
        Yii::$app->queue->push($job);
    }
}
