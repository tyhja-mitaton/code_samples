<?php

namespace app\assets;

class NotApplinkAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'kitcat/css/styles.css',
        'kitcat/css/styles-mob.css',
        'kitcat/css/theme/dark_theme.css',
        'css/disable-global.css',
        'css/flag-icon/css/flag-icon.min.css',
        'kitcat/jquery-ui-1.13.2.custom/jquery-ui.min.css',
        'kitcat/jquery-ui-1.13.2.custom/jquery-ui.structure.min.css',
        'kitcat/jquery-ui-1.13.2.custom/jquery-ui.theme.min.css',
        'kitcat/jsvectormap/dist/css/jsvectormap.min.css',
    ];
    public $js = [
        '/dash/lib/jquery/jquery.min.js',
        //'/dash/lib/bootstrap/js/bootstrap.bundle.min.js',
        '/dash/lib/perfect-scrollbar/perfect-scrollbar.min.js',
        '/dash/lib/jquery.flot/jquery.flot.js',
        '/dash/lib/jquery.flot/jquery.flot.stack.js',
        '/dash/lib/jquery.flot/jquery.flot.resize.js',
        '/dash/lib/jqvmap/jquery.vmap.min.js',
        '/dash/lib/jqvmap/maps/jquery.vmap.world.js',
        '/js/clipboard.min.js',
        '/js/copy.js',
        '/js/shorten.js',
        '/js/select2-4.0.13/dist/js/select2.js',
        '/js/audio.js',
        '/js/svg-utils.js',
        '/js/switch-menu.js',
        'kitcat/jquery-ui-1.13.2.custom/jquery-ui.min.js',
        '/js/init-datepicker.js',
        'kitcat/jsvectormap/dist/js/jsvectormap.min.js',
        'kitcat/jsvectormap/dist/maps/world.js',
        'kitcat/jsvectormap/country_coords.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
}