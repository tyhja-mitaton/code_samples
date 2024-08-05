<?php

namespace app\models\proxy\enums;

use app\components\traits\EnumToArray;

enum ProxyStatus: int
{
    use EnumToArray;

    case Fail = 0;
    case Success = 1;
    case Checking = 2;

    public function label():string
    {
        return match ($this) {
            self::Fail => \Yii::t('app', 'Fail'),
            self::Success => \Yii::t('app', 'Success'),
            self::Checking => \Yii::t('app', 'Checking'),
        };
    }
}
