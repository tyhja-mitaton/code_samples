<?php

namespace app\models\geo;

use cheatsheet\Time;
use Yii;

/**
 * This is the model class for table "geo_country".
 *
 * @property integer $id
 * @property string $iso
 * @property string $name_ru
 * @property string $name_en
 *
 * @property Region[] $regions
 * @property City[] $cities
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'geo_country';
    }

    public static function getLanguage()
    {
        $availableLanguages = [
            'ru', 'en',
        ];
        if (in_array(Yii::$app->language, $availableLanguages)) {
            return Yii::$app->language;
        }
        return 'ru';
    }

    public static function nameField($language)
    {
        return 'name_' . $language;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['iso'], 'string', 'max' => 2],
            [['name_ru', 'name_en'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'iso' => 'Iso',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegions()
    {
        return $this->hasMany(Region::className(), ['id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::className(), ['id' => 'country_id']);
    }

    public static function getList($asIso = false, $showIso = true)
    {
        static $list;
        if ($list === null) {
            $list = [];
            foreach (self::find()->orderBy('name_en')->all() as $country) {
                /**
                 * @var Country $country
                 */
                $list[$asIso ? $country->iso : $country->id] = ($showIso ? '[' . $country->iso . '] ' : '') . $country->name_en;
            }
        }
        return $list;
    }
}
