<?php

namespace app\models\search;

use app\components\assembly\Job;
use app\models\Build;
use kartik\daterange\DateRangeBehavior;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\reference\Reference as ReferenceModel;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Reference represents the model behind the search form of `app\models\reference\Reference`.
 */
class Reference extends ReferenceModel
{
    public $application;
    public $built_at;
    public $builtTimeRange;
    public $builtTimeStart;
    public $builtTimeEnd;
    public $logText;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'application_id', 'status'], 'integer'],
            [['url', 'package', 'application'], 'safe'],
            [['comment', 'logText'], 'string'],
            [['builtTimeRange'], 'match', 'pattern' => '/^.+\s\-\s.+$/'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => DateRangeBehavior::class,
                'attribute' => 'builtTimeRange',
                'dateStartAttribute' => 'builtTimeStart',
                'dateEndAttribute' => 'builtTimeEnd',
            ]
        ];
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ReferenceModel::find()
            ->joinWith('application')
            ->leftJoin(['b' => (new Query())->select(["application_id", 'build_id' => new Expression("params->'$.build_id'")])
                ->from('assembly_line_log')->where(['stage' => Job::BUILD])
                ->having(['IS NOT', 'build_id', null])], 'b.application_id = reference.application_id')
            ->leftJoin('build', 'build.id = b.build_id')
            ->addSelect(['reference.*'])
            ->groupBy('reference.id');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 25,
            ],
        ]);

        $dataProvider->setSort([
            'attributes' => [
                'reference.id',
                'package',
                'status',
                'comment',
                'application' => [
                    'asc' => ['application.name' => SORT_ASC, 'application.tool_version' => SORT_ASC],
                    'desc' => ['application.name' => SORT_DESC, 'application.tool_version' => SORT_DESC],
                    'label' => 'Application'
                ],
                'built_at' => [
                    'asc' => ['MAX(build.time_create)' => SORT_ASC],
                    'desc' => ['MAX(build.time_create)' => SORT_DESC],
                ],
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (empty($this->status)) {
            $query->andWhere(['reference.status' => array_keys(ReferenceModel::statuses())]);
        } else {
            $query->andFilterWhere(['reference.status' => $this->status]);
        }

        $query->andFilterWhere([
            'reference.id' => $this->id,
            'application_id' => $this->application_id,
            'reference.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'reference.package', $this->package])
            ->andFilterWhere(['like', 'reference.comment', $this->comment]);

        $query->andFilterWhere(['OR',
            ['like', 'application.name', $this->application],
            ['like', 'application.tool_version', $this->application]
        ]);

        if (!empty($this->builtTimeRange)) {
            $query->andFilterWhere(['>=', 'build.time_create', $this->builtTimeStart])
                ->andFilterWhere(['<=', 'build.time_create', $this->builtTimeEnd]);
        }

        $query->andFilterWhere(['LIKE', 'LOWER(build.log)', strtolower($this->logText)]);
        if(!empty($this->logText)) {
            $logUrls = Build::find()
                ->select('app_url')
                ->where([
                    'OR',
                    [
                        'AND',
                        ['NOT', ['log_test' => null]],
                        ['LIKE', 'LOWER(log_test)', strtolower($this->logText)]
                    ],
                    [
                        'AND',
                        ['NOT', ['logcat_json' => null]],
                        ['LIKE', 'LOWER(logcat_json)', strtolower($this->logText)]
                    ]
                ])
                ->column();
            $query->orFilterWhere(['url' => $logUrls]);
        }

        return $dataProvider;
    }
}
