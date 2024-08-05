<?php

namespace common\components\widgets;

use common\assets\SwitcherAsset;
use kartik\base\InputWidget;
use yii\helpers\Html;

class SwitcherWidget extends InputWidget
{
    public $defaultLabelOptions = ['class' => 'switch pr-5 switch-success mr-3'];
    public $leftLabel;

    public function run()
    {
        return $this->getCheckbox();
    }

    public function hasModel(): bool
    {
        return parent::hasModel();
    }

    public function init()
    {
        parent::init();
        $this->options['checked'] = (bool)$this->value;
        SwitcherAsset::register($this->view);
    }

    protected function getCheckbox()
    {
        return $this->render('switcher/switcher', ['switcher' => $this]);
    }

}