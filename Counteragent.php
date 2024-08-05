<?php

namespace app\models\structure\counteragent;

use app\components\validators\LinkValidator;
use app\models\geo\traits\Relations as CountryRelation;
use app\models\structure\counteragent\enums\CounteragentType;
use app\models\structure\counteragent\traits\Finders;
use app\models\structure\counteragent\traits\Finders as CounteragentFinder;
use app\models\structure\counteragent\traits\HtmlTrait;
use app\models\structure\counteragent\traits\CounteragentExtension;
use app\models\structure\Structure;
use app\modules\structure\contractor\controllers\CounteragentController;
use Gomzyakov\Validator\INN;
use Yii;
use app\models\structure\counteragent\enums\CounteragentRole;
use yii\helpers\Json;

/**
 * This is the model class for table "counteragent".
 *
 * @property int $id
 * @property string $name
 * @property int $type
 * @property string $inn
 * @property string $phone
 * @property array $roles
 * @property string $rs_url
 * @property bool $is_foreign
 * @property bool $is_active
 * @property string $foreign_oksm_country_code
 * @property string $foreign_epayment_method
 * @property string $foreign_registration_number
 * @property string $foreign_inn
 * @property boolean $is_reporting_person
 * @property string $ord_token
 * @property string $ord_provider
 * @property bool $is_archived
 */
class Counteragent extends Structure
{
    use CountryRelation, CounteragentFinder, Finders, HtmlTrait, CounteragentExtension;

    const SCENARIO_CREATE_OWN = 'create_own';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'counteragent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone'], 'required', 'when' => function ($model) {
                if ($model->is_foreign && $model->type == CounteragentType::ForeignJuridical->value) {
                    return false;
                }
                return true;
            },
                'whenClient' => 'function(){return false;}',
                'message' => 'Необходимо заполнить «Телефон»'
            ],
            [['name', 'type', 'roles'], 'required'],
            [['is_reporting_person', 'ord_token', 'ord_provider'], 'required', 'on' => self::SCENARIO_CREATE_OWN],
            [['type', 'country_id'], 'integer'],
            [['is_foreign', 'is_reporting_person', 'is_archived'], 'boolean'],
            [['name', 'rs_url', 'foreign_epayment_method', 'foreign_registration_number', 'foreign_inn'], 'string', 'max' => 255],
            [['inn'], 'validateInn'],
            [['phone'], 'string', 'min' => 9, 'max' => 16],
            [['phone'], 'match', 'pattern' => '/^[\+]?[0-9]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{1,8}$/', 'message' => 'Неверный формат номера телефона'],
            [['foreign_oksm_country_code'], 'string', 'min' => 3, 'max' => 3],
            [['foreign_oksm_country_code'], 'match', 'pattern' => '/^\d{3}$/', 'message' => 'Неправильный формат ОКСМ'],
            ['rs_url', 'validateRsUrl', 'skipOnEmpty' => false],
            [['ord_token', 'ord_provider'], 'validateOrdToken'],
            ['rs_url', 'string'],
            ['roles', 'safe'],
            ['ord_token', 'unique', 'message' => 'Токен уже используется'],
            ['is_archived', 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Наименование'),
            'type' => Yii::t('app', 'Тип'),
            'inn' => Yii::t('app', 'ИНН'),
            'phone' => Yii::t('app', 'Телефон'),
            'roles' => Yii::t('app', 'Роль'),
            'rs_url' => Yii::t('app', 'Ссылка рекламной площадки'),
            'foreign_epayment_method' => Yii::t('app', 'Реквизиты для оплаты'),
            'foreign_registration_number' => Yii::t('app', 'Регистрационный номер'),
            'country_id' => Yii::t('app', 'Страна'),
            'foreign_oksm_country_code' => Yii::t('app', 'ОКСМ'),
            'foreign_inn' => Yii::t('app', 'Номер налогоплательщика'),
            'is_foreign' => Yii::t('app', 'Иностранный'),
            'is_reporting_person' => Yii::t('app', 'Отчетное лицо'),
            'ord_token' => Yii::t('app', 'Токен ОРД'),
            'ord_provider' => Yii::t('app', 'ОРД Провайдер'),
            'owner_id' => Yii::t('app', 'ID менеджера'),
        ];
    }

    /**
     * @param $attribute
     * @return void
     */
    public function validateRsUrl($attribute)
    {
        if (in_array(CounteragentRole::Ors->value, $this->roles)) {
            if (empty($this->{$attribute})) {
                $this->addError($attribute, $this->getAttributeLabel($attribute) . " не может быть пустой");
            }
        }
    }

    public function validateInn($attribute)
    {
        if (!empty($this->{$attribute})) {
            $inn = new INN($this->{$attribute});
            if (!$inn->isValid()) {
                $this->addError($attribute, "Инн некорректен");
            }
        }
    }

    /**
     * @param $attribute
     * @return void
     */
    public function validateOrdToken($attribute)
    {
        if ($this->is_reporting_person) {
            if (empty($this->{$attribute})) {
                $this->addError($attribute, Yii::t('counteragent', 'У отчетного лица должен быть хотя бы один ОРД токен'));
            } else {
                if (empty($this->ord_provider)) {
                    $this->addError($attribute, Yii::t('counteragent', 'Для добавления токена должен быть заполнен провайдер'));
                }
                if (empty($this->ord_token)) {
                    $this->addError($attribute, Yii::t('counteragent', 'Для добавления токена у него должно быть заполнено значение'));
                }
            }
        }
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (array_key_exists('is_reporting_person', $this->dirtyAttributes) && !$this->is_reporting_person) {
            $this->ord_token = null;
        }
        if ($this->isAttributeChanged('phone')) {
            $this->phone = ltrim($this->phone, '+');
        }
        if (
            array_key_exists('roles', $this->dirtyAttributes) &&
            in_array(CounteragentRole::Ors->value, $this->getDirtyAttributes()['roles']) &&
            !in_array(CounteragentRole::Ors->value, $this->roles)
        ) {
            $this->rs_url = null;
        }

        return parent::beforeSave($insert);
    }
}
