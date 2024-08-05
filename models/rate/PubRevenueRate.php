<?php

namespace app\models\rate;

/**
 * @property string $sub_id
 * @property integer $percent
 */
class PubRevenueRate extends \yii\db\ActiveRecord
{
    const DEFAULT_RATE = 70;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pub_revenue_rate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sub_id', 'percent'], 'required'],
            ['sub_id', 'string'],
            ['percent', 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sub_id' => 'Sub ID',
            'percent' => 'Percent, %',
        ];
    }

    public static function getPercentage($subId): int
    {
        $rate = self::findOne(['sub_id' => $subId]);

        return !empty($rate) ? $rate->percent : self::DEFAULT_RATE;
    }

}