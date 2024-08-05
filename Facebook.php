<?php

namespace app\components;

use app\models\account\Account;
use app\models\comment\Comment;
use app\models\proxy\enums\ProxyType;
use app\models\proxy\Proxy;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class Facebook
{
    /** @var string  */
    const API_VERSION = "v17.0";

    /** @var Client|null  */
    public static ?Client $client = null;

    /** @var Account  */
    public Account $account;

    /**
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * @return Client|null
     */
    public function getClient()
    {
        if (self::$client === null) {
            if (!empty($this->account->proxy_list)) {
                $proxy = Proxy::find()->andWhere(['id' => $this->account->proxy_list])->orderBy('RAND()')->one();
                if (!empty($proxy)) {
                    /** @var Proxy $proxy */
                    $proxyDomain = ProxyType::tryFrom($proxy->type)->label() . "://" . $proxy->username . ":" . $proxy->password . "@" . $proxy->host . ":" . $proxy->port;

                    return new Client([
                        'base_uri' => self::getApiUrl(),
                        RequestOptions::PROXY => $proxyDomain
                    ]);
                }
            }
            return new Client([
                'base_uri' => self::getApiUrl()
            ]);
        }

        return self::$client;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccountInfo()
    {
        $client = self::getClient();

        $params = [
            'fields' => 'id,first_name,last_name,gender,accounts',
            'access_token' => $this->account->access_token
        ];

        try {
            $response = $client->request('GET', self::API_VERSION . '/me?' . http_build_query($params));
        } catch (\Exception $exception) {
            return $this->getErrorResponse($exception);
        }

        return $this->getResponse($response);
    }

    /**
     * @param $pageAccessToken
     * @param $postId
     * @param Comment $comment
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendComment($pageAccessToken, $postId, Comment $comment)
    {
        $client = self::getClient();
        $params = [
            'message' => $comment->text,
            'access_token' => $pageAccessToken
        ];

        $mediaFIles = [];
        if (!empty($comment->original_mediafile_name)) {
            $mediaFIles['multipart'] = [
                [
                    'name'     => 'source',
                    'contents' => fopen(\Yii::getAlias('@app/web') . '/' . $comment->original_mediafile_path, 'r'),
                    'filename' => basename($comment->original_mediafile_path),
                ]
            ];
        }

        $query = http_build_query($params);
        try {
            $response = $client->request('POST', self::API_VERSION . "/{$postId}/comments?{$query}", $mediaFIles);
        } catch (\Exception $exception) {
            return $this->getErrorResponse($exception);
        }

        return $this->getResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    private function getResponse(ResponseInterface $response)
    {
        return [
            'statusCode' => $response->getStatusCode(),
            'data' => $response->getBody()->getContents(),
        ];
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    private function getErrorResponse(\Exception $exception)
    {
        if ($content = json_decode($exception->getResponse()->getBody()->getContents())) {
            if (isset($content->error->message)) {
                return [
                    'statusCode' => $exception->getResponse()->getStatusCode(),
                    'data' => $content->error->message,
                ];
            }
        }
        return ['statusCode' => 500, 'data' => $exception->getMessage()];
    }

    /**
     * @return mixed
     */
    private static function getApiUrl()
    {
        return "https://graph.facebook.com";
    }

    /**
     * @todo method not implemented
     */
    public function removeAccountInfo(){}
}
