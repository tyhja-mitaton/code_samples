<?php

namespace app\components;

use GuzzleHttp\Client;
use Yii;


class Telegram
{
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function notificate()
    {
        $url = $this->buildUrl();

        $client = new Client([
            'base_uri' => 'https://api.telegram.org',
            'timeout' => 30,
            'http_errors' => false,
        ]);

        $params = [
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => Yii::$app->name,
            ],
        ];

        $client->get($url, $params);
    }

    private function buildUrl()
    {
        return 'https://api.telegram.org/bot' . Yii::$app->params['telegram']['token'] . '/sendMessage?chat_id=' . Yii::$app->params['telegram']['chat_id'] . '&parse_mode=markdown&text=' . urlencode($this->message);
    }
}
