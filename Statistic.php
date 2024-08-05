<?php

namespace app\models\statistic\creative;

use app\models\currency\Currency;
use app\models\search\Counteragent;
use app\models\search\PlatformSetting;
use app\models\statistic\creative\traits\Finders;
use app\models\statistic\creative\traits\Relations;
use app\models\structure\creative\Creative;
use app\models\structure\currency\CurrencyRate;
use brntsrs\ClickHouse\ActiveRecord;
use Yii;

/**
 * Creative statistic class
 * @property int $unixtimestamp
 * @property string $event_date
 * @property string $statistic_date
 * @property string $creative_name
 * @property string $creative_external_id
 * @property int $creative_id
 * @property int $platform_internal_id
 * @property int $statistic_impressions
 * @property int $statistic_clicks
 * @property int $statistic_installs
 * @property int $statistic_spend_currency_id
 * @property float $statistic_spend
 * @property string $statistic_full_json
 *
 * @property Currency $currency
 * @property Creative $creative
 * @property PlatformSetting $platformSetting
 *
 */
class Statistic extends ActiveRecord
{
    use Relations, Finders;

    /**
     * @return string[]
     */
    public static function primaryKey()
    {
        return [
            'unixtimestamp',
            'creative_name',
            'creative_external_id',
            'creative_id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic';
    }

    /**
     * @return \kak\clickhouse\Connection the ClickHouse connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('clickhouse');
    }

    /**
     * @param $insert
     * @return bool
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

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'statistic_date' => 'Дата статистики',
            'creative_name' => 'Название креатива',
            'creative_external_id' => 'Внешний ID креатива',
            'platform_internal_id' => 'Площадка',
            'statistic_impressions' => 'Кол-во показов',
            'statistic_clicks' => 'Кол-во кликов',
            'statistic_installs' => 'Кол-во установок',
            'statistic_spend_currency_id' => 'Валюта потраченных денег',
            'statistic_spend' => 'Кол-во потраченных денег (в валюте)',
            'statistic_spend_rub' => 'Кол-во потраченных денег (в рублях)',
        ];
    }

    public static function create(
        string $statisticDate,
        string $creativeExternalId,
        string $creativeName,
        int    $platformInternalId,
        int    $creative_id,
        int    $statisticImpressions,
        int    $statisticClicks,
        int    $statisticInstalls,
        int    $statisticSpendCurrencyId,
        float  $statisticSpend,
        string $statisticFullJson,
    )
    {
        $creativeStatistic = new self();
        $creativeStatistic->statistic_date = $statisticDate;
        $creativeStatistic->creative_external_id = $creativeExternalId;
        $creativeStatistic->creative_name = $creativeName;
        $creativeStatistic->platform_internal_id = $platformInternalId;
        $creativeStatistic->creative_id = $creative_id;
        $creativeStatistic->statistic_impressions = $statisticImpressions;
        $creativeStatistic->statistic_clicks = $statisticClicks;
        $creativeStatistic->statistic_installs = $statisticInstalls;
        $creativeStatistic->statistic_spend_currency_id = $statisticSpendCurrencyId;
        $creativeStatistic->statistic_spend = $statisticSpend;
        $creativeStatistic->statistic_full_json = $statisticFullJson;

        return $creativeStatistic;
    }

    public function getStatisticSpendRub()
    {
        $userId = $this->creative?->reportingPerson?->owner->id;
        $date = (date("Y-m", strtotime($this->statistic_date)));

        $rate = (int)CurrencyRate::getRate($userId, $date)?->rate_usd_rub;

        $currencyTo = Currency::getCurrencyByIso('RUB');

        return Currency::convert($this->currency, $currencyTo, $this->statistic_spend, $rate);
    }

}
