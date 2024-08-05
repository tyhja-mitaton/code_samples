<?php

namespace app\models\search;

use yii\data\ActiveDataProvider;
use app\models\rate\PubRevenueRate as PubRevenueRateModel;

class PubRevenueRate extends PubRevenueRateModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sub_id', 'percent'], 'safe']
        ];
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
        $query = PubRevenueRateModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->andFilterWhere([
            'sub_id' => $this->sub_id,
            'percent' => $this->percent,
        ]);

        return $dataProvider;
    }
}