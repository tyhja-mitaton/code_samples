<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class OuterAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        '/template/plugins/bootstrap/css/bootstrap.min.css',
        '/template/css/style.css',
        '/template/css/dark-style.css',
        '/template/css/skin-modes.css',
        '/template/plugins/sidemenu/sidemenu.css',
        '/template/plugins/bootstrap-daterangepicker/daterangepicker.css',
        '/template/plugins/sidebar/sidebar.css',
        '/template/css/icons.css',
        '/css/style.css',
    ];
    public $js = [
        '/template/plugins/bootstrap/js/popper.min.js',
        '/template/js/custom.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
}
