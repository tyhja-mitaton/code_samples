<?php

namespace app\models\search;

use app\models\settings\LastFullDayStatisticsSettings;
use app\models\user\User;
use brntsrs\ClickHouse\ActiveQuery;
use cheatsheet\Time;
use kak\clickhouse\Expression;
use kartik\daterange\DateRangeBehavior;
use yii\data\ActiveDataProvider;
use app\models\statistics\Statistics as StatisticsModel;
use Yii;

class Statistics extends StatisticsModel
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
            [['dateRange'], 'match', 'pattern' => '/^.+\s\-\s.+$/'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => DateRangeBehavior::class,
                'attribute' => 'dateRange',
                'dateStartAttribute' => 'dateStart',
                'dateEndAttribute' => 'dateEnd',
            ]
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        /**
         * @var ActiveQuery $query
         */
        $query = Yii::createObject(ActiveQuery::class, [Statistics::class]);

        $dataProvider = new ActiveDataProvider([
            'db' => Yii::$app->clickhouse,
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort' => [
                'defaultOrder' => [
                    'date' => SORT_DESC
                ],
                'attributes' => [
                    'unixtimestamp',
                    'market',
                    'date',
                    'sub_id',
                    'searches',
                    'bidded_searches',
                    'bidded_results',
                    'bidded_clicks',
                    'tq',
                    'revenue',
                    'keyword',
                    'search_type_tq',
                    'actual_revenue' => [
                        'asc' => ['revenue' => SORT_ASC],
                        'desc' => ['revenue' => SORT_DESC],
                    ],
                    'pub_revenue' => [
                        'asc' => ['revenue' => SORT_ASC],
                        'desc' => ['revenue' => SORT_DESC],
                    ],
                    'rpm' => [
                        'asc' => ['(revenue * 0.85 / searches) * 1000' => SORT_ASC],
                        'desc' => ['(revenue * 0.85 / searches) * 1000' => SORT_DESC],
                    ],
                    'epc' => [
                        'asc' => ['revenue * 0.85 / bidded_clicks' => SORT_ASC],
                        'desc' => ['revenue * 0.85 / bidded_clicks' => SORT_DESC],
                    ],
                    'ctr' => [
                        'asc' => ['(bidded_clicks / bidded_searches) * 100' => SORT_ASC],
                        'desc' => ['(bidded_clicks / bidded_searches) * 100' => SORT_DESC],
                    ],
                    'coverage' => [
                        'asc' => ['(bidded_searches / searches) * 100' => SORT_ASC],
                        'desc' => ['(bidded_searches / searches) * 100' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        if(!Yii::$app->user->can(User::ROLE_ADMINISTRATOR)) {
            if(!empty($this->group_by)) {
                if(empty(Yii::$app->user->identity->sub_id)) {
                    $query = $query->having(['sub_id' => []]);
                } else {
                    $subIds = Yii::$app->user->identity->sub_id;
                    $condition = ['or'];
                    foreach ($subIds as $subId) {
                        $condition[] = ['like', 'sub_id', $subId];
                    }
                    $query = $query->having($condition);
                }
            } else {
                $query = $query->where(['sub_id' => empty(Yii::$app->user->identity->sub_id) ? [] : Yii::$app->user->identity->sub_id]);
            }
        }

        $query->andFilterWhere([
            'event_date' => $this->event_date,
            'site_id' => $this->site_id,
        ]);
        if(!empty($this->revenue)) {
            if(!empty($this->group_by)) {
                $query->andFilterHaving([
                    'toFloat32(round(revenue, 2))' => $this->toFloat32(round($this->revenue, 2)),
                ]);
            } else {
                $query->andFilterWhere([
                    'toFloat32(round(revenue, 2))' => $this->toFloat32(round($this->revenue, 2)),
                ]);
            }
        }
        if(!empty($this->tq)) {
            if(!empty($this->group_by)) {
                $query->andFilterHaving([
                    'round(tq, 2)' => $this->toFloat32($this->tq),
                ]);
            } else {
                $query->andFilterWhere([
                    'round(tq, 2)' => $this->toFloat32($this->tq),
                ]);
            }
        }
        if(!empty($this->actual_revenue)) {
            if(!empty($this->group_by)) {
                $query->andFilterHaving([
                    'round(toFloat32(revenue * 0.85), 2)' => $this->toFloat32($this->actual_revenue),
                ]);
            } else {
                $query->andFilterWhere([
                    'round(toFloat32(revenue * 0.85), 2)' => $this->toFloat32($this->actual_revenue),
                ]);
            }
        }
        if(!empty($this->pub_revenue)) {
            $pubRevenueSubIds = \app\models\rate\PubRevenueRate::find()->select('sub_id')->column();
            $pubRevenueRates = \app\models\rate\PubRevenueRate::find()->select('percent')->indexBy('sub_id')->column();
            $pubRevenueCondition = ['or'];
            foreach ($pubRevenueSubIds as $pubRevenueSubId) {
                $grossRevenue = round(($this->pub_revenue/$pubRevenueRates[$pubRevenueSubId] * 100)/0.85, 2);
                $pubRevenueCondition[] = ['and', ['sub_id' => $pubRevenueSubId], ['toFloat32(round(toFloat32(revenue), 2))' => $this->toFloat32($grossRevenue)]];
            }
            $defaultRate =  \app\models\rate\PubRevenueRate::DEFAULT_RATE;
            $pubRevenueCondition[] = ['and', ['NOT IN', 'sub_id', $pubRevenueSubIds], ['toFloat32(round(round(toFloat32(revenue), 2) * 0.85, 2))' => $this->toFloat32(round($this->pub_revenue /$defaultRate * 100, 2))]];
            if(!empty($this->group_by)) {
                $query->andHaving($pubRevenueCondition);
            } else {
                $query->andWhere($pubRevenueCondition);
            }
        }
        if(!empty($this->rpm)) {
            $pubRevenueSubIds = \app\models\rate\PubRevenueRate::find()->select('sub_id')->column();
            $pubRevenueRates = \app\models\rate\PubRevenueRate::find()->select('percent')->indexBy('sub_id')->column();
            $rpmCondition = ['or'];
            foreach ($pubRevenueSubIds as $pubRevenueSubId) {
                $rpmCondition[] = ['and', ['sub_id' => $pubRevenueSubId], ['round(toFloat32(1000 * revenue * 0.85 / bidded_searches), 2)' => $this->toFloat32(round($this->rpm * 100 / $pubRevenueRates[$pubRevenueSubId], 2))]];
            }
            $defaultRate =  \app\models\rate\PubRevenueRate::DEFAULT_RATE;
            $rpmCondition[] = ['and', ['NOT IN', 'sub_id', $pubRevenueSubIds], ['round(toFloat32(1000 * revenue * 0.85 / bidded_searches), 2)' => $this->toFloat32(round($this->rpm * 100 / $defaultRate, 2))]];
            if(!empty($this->group_by)) {
                $query->andHaving($rpmCondition);
            } else {
                $query->andWhere($rpmCondition);
            }
        }
        if(!empty($this->epc)) {
            $pubRevenueSubIds = \app\models\rate\PubRevenueRate::find()->select('sub_id')->column();
            $pubRevenueRates = \app\models\rate\PubRevenueRate::find()->select('percent')->indexBy('sub_id')->column();
            $epcCondition = ['or'];
            foreach ($pubRevenueSubIds as $pubRevenueSubId) {
                $epcCondition[] = ['and', ['sub_id' => $pubRevenueSubId], ['round(toFloat32(revenue * 0.85 / bidded_clicks), 2)' => $this->toFloat32(round($this->epc * 100 / $pubRevenueRates[$pubRevenueSubId], 2))]];
            }
            $defaultRate =  \app\models\rate\PubRevenueRate::DEFAULT_RATE;
            $epcCondition[] = ['and', ['NOT IN', 'sub_id', $pubRevenueSubIds], ['toFloat32(round(round(toFloat32(revenue), 2)  * 0.85 / bidded_clicks, 2))' => $this->toFloat32(round($this->epc * 100 / $defaultRate, 2))]];
            if(!empty($this->group_by)) {
                $query->andHaving($epcCondition);
            } else {
                $query->andWhere($epcCondition);
            }
        }

        if(!empty($this->ctr)) {
            if(!empty($this->group_by)) {
                $query->andHaving([
                    'round(toFloat32((bidded_clicks / bidded_searches) * 100), 2)' => $this->toFloat32($this->ctr),
                ]);
            } else {
                $query->andWhere([
                    'round(toFloat32((bidded_clicks / bidded_searches) * 100), 2)' => $this->toFloat32($this->ctr),
                ]);
            }
        }

        if(!empty($this->coverage)) {
            if(!empty($this->group_by)) {
                $query->andHaving([
                    'round(toFloat32((bidded_searches / searches) * 100), 2)' => $this->toFloat32($this->coverage),
                ]);
            } else {
                $query->andWhere([
                    'round(toFloat32((bidded_searches / searches) * 100), 2)' => $this->toFloat32($this->coverage),
                ]);
            }
        }

        if(!empty($this->group_by)) {
            $groupBy = match ($this->group_by) {
                'date' => 'date',
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
                    'date' => ['date', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'market' => ['market', 'max(date) as date', "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'sub_id' => ['max(date) as date', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", 'sub_id', "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'source_tag' => ['max(date) as date', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                    'device_type' => ['max(date) as date', "arrayStringConcat(groupUniqArray(20)(market), ', ') as market", "arrayStringConcat(groupUniqArray(20)(sub_id), ', ') as sub_id", "arrayStringConcat(groupUniqArray(20)(keyword), ', ') as keyword"],
                ];
                $query->select(array_merge($fields, $groupFields[$groupBy]))
                ->groupBy($groupBy);
            }
        }

        if(!empty($groupBy)) {
            $query->andFilterHaving([
                'unixtimestamp' => $this->unixtimestamp,
                'searches' => $this->searches,
                'bidded_searches' => $this->bidded_searches,
                'bidded_results' => $this->bidded_results,
                'bidded_clicks' => $this->bidded_clicks,
            ]);
            $query->andFilterHaving(['like', 'search_type_tq', $this->search_type_tq])
                ->andFilterHaving(['like', 'keyword', $this->keyword])
                ->andFilterHaving(['like', 'source_tag', $this->source_tag])
                ->andFilterHaving(['like', 'device_type', $this->device_type])
                ->andFilterHaving(['like', 'currency', $this->currency]);
            if(is_array($this->market)) {
                $conditionMarket = ['or'];
                foreach ($this->market as $market) {
                    $conditionMarket[] = ['like', 'market', strtolower($market)];
                }
                $query->andFilterHaving($conditionMarket);
            } else {
                $query->andFilterHaving(['like', 'market', isset($this->market) ? strtolower($this->market) : null]);
            }
            if(is_array($this->sub_id)) {
                $conditionSubId = ['or'];
                foreach ($this->sub_id as $subId) {
                    $conditionSubId[] = ['like', 'sub_id', $subId];
                }
                $query->andFilterHaving($conditionSubId);
            } else {
                $query->andFilterHaving(['like', 'sub_id', $this->sub_id]);
            }
        } else {
            $query->andFilterWhere([
                'unixtimestamp' => $this->unixtimestamp,
                'searches' => $this->searches,
                'bidded_searches' => $this->bidded_searches,
                'bidded_results' => $this->bidded_results,
                'bidded_clicks' => $this->bidded_clicks,
            ]);
            $query->andFilterWhere(['like', 'search_type_tq', $this->search_type_tq])
                ->andFilterWhere(['like', 'keyword', $this->keyword])
                ->andFilterWhere(['like', 'source_tag', $this->source_tag])
                ->andFilterWhere(['like', 'device_type', $this->device_type])
                ->andFilterWhere(['like', 'currency', $this->currency]);
            if(is_array($this->market)) {
                $conditionMarket = ['or'];
                foreach ($this->market as $market) {
                    $conditionMarket[] = ['like', 'market', strtolower($market)];
                }
                $query->andFilterWhere($conditionMarket);
            } else {
                $query->andFilterWhere(['like', 'market', isset($this->market) ? strtolower($this->market) : null]);
            }
            if(is_array($this->sub_id)) {
                $conditionSubId = ['or'];
                foreach ($this->sub_id as $subId) {
                    $conditionSubId[] = ['like', 'sub_id', $subId];
                }
                $query->andFilterWhere($conditionSubId);
            } else {
                $query->andFilterWhere(['like', 'sub_id', $this->sub_id]);
            }
        }

        $timeZone = Yii::$app->timeZone;
        if (!empty($this->dateRange)) {
            if(!empty($groupBy)) {
                $query->andFilterHaving(['>=', "toUnixTimestamp(date, '$timeZone')", $this->dateStart])
                    ->andFilterHaving(['<=', "toUnixTimestamp(date, '$timeZone')", $this->dateEnd]);
            } else {
                $query->andFilterWhere(['>=', "toUnixTimestamp(date, '$timeZone')", $this->dateStart])
                    ->andFilterWhere(['<=', "toUnixTimestamp(date, '$timeZone')", $this->dateEnd]);
            }
        }
        if($this->group_by == 'date') {
            $fullDayStatisticsExist = strtotime((new LastFullDayStatisticsSettings())->last_full_day ?? '') >= time();
            if(!$fullDayStatisticsExist) {
                $prevDayExists = Statistics::find()->where(['date' => date('Y-m-d', time() - Time::SECONDS_IN_A_DAY)])->exists();
                $todaySummaryQuery = StatisticsHourly::getTodaySummaryQuery($prevDayExists);
                $todaySummaryQuery->andFilterWhere([
                    'event_date' => $this->event_date,
                    'site_id' => $this->site_id,
                ]);
                if(!empty($this->revenue)) {
                    $todaySummaryQuery->andFilterHaving([
                        'round(revenue, 2)' => $this->toFloat32($this->revenue),
                    ]);
                }
                if(!empty($this->tq)) {
                    $todaySummaryQuery->andFilterHaving([
                        'round(tq, 2)' => $this->toFloat32($this->tq),
                    ]);
                }
                if(!empty($this->actual_revenue)) {
                    $todaySummaryQuery->andFilterHaving([
                        'round(toFloat32(revenue * 0.85), 2)' => $this->toFloat32($this->actual_revenue),
                    ]);
                }
                if(!empty($this->pub_revenue)) {
                    $todaySummaryQuery->andHaving($pubRevenueCondition);
                }
                if(!empty($this->rpm)) {
                    $query->andHaving($rpmCondition);
                }
                if(!empty($this->epc)) {
                    $query->andHaving($epcCondition);
                }
                if(!empty($this->ctr)) {
                    $query->andHaving([
                        'round(toFloat32((bidded_clicks / bidded_searches) * 100), 2)' => $this->toFloat32($this->ctr),
                    ]);
                }
                if(!empty($this->coverage)) {
                    $query->andHaving([
                        'round(toFloat32((bidded_searches / searches) * 100), 2)' => $this->toFloat32($this->coverage),
                    ]);
                }
                $todaySummaryQuery->andFilterHaving([
                    'unixtimestamp' => $this->unixtimestamp,
                    'searches' => $this->searches,
                    'bidded_searches' => $this->bidded_searches,
                    'bidded_results' => $this->bidded_results,
                    'bidded_clicks' => $this->bidded_clicks,
                ]);
                $todaySummaryQuery->andFilterHaving(['like', 'search_type_tq', $this->search_type_tq])
                    ->andFilterHaving(['like', 'keyword', $this->keyword])
                    ->andFilterHaving(['like', 'source_tag', $this->source_tag])
                    ->andFilterHaving(['like', 'device_type', $this->device_type])
                    ->andFilterHaving(['like', 'currency', $this->currency]);
                if(is_array($this->market)) {
                    $conditionMarket = ['or'];
                    foreach ($this->market as $market) {
                        $conditionMarket[] = ['like', 'market', strtolower($market)];
                    }
                    $todaySummaryQuery->andFilterHaving($conditionMarket);
                } else {
                    $todaySummaryQuery->andFilterHaving(['like', 'market', isset($this->market) ? strtolower($this->market) : null]);
                }
                if(is_array($this->sub_id)) {
                    $conditionSubId = ['or'];
                    foreach ($this->sub_id as $subId) {
                        $conditionSubId[] = ['like', 'sub_id', $subId];
                    }
                    $todaySummaryQuery->andFilterHaving($conditionSubId);
                } else {
                    $todaySummaryQuery->andFilterHaving(['like', 'sub_id', $this->sub_id]);
                }
                if (!empty($this->dateRange)) {
                    $todaySummaryQuery->andFilterHaving(['>=', "toUnixTimestamp(date, '$timeZone')", $this->dateStart])
                        ->andFilterHaving(['<=', "toUnixTimestamp(date, '$timeZone')", $this->dateEnd]);
                }
                $query->union($todaySummaryQuery, true)->addOrderBy(['date' => SORT_DESC]);
                $wrapQuery = Yii::createObject(ActiveQuery::class, [Statistics::class]);
                $wrapQuery->select('*')->from(['u_stat' => $query])->orderBy(['u_stat.date' => SORT_DESC]);
                $dataProvider->query = $wrapQuery;
            }
        }
        //echo $query->createCommand()->rawSql;die;

        return $dataProvider;
    }

    private function toFloat32(?float $value): Expression
    {
        if(is_float($value)) {
            return new Expression("toFloat32($value)");
        }
        return new Expression($value);
    }
}
