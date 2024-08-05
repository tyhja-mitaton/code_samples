<?php

namespace app\models\geo;

/**
 * This is the model class for table "geo_city".
 *
 * @property integer $id
 * @property integer $country_id
 * @property integer $region_id
 * @property string $name_ru
 * @property string $name_en
 *
 * @property Country $country
 * @property Region $region
 */
class City extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'geo_city';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country_id', 'region_id'], 'integer'],
            [['name_ru', 'name_en'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country_id' => 'Country ID',
            'region_id' => 'Region ID',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::className(), ['region_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Region::className(), ['country_id' => 'id']);
    }
}
