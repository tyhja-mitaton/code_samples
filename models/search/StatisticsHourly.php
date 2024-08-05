<?php

namespace app\models\search;

use app\models\statistics\StatisticsHourly as StatisticsHourlyModel;
use brntsrs\ClickHouse\ActiveQuery;
use cheatsheet\Time;

class StatisticsHourly extends StatisticsHourlyModel
{
    public $actual_revenue;
    public $pub_revenue;
    public $rpm;
    public $epc;
    public $ctr;
    public $coverage;

    public $dateRange;
    public $dateStart;
    public $dateEnd;

    public $group_by = 'date';

    public function rules()
    {
        return [
            [['event_date', 'unixtimestamp', 'market', 'date', 'source_tag', 'device_type', 'keyword', 'sub_id', 'currency', 'search_type_tq', 'site_id', 'searches', 'bidded_searches', 'bidded_results', 'bidded_clicks', 'revenue', 'dateRange', 'group_by', 'tq'], 'safe'],
            [['pub_revenue', 'actual_revenue', 'rpm', 'epc', 'ctr', 'coverage'], 'number'],
            [['hour'], 'integer'],
            [['dateRange'], 'match', 'pattern' => '/^.+\s\-\s.+$/'],
        ];
    }

    /**
     * @param array $params
     *
     */
    public function search($params)
    {
        /**
         * @var ActiveQuery $query
         */
        $query = \Yii::createObject(ActiveQuery::class, [StatisticsHourly::class]);

        $this->load($params);

        if(!empty($this->group_by)) {
            $groupBy = match ($this->group_by) {
                'date' => 'date, hour',
                'geo' => 'market',
                'sub_id' => 'sub_id',
                'source_tag' => 'source_tag',
                'device' => 'device_type',
                default => null,
            };
            if(!empty($groupBy)) {
                $fields = [
                    'min(unixtimestamp) as unixtimestamp',
                    'sum(searches) as searches',
                    'sum(bidded_searches) as bidded_searches',
                    'sum(bidded_results) as bidded_results',
                    'sum(bidded_clicks) as bidded_clicks',
                    'sum(revenue) as revenue',
                    'min(tq) as tq',
                    'min(search_type_tq) as search_type_tq',
                ];
                $groupFields = [
                    'date, hour' => ['date', 'hour', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'market' => ['market', 'min(date) as date', 'min(hour) as hour', "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'sub_id' => ['min(date) as date', 'min(hour) as hour', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", 'sub_id', "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'source_tag' => ['min(date) as date', 'min(hour) as hour', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'device_type' => ['min(date) as date', 'min(hour) as hour', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                ];
                $query->select(array_merge($fields, $groupFields[$groupBy]))
                    ->groupBy($groupBy)->orderBy(['hour' => SORT_ASC]);
            }
        }

        return $query->all();
    }

    public static function getTodaySummaryQuery($prevDayExists = true): ActiveQuery
    {
        /**
         * @var ActiveQuery $query
         */
        $query = \Yii::createObject(ActiveQuery::class, [StatisticsHourly::class]);
        $selectFields = [
            'max(unixtimestamp) as unixtimestamp',
            'sum(searches) as searches',
            'sum(bidded_searches) as bidded_searches',
            'sum(bidded_results) as bidded_results',
            'sum(bidded_clicks) as bidded_clicks',
            'sum(revenue) as revenue',
            'min(tq) as tq',
            'min(search_type_tq) as search_type_tq',
            'date',
            "arrayStringConcat(groupUniqArray(20)(market), ', ') as market",
            "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id",
            "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword",
        ];

        $query->select($selectFields)->where(['date' => date('Y-m-d', time())]);
        if(!$prevDayExists) {
            $query->orWhere(['date' => date('Y-m-d', time() - Time::SECONDS_IN_A_DAY)]);
        }
        return $query->groupBy('date')->addOrderBy(['date' => SORT_DESC]);
    }
}