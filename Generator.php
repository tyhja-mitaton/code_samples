<?php

namespace app\models\graphic;

use app\components\File;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * This is the model class for table "generator".
 *
 * @property int $id
 * @property string $icon
 * @property string $screenshots
 * @property int $weight_limit
 * @property string $icon_unique
 * @property string $screenshots_unique
 */
class Generator extends \yii\db\ActiveRecord
{
    const PROMO_FILE_PATH = "storage/graphic/generator/";
    const ICON_TYPE = "icon/";
    const SCREENSHOTS_TYPE = "screenshots/";
    const SCREENSHOTS_UNIQUE_TYPE = "screenshots/unique/";
    const ICON_UNIQUE_TYPE = "icon/unique/";

    const DEFAULT_EXTENSION = 'jpg';
    const EFFECT_RATIO = 0.2;//размер эффекта относительно исходного изображения

    public $iconImage;
    public $screenshotsImages;
    public $screenshotsBorderTemplateId;
    public $screenshotsTextTemplateId;
    public $screenshotsEffectTemplateId;
    public $screenshotsBackgroundTemplateId;
    public $iconBorderTemplateId;
    public $iconBackgroundTemplateId;
    public $iconEffectTemplateId;

    private $deletedKeyScreen = null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'generator';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['screenshots', 'screenshots_unique'], 'safe'],
            [['weight_limit', 'screenshotsBorderTemplateId', 'screenshotsBackgroundTemplateId', 'iconBorderTemplateId', 'iconBackgroundTemplateId'], 'integer'],
            [['icon', 'icon_unique'], 'string', 'max' => 255],
            [['iconImage', 'screenshotsImages'], 'file', 'skipOnEmpty' => true, 'extensions' => 'jpg, jpeg, png', 'maxFiles' => 10],
            [['screenshotsEffectTemplateId', 'iconEffectTemplateId'], 'limitSelection', 'params' => ['limit' => 5]],
            [['screenshotsTextTemplateId'], 'limitSelection', 'params' => ['limit' => 3]]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'icon' => Yii::t('app', 'Icon'),
            'screenshots' => Yii::t('app', 'Screenshots'),
            'weight_limit' => Yii::t('app', 'Weight Limit'),
            'icon_unique' => Yii::t('app', 'Icon Unique'),
            'screenshots_unique' => Yii::t('app', 'Screenshots Unique'),
        ];
    }

    public function limitSelection($attribute,$params)
    {
        if(count($this->$attribute)>$params['limit']){
            $this->addError($attribute,"You are only allowed to select {$params['limit']} or less items for ".$attribute);
        }
    }

    public function beforeSave($insert)
    {
        $this->uploadIcon();
        $this->uploadScreenshots();

        static $filePath;
        if (array_key_exists('icon', $this->dirtyAttributes) && isset($this->oldAttributes['icon'])) {
            $filePath = $this->oldAttributes['icon'];
        } elseif (array_key_exists('screenshots', $this->dirtyAttributes) && isset($this->oldAttributes['screenshots']) && ($this->deletedKeyScreen != null)) {
            $old = json_decode($this->oldAttributes['screenshots'], true);
            $filePath = $old[$this->deletedKeyScreen]['url'];
        }
        if ($filePath && file_exists(\Yii::$app->basePath . '/web/' . $filePath)) {
            unlink(\Yii::$app->basePath . '/web/' . $filePath);
        }

        return parent::beforeSave($insert);
    }

    private function uploadIcon()
    {
        $uploadIcon = UploadedFile::getInstance($this, 'iconImage');
        if(!empty($uploadIcon)){
            $upload = new File($this->getFilePath(self::ICON_TYPE));
            $image = $upload->uploadImage($uploadIcon);
            $this->icon = $image->path;
        }
        if(!empty($this->icon)) {
            $pathArray = $this->prepareDirectory(self::ICON_UNIQUE_TYPE, $this->getIconExtension());
            $data = $this->icon;
            $weightTotal = 0;
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 512);
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512);
            if($this->iconBackgroundTemplateId){
                $template = GraphicMaterial::findOne($this->iconBackgroundTemplateId);
                $this->mergeTemplate($template, 'mergeBackground', $data, $weightTotal, $pathArray);
            }
            if($this->iconBorderTemplateId){
                $template = GraphicMaterial::findOne($this->iconBorderTemplateId);
                $this->mergeTemplate($template, 'mergeBorder', $data, $weightTotal, $pathArray);
            }
            if($this->iconEffectTemplateId){
                $templates = GraphicMaterial::find()->where(['IN', 'id', $this->iconEffectTemplateId])->all();
                $this->mergeTemplates($templates, 'mergeEffect', $data, $weightTotal, $pathArray);
            }
            $this->icon_unique = $data;
        }
    }

    private function uploadScreenshots()
    {
        $uploadScreenshots = UploadedFile::getInstances($this, 'screenshotsImages');
        $data = null;
        if (!empty($uploadScreenshots)) {
            $dataOld = json_decode($this->screenshots, true);
            foreach ($uploadScreenshots as $key => $uploadImg) {
                $upload = new File($this->getFilePath(self::SCREENSHOTS_TYPE));
                $image = $upload->uploadImage($uploadImg);
                $dataNew[$key]['url'] = $image->path;
                $dataNew[$key]['original_name'] = $image->original_name;
            }
            $data = $dataOld ? array_merge($dataOld, $dataNew) : $dataNew;
            $this->screenshots = json_encode($data, JSON_FORCE_OBJECT);
        }
        $screenshotArray = json_decode($this->screenshots, true);
        if(!empty($screenshotArray)) {
            $pathArray = $this->prepareDirectory(self::SCREENSHOTS_UNIQUE_TYPE, self::DEFAULT_EXTENSION);
            $data = $screenshotArray;
            $weightTotal = 0;
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 512);
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512);
            if($this->screenshotsBackgroundTemplateId){
                $template = GraphicMaterial::findOne($this->screenshotsBackgroundTemplateId);
                $this->mergeTemplate($template, 'mergeBackground', $data, $weightTotal, $pathArray);
            }
            if($this->screenshotsBorderTemplateId){
                $template = GraphicMaterial::findOne($this->screenshotsBorderTemplateId);
                $this->mergeTemplate($template, 'mergeBorder', $data, $weightTotal, $pathArray);
            }
            if($this->screenshotsEffectTemplateId){
                $templates = GraphicMaterial::find()->where(['IN', 'id', $this->screenshotsEffectTemplateId])->all();
                $this->mergeTemplates($templates, 'mergeEffect', $data, $weightTotal, $pathArray);
            }
            if($this->screenshotsTextTemplateId){
                $templates = GraphicMaterial::find()->where(['IN', 'id', $this->screenshotsTextTemplateId])->all();
                $this->mergeTemplates($templates, 'mergeText', $data, $weightTotal, $pathArray);
            }
            $this->screenshots_unique = json_encode($data, JSON_FORCE_OBJECT);
        }
    }

    public function getFilePath($typeFolder = '')
    {
        return self::PROMO_FILE_PATH . $this->id . '/' . $typeFolder;
    }

    public function prepareScreenshotsImages()
    {
        if ($this->screenshots == null) {
            return [];
        }
        foreach (json_decode($this->screenshots, JSON_FORCE_OBJECT) as $screen) {
            $preparing[] = Html::img('/'.$screen['url'], ['height' => 140]);
        }
        return $preparing;
    }

    public function prepareScreenshotsProperty()
    {
        $screenshots = json_decode($this->screenshots, true);
        if ($screenshots == null) {
            return [['caption' => null, 'size' => null]];
        }
        foreach ($screenshots as $key => $value) {
            $preparing[$key]['caption'] =$value['original_name'];
            $preparing[$key]['url'] = Url::to(['image-delete', 'id' => $this->id, 'attribute' => 'screenshots', 'keyScreen' => $key], true);
            $preparing[$key]['size'] = File::getFileSize($value['url']);
        }

        return $preparing;
    }

    private function mergeBorder(GraphicMaterial $template, $data, $pathArray)
    {
        $withinBorder = json_decode($template->data)->flag;
        if(is_array($data)) {
            $dataNew = [];
            foreach ($data as $key => $screenshot) {
                $path = $pathArray[0].$key.'$'.$pathArray[1];
                $screenshotUrl = strpos($screenshot['url'], Yii::getAlias('@webroot')) !== false ? $screenshot['url'] :
                    Yii::getAlias('@webroot') . '/' . $screenshot['url'];
                $imagickScreenshot = new \Imagick($screenshotUrl);
                $imagickBorder = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);
                $this->generateImageBorder($imagickBorder, $imagickScreenshot, $path, $withinBorder);
                $imagickBorder->clear();
                $imagickScreenshot->clear();
                $dataNew[$key]['url'] = $path;
                $dataNew[$key]['original_name'] = $key.'$'.$pathArray[1];
            }
            return $dataNew;
        }else{
            $path = implode('', $pathArray);
            $iconUrl = strpos($data, Yii::getAlias('@webroot')) !== false ? $data :
                Yii::getAlias('@webroot') . '/' . $data;
            $imagickIcon = new \Imagick($iconUrl);
            $imagickBorder = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);
            $this->generateImageBorder($imagickBorder, $imagickIcon, $path);
            $imagickIcon->clear();
            $imagickBorder->clear();

            return $path;
        }
    }

    private function mergeText(GraphicMaterial $template, $data, $pathArray)
    {
        $allScreenshots = json_decode($template->data)->flag;
        if(is_array($data)) {
            return $this->generateImageBackground($data, $template, $pathArray, $allScreenshots, true);
        }else{
            return $data;
        }
    }

    private function mergeEffect(GraphicMaterial $template, $data, $pathArray)
    {
        if(is_array($data)) {
            $dataNew = [];
            foreach ($data as $key => $screenshot) {
                $path = $pathArray[0].$key.'$'.$pathArray[1];
                $this->applyEffect($screenshot['url'], $path, $template);
                $dataNew[$key]['url'] = $path;
                $dataNew[$key]['original_name'] = $key.'$'.$pathArray[1];
            }
            return $dataNew;
        }else {
            $path = implode('', $pathArray);
            $this->applyEffect($data, $path, $template);
            return $path;
        }
    }

    private function applyEffect($fileUrl, $path, GraphicMaterial $template)
    {
        $url = strpos($fileUrl, Yii::getAlias('@webroot')) !== false ? $fileUrl :
            Yii::getAlias('@webroot') . '/' . $fileUrl;
        $imagickFile = new \Imagick($url);
        $imagickEffect = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);
        $draw = new \ImagickDraw();
        $effectWidth = $imagickEffect->getImageWidth() / $imagickFile->getImageWidth() > self::EFFECT_RATIO ? $imagickFile->getImageWidth() * self::EFFECT_RATIO : $imagickEffect->getImageWidth();
        $effectHeight = $imagickEffect->getImageHeight() / $imagickFile->getImageHeight() > self::EFFECT_RATIO ? $imagickFile->getImageHeight() * self::EFFECT_RATIO: $imagickEffect->getImageHeight();
        $coordX = random_int(0, $imagickFile->getImageWidth()) - $effectWidth/2;
        $coordY = random_int(0, $imagickFile->getImageHeight()) - $effectHeight/2;
        $draw->composite(\Imagick::COMPOSITE_DISSOLVE, $coordX, $coordY, $effectWidth, $effectHeight, $imagickEffect);
        $imagickFile->drawImage($draw);
        $imagickFile->writeImage($path);
        $imagickFile->clear();
        $imagickEffect->clear();
        $draw->clear();
    }

    private function mergeBackground(GraphicMaterial $template, $data, $pathArray)
    {
        $allScreenshots = json_decode($template->data)->flag;
        if(is_array($data)) {
            return $this->generateImageBackground($data, $template, $pathArray, $allScreenshots);
        }else{
            $path = implode('', $pathArray);
            $iconUrl = strpos($data, Yii::getAlias('@webroot')) !== false ? $data :
                Yii::getAlias('@webroot') . '/' . $data;
            $imagickIcon = new \Imagick($iconUrl);
            $imagickBackground = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);
            $draw = new \ImagickDraw();
            $draw->composite(\Imagick::COMPOSITE_DISSOLVE, 0, 0, $imagickIcon->getImageWidth(), $imagickIcon->getImageHeight(), $imagickIcon);
            $imagickBackground->scaleImage($imagickIcon->getImageWidth(), $imagickIcon->getImageHeight());
            $imagickBackground->drawImage($draw);
            $imagickBackground->writeImage($path);
            $imagickIcon->clear();
            $imagickBackground->clear();
            $draw->clear();

            return $path;
        }
    }

    private function getIconExtension()
    {
        $matches = [];
        if(!empty($this->icon)) {
            preg_match('/([^.]*)$/', $this->icon, $matches);
        }
        return $this->iconImage ? $this->iconImage->extension : $matches[0];
    }

    public function getPathDownloadUniqueScreenshots($id)
    {
        if (!$this->screenshots_unique) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $rootPath = Yii::getAlias('@webroot') . '/' . $this->getFilePath($this::SCREENSHOTS_UNIQUE_TYPE);
        if (!file_exists($rootPath)) {
            throw new NotFoundHttpException('The requested files do not exist.');
        }
        $folderPath = ($this::PROMO_FILE_PATH . $this->id . '/' . $this::SCREENSHOTS_UNIQUE_TYPE);
        $zipPath = $folderPath . "/unique_screenshots_{$id}.zip";

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        if(iterator_count($files) <= 2){ //пустая директория содержит /. и /..
            return ['zip' => null];
        }
        $zip = new \ZipArchive();
        $zip->open($folderPath . "/unique_screenshots_{$id}.zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath));
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        return ['zip' => $zipPath];
    }

    public function deleteImage($attribute, $keyScreen = null)
    {
        if ($attribute == "screenshots") {
            $this->deletedKeyScreen = $keyScreen;

            $screenshots = json_decode($this->screenshots, true);
            unset($screenshots[$keyScreen]);
            sort($screenshots);
            $this->screenshots = !empty($screenshots) ? json_encode($screenshots, JSON_FORCE_OBJECT) : null;
            $this->save(false, ['screenshots']);
        } elseif ($attribute == "icon") {
            $this->icon = null;
            $this->save(false, ['icon']);
        }
    }

    private function mergeTemplates($templates, $mergeType, &$data, &$weightTotal, $pathArray)
    {
        foreach ($templates as $template){
            $weightTotal += $template->weight;
            if($weightTotal <= $this->weight_limit){
                $data = $this->$mergeType($template, $data, $pathArray);
            }
        }
    }

    private function mergeTemplate($template, $mergeType, &$data, &$weightTotal, $pathArray)
    {
        if($template){
            $weightTotal += $template->weight;
            if($weightTotal <= $this->weight_limit){
                $data = $this->$mergeType($template, $data, $pathArray);
            }
        }
    }

    private function prepareDirectory($typeFolder, $extension):array
    {
        $uniqueFilePath = Yii::getAlias('@webroot') . '/' . $this->getFilePath($typeFolder);
        if (!file_exists($uniqueFilePath)) {
            FileHelper::createDirectory($uniqueFilePath, $mode = 0755, $recursive = true);
        }
        $uniqueFileName = Yii::$app->security->generateRandomString(35) . "." . $extension;
        $pathArray = [$uniqueFilePath, $uniqueFileName];
        File::clearDir($this->getFilePath($typeFolder));

        return $pathArray;
    }

    private function generateImageBorder(\Imagick $imagickBorder, \Imagick $imagickScreenshot, $path, $withinBorder = false)
    {
        if($withinBorder) {
            if($imagickBorder->getImageAlphaChannel()) {
                $imageIterator = $imagickBorder->getPixelIterator();
                $rows = [];$columns = [];
                foreach ($imageIterator as $row => $pixels) {
                    foreach ($pixels as $column => $pixel) {
                        $color = $pixel->getColor();
                        if($color['a'] == 0) {
                            $rows[] = $row;
                            $columns[] = $column;
                            $rows = [min($rows), max($rows)]; //сокращает потребление памяти
                            $columns = [min($columns), max($columns)];
                        }
                    }
                }
                $minX = min($columns); $minY = min($rows);
                $maxX = max($columns); $maxY = max($rows);
                $transparentWidth = $maxX - $minX;
                $transparentHeight = $maxY - $minY;

                $imagickScreenshot->scaleImage($transparentWidth, $transparentHeight);
                $draw = new \ImagickDraw();
                $draw->composite(\Imagick::COMPOSITE_DEFAULT, $minX, $minY, $transparentWidth, $transparentHeight, $imagickScreenshot);
                $imagickBorder->drawImage($draw);
                $imagickBorder->writeImage($path);
                $draw->clear();
            }
        }else{
            $draw = new \ImagickDraw();
            $draw->composite(\Imagick::COMPOSITE_DISSOLVE, 0, 0, $imagickScreenshot->getImageWidth(), $imagickScreenshot->getImageHeight(), $imagickBorder);
            $imagickScreenshot->drawImage($draw);
            $imagickScreenshot->writeImage($path);
            $draw->clear();
        }
    }

    private function generateImageBackground($data, $template, $pathArray, $allScreenshots, $text = false):array
    {
        $dataNew = [];
        if ($allScreenshots) {
            $imagickBackground = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);

            $imagickScreenshots = [];$heights = [];$widths = [];

            foreach ($data as $key => $screenshot) {
                $screenshotUrl = strpos($screenshot['url'], Yii::getAlias('@webroot')) !== false ? $screenshot['url'] :
                    Yii::getAlias('@webroot') . '/' . $screenshot['url'];
                $imagickScreenshot = new \Imagick($screenshotUrl);
                $heights[] = $imagickScreenshot->getImageHeight();
                $imagickScreenshots[] = $imagickScreenshot;
            }
            $compoundHeight = min($heights);
            foreach ($imagickScreenshots as $imagickScreenshot) {
                $imagickScreenshot->scaleImage(0, $compoundHeight);
                $widths[] = $imagickScreenshot->getImageWidth();
            }
            $compoundWidth = array_sum($widths);
            $imagick = new \Imagick();
            $imagick->newImage($compoundWidth, $compoundHeight, 'rgba(6,6,6,0.6)');
            $currentWidth = 0;
            foreach ($imagickScreenshots as $key => $imagickScreenshot) {
                $imagickScreenshot->scaleImage(0, $compoundHeight);
                $drawScr = new \ImagickDraw();
                $drawScr->composite(\Imagick::COMPOSITE_DEFAULT, $currentWidth, 0, $imagickScreenshot->getImageWidth(), $imagickScreenshot->getImageHeight(), $imagickScreenshot);
                $currentWidth += $imagickScreenshot->getImageWidth();
                $imagick->drawImage($drawScr);
                $imagickScreenshot->clear();
                $drawScr->clear();
            }
            $drawBackground = new \ImagickDraw();
            $drawBackground->composite(\Imagick::COMPOSITE_DISSOLVE, 0, 0, $imagick->getImageWidth(), $imagick->getImageHeight(), !$text ? $imagick : $imagickBackground);
            if(!$text) {
                $imagickBackground->scaleImage($imagick->getImageWidth(), $imagick->getImageHeight());
                $imagickBackground->drawImage($drawBackground);
                $imagickBackground->setImageFormat('png');
            }else{
                $imagick->drawImage($drawBackground);
                $imagick->setImageFormat('png');
            }
            $currentWidth = 0;
            foreach ($widths as $key => $width) {
                $path = $pathArray[0] . $key . '$' . $pathArray[1];
                $imagickCropped = !$text ? clone $imagickBackground : clone $imagick;
                $imagickCropped->cropImage($width, $imagick->getImageHeight(), $currentWidth, 0);
                $imagickCropped->writeImage($path);
                $dataNew[$key]['url'] = $path;
                $dataNew[$key]['original_name'] = $key . '$' . $pathArray[1];
                $currentWidth += $width;
            }
            $imagickBackground->clear();
            $imagick->clear();
            $drawBackground->clear();
        } else {
            foreach ($data as $key => $screenshot) {
                $path = $pathArray[0] . $key . '$' . $pathArray[1];
                $screenshotUrl = strpos($screenshot['url'], Yii::getAlias('@webroot')) !== false ? $screenshot['url'] :
                    Yii::getAlias('@webroot') . '/' . $screenshot['url'];
                $imagickScreenshot = new \Imagick($screenshotUrl);
                $imagickBackground = new \Imagick(Yii::getAlias('@webroot') . '/' . json_decode($template->data)->path);
                $draw = new \ImagickDraw();
                $draw->composite(\Imagick::COMPOSITE_DISSOLVE, 0, 0, $imagickScreenshot->getImageWidth(), $imagickScreenshot->getImageHeight(), !$text ? $imagickScreenshot : $imagickBackground);
                if(!$text) {
                    $imagickBackground->scaleImage($imagickScreenshot->getImageWidth(), $imagickScreenshot->getImageHeight());
                    $imagickBackground->drawImage($draw);
                    $imagickBackground->writeImage($path);
                }else{
                    $imagickScreenshot->drawImage($draw);
                    $imagickScreenshot->writeImage($path);
                }
                $dataNew[$key]['url'] = $path;
                $dataNew[$key]['original_name'] = $key . '$' . $pathArray[1];
                $imagickScreenshot->clear();
                $imagickBackground->clear();
                $draw->clear();
            }
        }

        return $dataNew;
    }
}
