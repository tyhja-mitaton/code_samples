<?php

namespace app\models\statistics;

use Yii;

/**
 * This is the model class for table "statistics_by_hour".
 *
 * @property string $market
 * @property string $date
 * @property int $hour
 * @property string $source_tag
 * @property int $site_id
 * @property string $device_type
 * @property string $keyword
 * @property string $sub_id
 * @property int $searches
 * @property int $bidded_searches
 * @property int $bidded_results
 * @property int $bidded_clicks
 * @property float $revenue
 * @property string $currency
 * @property string $search_type_tq
 * @property float $tq
 *
 * @property string $event_date [Date]
 * @property string $unixtimestamp [DateTime]
 */
class StatisticsHourly extends \brntsrs\ClickHouse\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistics_by_hour';
    }

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('clickhouse');
    }

    /**
     * @inheritDoc
     */
    public static function primaryKey()
    {
        return [
            'unixtimestamp',
        ];
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        if (empty($this->unixtimestamp)) {
            $this->unixtimestamp = time();
        }
        if (empty($this->event_date)) {
            $this->event_date = date('Y-m-d');
        }

        return parent::beforeSave($insert);
    }

    public static function create(\SimpleXMLElement $statistic)
    {
        $statisticLine = new self([
            'market' => $statistic->market ?? '',//$statistic['market']
            'date' => $statistic->date ?? '1970-01-01',//$statistic['date']
            'hour' => $statistic->hour ?? 0,//$statistic['hour']
            'source_tag' => $statistic->source_tag ?? '',//$statistic['source_tag']
            'site_id' => $statistic->site_id ?? 0,//$statistic['site_id']
            'device_type' => $statistic->device_type ?? '',//$statistic['device_type']
            'keyword' => $statistic->keyword ?? '',//$statistic['keyword']
            'sub_id' => $statistic->sub_id ?? '',//$statistic['sub_id']
            'searches' => $statistic->searches ?? 0,//$statistic['searches']
            'bidded_searches' => $statistic->bidded_searches ?? 0,//$statistic['bidded_searches']
            'bidded_results' => $statistic->bidded_results ?? 0,//$statistic['bidded_results']
            'bidded_clicks' => $statistic->bidded_clicks ?? 0,//$statistic['bidded_clicks']
            'revenue' => $statistic->revenue ?? 0,//$statistic['revenue']
            'currency' => $statistic->currency ?? '',//$statistic['currency']
            'search_type_tq' => $statistic->search_type_tq ?? '',//$statistic['search_type_tq']
            'tq' => isset($statistic->tq) ? (float)$statistic->tq : 0,//$statistic['tq']
        ]);
        $statisticLine->save(false);
    }
}