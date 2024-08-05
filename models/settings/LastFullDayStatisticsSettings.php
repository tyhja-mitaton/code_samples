<?php

namespace app\models\settings;

/**
 * @property string $last_full_day
 */
class LastFullDayStatisticsSettings extends Settings
{
    protected $category = "statistics-daily";

    public function rules()
    {
        return [
            ['last_full_day', 'string']
        ];
    }

    public function attributeLabels()
    {
        return [
            'last_full_day' => \Yii::t('app', 'Last full day'),
        ];
    }
}