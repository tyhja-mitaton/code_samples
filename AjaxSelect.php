<?php

namespace app\components;

use yii\helpers\Url;
use yii\web\JsExpression;
use Yii;

class AjaxSelect extends \kartik\select2\Select2
{
    public $url = "/structure/creative/creative/contractor-list";
    public $nameField = 'name';
    public $placeholder = 'Поиск отчётного лица ...';

    public function init()
    {
        $this->pluginOptions = [
            'allowClear' => true,
            'minimumInputLength' => 3,
            'language' => [
                'errorLoading' => new JsExpression("function () { return 'Загрузка...'; }"),
            ],
            'ajax' => [
                'url' => Url::to($this->url),
                'dataType' => 'json',
                'data' => new JsExpression('function(params) { return {name:params.term}; }')
            ],
            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
            'templateResult' => new JsExpression("function(contractor) { return contractor.{$this->nameField}; }"),
            'templateSelection' => new JsExpression('function (contractor) { return contractor.text; }'),
        ];

        $this->options = ['multiple' => false, 'placeholder' => Yii::t('common', $this->placeholder)];

        parent::init();
    }

}