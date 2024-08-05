<?php

namespace app\models\proxy;

use app\models\proxy\traits\Utils;
use Yii;

/**
 * This is the model class for table "proxy".
 *
 * @property int $id
 * @property string $host
 * @property int $port
 * @property string $username
 * @property string $password
 * @property int $status
 * @property int $type
 * @property int $owner_id
 */
class Proxy extends \yii\db\ActiveRecord
{
    use Utils;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proxy';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['host', 'port', 'username', 'password'], 'required'],
            [['port', 'status', 'type', 'owner_id'], 'integer'],
            [['host', 'username', 'password'], 'string', 'max' => 255],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9\-\_\.]*$/'],
            ['host', 'match', 'pattern' => '/^[a-zA-Z0-9\-\_\.]*$/'],
            ['port', 'match', 'pattern' => '/^[0-9]*$/'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute)
    {
        if(preg_match('/[@\/\\\:&?]/', $this->{$attribute})) {
            $this->addError($attribute, Yii::t('app', $attribute . ' is invalid.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'host' => Yii::t('app', 'Host'),
            'port' => Yii::t('app', 'Port'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'status' => Yii::t('app', 'Status'),
            'type' => Yii::t('app', 'Scheme'),
            'owner_id' => Yii::t('app', 'Owner'),
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        self::check($this->id);
    }
}
