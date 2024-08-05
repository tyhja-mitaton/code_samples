<?php


namespace app\models\settings;

use Yii;

abstract class Settings extends \yii\base\Model
{
    protected $category;
    protected $attributes = [];

    public function __get($setting)
    {
        if (method_exists($this, 'read' . $setting)) {
            return $this->{'read' . $setting}(Yii::$app->settings->get($setting, $this->category));
        }

        return Yii::$app->settings->get($setting, $this->category);
    }

    public function __set($setting, $value)
    {
        $this->setAttributes([
            $setting => $value
        ]);

        $this->attributes[] = $setting;
    }

    public function save($validate = true)
    {
        if ($validate && !$this->validate()) {
            return false;
        }
        $this->beforeSave();
        foreach ($this->attributes as $attribute) {
            if (method_exists($this, 'write' . $attribute)) {
                $value = $this->{'write' . $attribute}($this->{$attribute});
                Yii::$app->settings->set($attribute, $value, $this->category, 'string');
            } else {
                Yii::$app->settings->set($attribute, $this->{$attribute}, $this->category, 'string');
            }
        }

        return true;
    }

    public function beforeSave()
    {
        return true;
    }
}