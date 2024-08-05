<?php

namespace app\models\statistics;

use Yii;

/**
 * This is the model class for table "user_action_log".
 *
 * @property array|string $market
 * @property string $date
 * @property string $source_tag
 * @property int $site_id
 * @property string $device_type
 * @property string $keyword
 * @property array|string $sub_id
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
class Statistics extends \brntsrs\ClickHouse\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ch_table_statistics';
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

    public static function create(\SimpleXMLElement $statistic, $tq)
    {
        $statisticLine = new self([
            'market' => $statistic->market ?? '',
            'date' => $statistic->date ?? '1970-01-01',
            'source_tag' => $statistic->source_tag ?? '',
            'site_id' => $statistic->site_id ?? 0,
            'device_type' => $statistic->device_type ?? '',
            'keyword' => $statistic->keyword ?? '',
            'sub_id' => $statistic->sub_id ?? '',
            'searches' => $statistic->searches ?? 0,
            'bidded_searches' => $statistic->bidded_searches ?? 0,
            'bidded_results' => $statistic->bidded_results ?? 0,
            'bidded_clicks' => $statistic->bidded_clicks ?? 0,
            'revenue' => $statistic->revenue ?? 0,
            'currency' => $statistic->currency ?? '',
            'search_type_tq' => $statistic->search_type_tq ?? '',
            'tq' => isset($tq) ? (float)$tq : 0,
        ]);
        $statisticLine->save(false);
    }

    public static function getSubIdList(): array
    {
        return self::find()->select('sub_id')->indexBy('sub_id')->column();
    }

    public static function getKeywordList(): array
    {
        return self::find()->select('keyword')->indexBy('keyword')->column();
    }
}
