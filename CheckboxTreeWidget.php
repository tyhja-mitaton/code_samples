<?php
/**
 * Based on https://github.com/valeriogalano/jquery-tree
 *
 * Usage:
 * \common\components\widgets\CheckboxTreeWidget::begin(['id' => 'tree', 'checkAllName' => 'check-all']);
 *  echo '<input type="checkbox" name="check-all"> Check all <ul><li>[...]</li>[...]<li><ul><li>[...]</li></ul></li></ul>'
 * \common\components\widgets\CheckboxTreeWidget::end()
 */
namespace common\components\widgets;

use common\assets\CheckboxTreeAsset;
use Yii;
use yii\jui\Widget;
use yii\helpers\Html;

class CheckboxTreeWidget extends Widget
{

    public function init()
    {
        CheckboxTreeAsset::register($this->view);
        parent::init();
        ob_start();
        ob_implicit_flush(false);
    }

    public function run()
    {
        parent::run();
        $content = ob_get_clean();
        $html = Html::beginTag('div', $this->options);
        $html .= $content;
        $this->registerClientScripts();
        $html .= Html::endTag('div');

        return $html;
    }

    private function registerClientScripts()
    {
        Yii::$app->view->registerJs("jQuery('#{$this->id}').tree(".json_encode($this->options).");", $this->view::POS_READY, $this->id.'-js');
    }
}