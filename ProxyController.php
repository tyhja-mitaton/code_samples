<?php

namespace app\commands;

use app\models\proxy\Proxy;

class ProxyController extends \yii\console\Controller
{
    public function actionCheck($id = null)
    {
        Proxy::check($id);
    }

}