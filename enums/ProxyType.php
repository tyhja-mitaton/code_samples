<?php

namespace app\models\proxy\enums;

use app\components\traits\EnumToArray;

enum ProxyType: int
{
    use EnumToArray;

    case Http = 0;
    case Https = 1;

    public function label(): string
    {
        return match ($this) {
            self::Http => \Yii::t('app', 'http'),
            self::Https => \Yii::t('app', 'https'),
        };
    }

}
