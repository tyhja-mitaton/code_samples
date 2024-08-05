<?php

namespace app\models\structure\creative;

use app\components\queue\UpdateChildCreatives;
use app\components\queue\UploadMediafileQueue;
use app\models\structure\contract\Contract;
use app\models\structure\counteragent\Counteragent;
use app\models\structure\creative\enums\CreativeMarkerPositions;
use app\models\structure\creative\traits\Api;
use app\models\structure\creative\traits\Finders;
use app\models\structure\creative\traits\HtmlTrait;
use app\models\structure\creative\traits\MediaTrait;
use app\models\structure\Structure;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use app\models\structure\creative\traits\Relations;
use yii\validators\UrlValidator;

/**
 * This is the model class for table "creative".
 *
 * @property int $id
 * @property string $internal_name
 * @property string $description
 * @property string $url
 * @property string $contract_id
 * @property string|array $okveds
 * @property array|null $commercial
 * @property int $form
 * @property int $pay_type
 * @property string $targeting
 * @property string|null $texts
 * @property string $extra_media_urls
 * @property int $processing_status
 * @property string $marker
 * @property string $original_mediafile_name
 * @property string $original_mediafile_path
 * @property int|null $parent
 * @property string $reporting_id
 * @property int $marker_position
 * @property bool $is_archived
 * @property int $created_at
 * @property int $updated_at
 * @property string $marker_counteragent_id
 * @property string $marker_text
 * @property bool $is_template
 *
 * @property-read string $markedMediaFilePath
 *
 * @property Contract $contract
 * @property Counteragent $reportingPersonExternal
 */
class Creative extends Structure
{
    use Relations, Finders, HtmlTrait, MediaTrait, Api;

    const CREATIVE_MASK_PATTERN = '/marker_([^.]+)/';

    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_TEMPLATE = 'template';

    private $deletedKeyFile = null;

    private $saveFile;

    public array $mediafiles = [];

    /**
     * @var array<Creative>
     */
    private $childCreativesWaiting = [];

    /**
     * @var bool $sendToOrd
     */
    private bool $sendToOrd = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'creative';
    }

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                [
                    'class' => TimestampBehavior::class,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['form', 'pay_type', 'description', 'okveds', 'contract_id', 'okvedList'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['contract_id', 'internal_name'], 'required', 'on' => self::SCENARIO_TEMPLATE],
            [['internal_name'], 'string', 'on' => self::SCENARIO_TEMPLATE],
            [['description'], 'string'],
            [['form', 'pay_type', 'processing_status', 'marker_position'], 'integer'],
            [['targeting', 'contract_id'], 'string', 'max' => 255],
            ['url', 'url', 'defaultScheme' => 'http'],
            [['texts', 'okveds', 'extra_media_urls','commercial'], 'safe'],
            [['mediafiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'jpg, jpeg, png, mov, mp4', 'maxFiles' => $this->isParent() ? null : 1],
            ['targeting', 'default', 'value' => '18+'],
            ['extra_media_urls', 'validateExtraUrl'],
            ['okvedList', 'validateOkveds', 'skipOnEmpty' => false, 'on' => self::SCENARIO_DEFAULT],
            ['okvedList', 'validateOkveds', 'skipOnEmpty' => true, 'on' => self::SCENARIO_TEMPLATE],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contract::class, 'targetAttribute' => ['contract_id' => 'external_id']],
            [['form', 'extra_media_urls', 'targeting', 'pay_type', 'description', 'okveds', 'commercial', 'contract_id', 'url', 'texts'], 'validateFieldForNonParent'],
            [['is_archived', 'is_template'], 'boolean'],
            ['is_archived', 'default', 'value' => 0],
            [['form','pay_type','marker_position'], 'filter', 'filter' => 'intval'],
            [['marker_counteragent_id'], 'default', 'value' => null],
            ['marker_text', 'validateMarketText'],
        ];
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function validateMarketText($attribute)
    {
        $needle = ['РЕКЛАМА', 'AGENT', 'INN', 'ERID'];
        $marketText = $this->{$attribute};
        if (array_diff($needle, explode('*', $marketText))) {
            $this->addError($attribute, Yii::t('app', "Необходимо указать все обязательные теги в тексте маркировки"));
            return false;
        }
        return true;
    }

    /**
     * Only parent creatives can edit fields.
     */
    public function validateFieldForNonParent($attribute, $params): bool
    {
        if (!$this->isParent()) {
            $excluded_attributes = ['marker_position'];

            if (in_array($attribute, $excluded_attributes)) {
                return true;
            }

            $originalValue = $this->getOldAttribute($attribute);
            $currentValue = $this->{$attribute};

            if (is_array($originalValue) && is_array($currentValue)) {
                $result = empty(array_diff($originalValue, $currentValue));
            } else {
                $result = (string)$originalValue === (string)$currentValue;
            }

            if (!$result) {
                $this->addError($attribute, "You are not allowed to modify this creative. Creative should be parent");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate extra urls
     */
    public function validateExtraUrl($attribute): void
    {
        $requiredValidator = new UrlValidator();

        foreach ($this->$attribute as $index => $row) {
            $error = null;
            if (!empty($row)) {
                $requiredValidator->validate($row, $error);
            }
            if (!empty($error)) {
                $key = $attribute . '[' . $index . ']';
                $this->addError($key, $error);
            }
        }
    }

    /**
     * Validate okved field
     */
    public function validateOkveds($attribute): void
    {
        $okveds = str_replace(' ', '', $this->{$attribute});
        $okvedsList = explode(',', $okveds);
        foreach ($okvedsList as $okved) {
            if (!preg_match('/^\d{1,2}\.\d{1,2}(\.\d{1,2})?$/', $okved)) {
                $this->addError($attribute, Yii::t('app', "Некорректный код ОКВЭД: $okved"));
                break;
            }
        }
    }

    /**
     * @return bool
     */
    public function isParent(): bool
    {
        return (int)$this->parent === 0;
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->countChild() > 0;
    }

    /**
     * @param array $attributes
     * @param array $unsetAttributes
     * @return Creative
     */
    public function createChild(array $attributes = [], array $unsetAttributes = ['id', 'marker', 'external_id', 'parent']): Creative
    {
        $childCreative = new Creative();

        $allAttributes = ArrayHelper::merge($this->attributes, $attributes);

        foreach ($unsetAttributes as $attr) {
            unset($allAttributes[$attr]);
        }

        foreach ($allAttributes as $attribute_name => $attribute_value) {
            $childCreative->{$attribute_name} = $attribute_value;
        }

        $childCreative->external_id = $this->generateExternalId();
        $childCreative->parent = !empty($this->id) ? $this->id : -1;

        return $childCreative;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'internal_name' => Yii::t('app', 'Название'),
            'description' => Yii::t('app', 'Описание'),
            'url' => Yii::t('app', 'Ссылка рекламного перехода'),
            'contract_id' => Yii::t('app', 'Договор'),
            'okveds' => Yii::t('app', 'Коды ОКВЭД'),
            'okvedList' => Yii::t('app', 'Коды ОКВЭД'),
            'commercial' => Yii::t('app', 'Реклама'),
            'form' => Yii::t('app', 'Форма креатива'),
            'pay_type' => Yii::t('app', 'Тип рекламной компании'),
            'targeting' => Yii::t('app', 'Целевая аудитория креатива'),
            'extra_media_urls' => Yii::t('app', 'URL Внешних медиафайлов'),
            'texts' => Yii::t('app', 'Тексты'),
            'processing_status' => Yii::t('app', 'Состояние маркировки'),
            'original_mediafile_name' => Yii::t('app', 'Медиа файл'),
            'mediafiles' => Yii::t('app', 'Медиа файл'),
            'marker' => Yii::t('app', 'Маркировка'),
            'markedMediaFilePath' => Yii::t('app', 'Маркированный медиа-файл'),
            'owner_id' => Yii::t('app', 'Менеджер'),
            'marker_position' => Yii::t('app', 'Расположение маркировки'),
            'created_at' => Yii::t('app', 'Дата создания'),
            'updated_at' => Yii::t('app', 'Дата обновления'),
            'marker_counteragent_id' => Yii::t('app', 'Рекламодатель для маркировки'),
            'marker_text' => Yii::t('app', 'Текст маркировки'),
        ];
    }

    public function afterFind()
    {
        parent::afterFind();
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        // chang status trigger
        if (!$this->sendToOrd) {
            return true;
        }

        if (!$this->reporting_id) {
            $this->reporting_id = Counteragent::getCurrentReportingPerson($this->owner)?->external_id;
            $this->created_at = time(); $this->updated_at = time();
        }

        // generate internal name
        if ((empty($this->internal_name) || !empty($this->getDirtyAttributes(['contract_id']))) && !$this->is_template) {
            $this->internal_name = $this->generateInternalName();
        }

        // for parent without media
        if ($this->isParent() && !($this->hasChildren() || !empty($this->mediafiles))) {
            // new record to ord
            if ((bool) count(array_diff(array_keys($this->dirtyAttributes), ['internal_name', 'form']))) {
                $this->external_id = $this->generateExternalId();
            }
            return $this->is_template || parent::beforeSave($insert);
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        // chang status trigger
        if (!$this->sendToOrd) {
            return true;
        }

        // for parent has media
        if ($this->isParent() && !empty($this->mediafiles)) {
            $this->createChildCreativesByUpload($this->mediafiles);
        }

        // queue for children creative
        if ($insert && isset($this->saveFile)) {
            //queue chain | upload -> sendOrd -> marker
            $this->addTaskToQueue(new UploadMediafileQueue([
                'creative_id' => $this->id,
                'saveFile' => Json::encode($this->saveFile),
            ]));
        }

        // update creative
        if (
            !$insert
            && (count($changedAttributes) > 0 || !empty($this->mediafiles))
            && !$this->is_template
        ) {
            // queue chain | upload -> sendOrd -> marker
            $this->addTaskToQueue(new UpdateChildCreatives([
                'creative_id' => $this->id,
                'changedAttributes' => Json::encode($changedAttributes),
                'saveFile' => !empty($this->mediafiles) ? $this->mediafiles[0]: null
            ]));
        }

        parent::afterSave($insert, $changedAttributes);
        return true;
    }

    public function getMarkerPosition(): int
    {
        if ($this->marker_position === NULL && !$this->isParent()) {
            return Creative::findOne($this->parent)->getMarkerPosition();
        } else {
            return $this->marker_position === NULL ? CreativeMarkerPositions::Center->value : $this->marker_position;
        }
    }

    /**
     * Create child creative instances by uploaded files
     */
    private function createChildCreativesByUpload(array $mediafiles): void
    {
        foreach ($mediafiles as $fileData) {
            $fileData = Json::decode($fileData);

            $fileName = $fileData['server_file_name'];

            $uploadFilePath = Yii::$app->basePath . '/web/storage/uploads/' . basename($fileName);
            if (!file_exists($uploadFilePath)) continue;

            $childCreative = $this->createChild();
            $childCreative->parent = $this->id;
            $childCreative->saveFile = $fileData;
            $childCreative->save(false);
        }
    }

    public function getOkvedList()
    {
        return !empty($this->okveds) ? implode(',', $this->okveds) : null;
    }

    public function setOkvedList($okveds)
    {
        $this->okveds = explode(',', $okveds);
    }

    public function generateInternalName()
    {
        $internalName = "";

        if (!empty($this->contract)) {
            $internalName .= $this->contract->serial;
        }

        if (!empty($this->original_mediafile_name)) {
            $internalName .= "_" . pathinfo($this->original_mediafile_name, PATHINFO_FILENAME);
        }

        return $internalName;
    }
}
