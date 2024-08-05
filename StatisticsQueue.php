<?php

namespace app\components\queue;

use app\models\settings\LastFullDayStatisticsSettings;
use app\models\statistics\Statistics;
use yii\helpers\ArrayHelper;

class StatisticsQueue extends \app\components\QueueAbstract
{
    /**
     * @var string Queue name
     */
    public $queueName = "statistics";

    /** Number of execution attempts */
    const MAX_ATTEMPTS = 1;

    const BASE_URL = 'https://ads.youniversalnext.com/v3/';

    public string $query;
    public string $queryTq;
    public string $date;

    public function run($queue)
    {
        $response = file_get_contents(self::BASE_URL . "?{$this->query}");
        $responseTq = file_get_contents(self::BASE_URL . "?{$this->queryTq}");
        if ($response !== false) {
            $tqStatByMarket = null;
            if ($responseTq !== false) {
                $xmlTq = simplexml_load_string($responseTq);
                if(!empty($xmlTq->xpath('//Result'))) {
                    $resultsTq = $xmlTq->xpath('//Result');
                    array_walk($resultsTq, function (&$item) {
                        $item = (array)$item;
                    });
                    $tqStatByMarket = ArrayHelper::map($resultsTq, 'device_type', 'tq', 'market');
                }
            }
            $xml = simplexml_load_string($response);
            if (empty($xml->xpath('//Result'))) {
                $emptyStatistics = new \SimpleXMLElement("<Result><date>{$this->date}</date></Result>");
                Statistics::create($emptyStatistics, null);
                $lastFullDaySettings = new LastFullDayStatisticsSettings();
                $lastFullDaySettings->last_full_day = $this->date;
                $lastFullDaySettings->save(false);
                return;
            }
            foreach ($xml->xpath('//Result') as $result) {
                $resultSimple = $result;
                $tq = isset($tqStatByMarket) ?
                    (key_exists((string)$resultSimple->market, $tqStatByMarket) && key_exists((string)$resultSimple->device_type, $tqStatByMarket[(string)$resultSimple->market]) ?
                        $tqStatByMarket[(string)$resultSimple->market][(string)$resultSimple->device_type] : null) : null;
                Statistics::create($result, $tq);
            }
            $lastFullDaySettings = new LastFullDayStatisticsSettings();
            $lastFullDaySettings->last_full_day = $this->date;
            $lastFullDaySettings->save(false);
        } else {
            echo 'Error';
        }
    }
}