<?php

namespace app\models\user\search;

use app\models\user\User;
use app\models\user\UserProfile;
use cheatsheet\Time;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * UserSearch represents the model behind the search form about `app\models\user\User`.
 */
class UserSearch extends User
{
    public $fullname;
    public $locale;
    public $role;
    public $extraFilter;

    const FILTER_ACTIVE = 'active';
    const FILTER_DELETED = 'deleted';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['username', 'email', 'fullname', 'locale', 'role', 'status_moderation'], 'safe'],
            [['created_at', 'updated_at', 'logged_at'], 'date'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->andFilterWhere([
            'id'               => $this->id,
            'user_profile.locale' => $this->locale,
            'status_moderation' => $this->status_moderation,
        ]);
        $query->joinWith(['userProfile']);

        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'user_profile.fullname', $this->fullname]);

        if($this->extraFilter == self::FILTER_ACTIVE) {
            $query->andFilterWhere([
                'status' => self::STATUS_ACTIVE,
            ])->andFilterWhere([
                'status_moderation' => self::STATUS_MODERATION_ACCEPT
            ]);
        } elseif ($this->extraFilter == self::FILTER_DELETED) {
            $query->andFilterWhere([
                'status' => self::STATUS_DELETED,
            ])->orFilterWhere([
                'status_moderation' => self::STATUS_MODERATION_DECLINE
            ]);
        }

        if (!empty($this->role)) {
            $query->join('INNER JOIN', 'auth_assignment', 'auth_assignment.user_id = id');
            $query->andFilterWhere([
                'auth_assignment.item_name' => $this->role,
            ]);
        }

        foreach (['logged_at', 'created_at', 'updated_at'] as $attribute) {
            if (!empty($this->$attribute)) {
                $time = strtotime($this->$attribute);
                $query->andWhere([
                    'AND',
                    [
                        '>=',
                        $attribute,
                        $time
                    ],
                    [
                        '<',
                        $attribute,
                        $time + Time::SECONDS_IN_A_DAY
                    ]
                ]);
            }
        }

        return $dataProvider;
    }

    public static function getList()
    {
        static $list;
        if ($list === null) {
            $list = [];
            $rows = (new \yii\db\Query())
                ->select(['id', 'fullname', 'username', 'email'])
                ->join('INNER JOIN', UserProfile::tableName(), 'user_id = id')
                ->from(self::tableName())
                ->all();
            foreach ($rows as $row) {
                $list[$row['id']] = '#' . $row['id'] . ' - ' . (empty($row['fullname']) ? $row['username'] : $row['fullname']) . ' (' . $row['email'] . ')';
            }
        }
        return $list;
    }

    public static function getDropDownList($type = null)
    {
        $allowedIds = null;
        if (!empty($type)) {
            $query = new Query();
            $query->select('user_id')
                ->from('auth_assignment')
                ->filterWhere(['item_name' => $type]);
            $allowedIds = $query->column();
            if (empty($allowedIds)) {
                return [];
            }
        }
        return ArrayHelper::map(
            User::find()->orderBy(['username' => SORT_ASC])->andFilterWhere(['id' => $allowedIds])->all(),
            'id', 'username');
    }

    public static function getActiveCount()
    {
        return [
            'total' => self::find()->count(),
            'active' => self::find()->andWhere(['>=', 'logged_at', time() - Time::SECONDS_IN_A_MONTH])->count(),
            'new' => self::find()->andWhere(['>=', 'created_at', time() - Time::SECONDS_IN_A_MONTH])->count(),
        ];
    }
}
