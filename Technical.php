<?php
namespace app\models\graphic;

use app\components\File;
use app\models\user\User;
use Imagick;
use yii\behaviors\TimestampBehavior;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use Yii;

/**
 * This is the model class for table "graphic_technical".
 * For visual tests: https://phpimagick.com/Imagick/
 *
 * @property int $id
 * @property string $user_id
 * @property string $source_file
 * @property string $modified_file
 * @property int $created_at
 */
class Technical extends \yii\db\ActiveRecord
{
    const FILE_SOURCE_STORAGE_URL = '/storage/graphic/technical/source/';
    const FILE_MODIFIED_STORAGE_URL = '/storage/graphic/technical/modified/';

    public $username;

    private $sourceFile;
    private $modifiedFile;

    public $modifiedFileName = '';

    //зеркально
    public $isTransverseImage;
    //разрешение
    public $isScaleImage;
    public $scaleImageWidth;
    public $scaleImageHeight;
    //адаптивное размытие
    public $isAdaptiveBlurImage;
    public $adaptiveBlurImageRadius;
    public $adaptiveBlurImageSigma;
    //размытие
    public $isBlurImage;
    public $blurImageRadius;
    public $blurImageSigma;
    //резкость
    public $isAdaptiveSharpenImage;
    public $adaptiveSharpenImageRadius;
    public $adaptiveSharpenImageSigma;
    //шум
    public $isAddNoiseImage;
    public $noiseType;
    //случайно сместить каждый пиксель в блоке
    public $isSpreadImage;
    public $spreadRadius;
    //закрутить пиксели вокруг центра изображения
    public $isSwirlImage;
    public $swirlSwirl;


    public static function tableName()
    {
        return 'graphic_technical';
    }

    public function rules()
    {
        return [
            [['sourceFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg', 'checkExtensionByMimeType' => false],
            [['user_id'], 'integer'],
            [['source_file', 'modified_file'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['isTransverseImage',], 'boolean'],
            [['isScaleImage',], 'boolean'],
            [['scaleImageWidth'], 'number', 'min' => 50, 'max' => 800, 'skipOnEmpty' => false],
            [['scaleImageHeight'], 'number', 'min' => 50, 'max' => 1000, 'skipOnEmpty' => false],
            [['isAdaptiveBlurImage',], 'boolean'],
            [['adaptiveBlurImageRadius'], 'number', 'min' => 0, 'max' => 10, 'skipOnEmpty' => false],
            [['adaptiveBlurImageSigma'], 'number', 'min' => 0, 'max' => 100, 'skipOnEmpty' => false],
            [['isBlurImage',], 'boolean'],
            [['blurImageRadius'], 'number', 'min' => 0, 'max' => 10, 'skipOnEmpty' => false],
            [['blurImageSigma'], 'number', 'min' => 0, 'max' => 100, 'skipOnEmpty' => false],
            [['isAdaptiveSharpenImage',], 'boolean'],
            [['adaptiveSharpenImageRadius'], 'number', 'min' => 0, 'max' => 10, 'skipOnEmpty' => false],
            [['adaptiveSharpenImageSigma'], 'number', 'min' => 0, 'max' => 100, 'skipOnEmpty' => false],
            [['isAddNoiseImage',], 'boolean'],
            [['noiseType'], 'number', 'min' => 1, 'max' => 7, 'skipOnEmpty' => false],
            [['isSpreadImage',], 'boolean'],
            [['spreadRadius'], 'number', 'min' => 0, 'max' => 100, 'skipOnEmpty' => false],
            [['isSwirlImage',], 'boolean'],
            [['swirlSwirl'], 'number', 'min' => -3600, 'max' => 3600, 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('graphic', 'ID'),
            'user_id' => Yii::t('graphic', 'User'),
            'source_file' => Yii::t('graphic', 'Source File'),
            'modified_file' => Yii::t('graphic', 'Modified File'),
            'created_at' => Yii::t('graphic', 'Created At'),
            'isTransverseImage' => Yii::t('graphic', 'Transverse'),
            'isScaleImage' => Yii::t('graphic', 'Scale'),
            'scaleImageWidth' => Yii::t('graphic', 'Width'),
            'scaleImageHeight' => Yii::t('graphic', 'Height'),
            'isAdaptiveBlurImage' => Yii::t('graphic', 'Adaptive Blur'),
            'adaptiveBlurImageRadius' => Yii::t('graphic', 'Radius'),
            'adaptiveBlurImageSigma' => Yii::t('graphic', 'Sigma'),
            'isBlurImage' => Yii::t('graphic', 'Blur'),
            'blurImageRadius' => Yii::t('graphic', 'Radius'),
            'blurImageSigma' => Yii::t('graphic', 'Sigma'),
            'isAdaptiveSharpenImage' => Yii::t('graphic', 'Adaptive Sharpen'),
            'adaptiveSharpenImageRadius' => Yii::t('graphic', 'Radius'),
            'adaptiveSharpenImageSigma' => Yii::t('graphic', 'Sigma'),
            'isAddNoiseImage' => Yii::t('graphic', 'Noise'),
            'noiseType' => Yii::t('graphic', 'Noise Type'),
            'isSpreadImage' => Yii::t('graphic', 'Spread'),
            'spreadRadius' => Yii::t('graphic', 'Radius'),
            'isSwirlImage' => Yii::t('graphic', 'Swirl'),
            'swirlSwirl' => Yii::t('graphic', 'Swirl'),
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at'],
                    self::EVENT_BEFORE_UPDATE => null,
                ],
            ],
        ];
    }

    public function beforeSave($insert)
    {
        (new File(self::FILE_SOURCE_STORAGE_URL))->uploadForModel($this, 'sourceFile', 'source_file', null, true);

        $this->user_id = \Yii::$app->user->id;
        $this->modifiedFileName = $this->generateModifiedFileName($this->source_file);
        $this->modified_file = self::FILE_MODIFIED_STORAGE_URL . $this->modifiedFileName;

        return parent::beforeSave( $insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $this->runImagick();

        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeDelete()
    {
        if (file_exists(\Yii::getAlias('@webroot') . $this->source_file)) {
            unlink(\Yii::getAlias('@webroot') . $this->source_file);
        }
        if (file_exists(\Yii::getAlias('@webroot') . $this->modified_file)) {
            unlink(\Yii::getAlias('@webroot') . $this->modified_file);
        }

        return parent::beforeDelete();
    }

    public function setSourceFile()
    {
        $this->sourceFile = UploadedFile::getInstance($this, 'sourceFile');
    }

    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    public function getPathDownloadImage($model, $modifiedImage = false)
    {
        if ($modifiedImage) {
            $rootPath = Yii::getAlias(('@app/web') . $model->modified_file);
        } else {
            $rootPath = Yii::getAlias(('@app/web') . $model->source_file);
        }

        return $rootPath;
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    private function runImagick()
    {
        $imagick = new Imagick(\Yii::getAlias('@webroot') . $this->source_file);
        if($this->isTransverseImage) {
            $imagick->flopimage();
        }
        if($this->isScaleImage) {
            $imagick->adaptiveResizeImage($this->scaleImageWidth, $this->scaleImageHeight);
        }
        if($this->isAdaptiveBlurImage) {
            $imagick->adaptiveBlurImage($this->adaptiveBlurImageRadius, $this->adaptiveBlurImageSigma);
        }
        if($this->isBlurImage) {
            $imagick->blurImage($this->blurImageRadius, $this->blurImageSigma);
        }
        if($this->isAdaptiveSharpenImage) {
            $imagick->adaptiveSharpenImage($this->adaptiveSharpenImageRadius, $this->adaptiveSharpenImageSigma);
        }
        if($this->isAddNoiseImage) {
            $imagick->addNoiseImage($this->noiseType);
        }
        if($this->isSpreadImage) {
            $imagick->spreadImage($this->spreadRadius);
        }
        if($this->isSwirlImage) {
            $imagick->swirlImage($this->swirlSwirl);
        }

        $directoryPath = Yii::$app->basePath . '/web' . self::FILE_MODIFIED_STORAGE_URL;
        if (!file_exists($directoryPath)) {
            FileHelper::createDirectory($directoryPath, $mode = 0755, $recursive = true);
        }

        $imagick->writeImage($directoryPath . $this->modifiedFileName);
    }

    private function generateModifiedFileName($filePath)
    {
        return 'm-' . substr($filePath, strrpos($this->source_file, "/") + 1);
    }

}