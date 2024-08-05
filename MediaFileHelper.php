<?php

namespace app\components\helpers;

use app\components\Logger;
use FFMpeg\FFMpeg;

class MediaFileHelper
{
    const TEXT_DIRECTION_LEFT = 1;
    const TEXT_DIRECTION_RIGHT = 2;
    const TEXT_DIRECTION_CENTER = 4;
    const TEXT_POSITION_UP = 8;
    const TEXT_POSITION_DOWN = 16;
    const TEXT_POSITION_CENTER = 32;

    const RED_COLOR_CHANNEL = 0.299;
    const GREEN_COLOR_CHANNEL = 0.587;
    const BLUE_COLOR_CHANNEL = 0.114;

    /**
     * Adds text to an image with a contrasting background
     * @param string $filePath
     * @param string $text Text to be added
     * @param string $outputPath Path to save the resulting image
     * @param string $fontPath Path to TrueType font (.ttf)
     * @param int $direction Text placement direction
     * @param int $fontSize Font size
     * @param int $xOffset Horizontal offset of the text from the left edge
     * @param int $yOffset Vertical offset of the text from the top edge
     * @return bool
     */
    public static function addTextToImage(
        string $filePath,
        string $text,
        string $outputPath,

        int    $direction = self::TEXT_DIRECTION_LEFT | self::TEXT_POSITION_DOWN,
        string $fontPath = '/web/local/font/Helvetica.ttf',

        int    $fontSize = 15,
        int    $xOffset = 10,
        int    $yOffset = 10
    ): bool
    {
        $fontPath = \Yii::$app->basePath . '/' . ltrim($fontPath, '/');

        $image = self::getImageObject($filePath);
        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $textSize = self::getTextSize($text, $fontPath, $fontSize);
        $optimalTextSize = self::findOptimalFontSize($textSize, $width, $height);
        $yDefaultOffset = $smallSizeOffset = 5;
        $isUp = ($direction & self::TEXT_POSITION_UP) !== 0;
        if($optimalTextSize <= 7) {$xOffset = $yOffset = $height/10;$yDefaultOffset = 0;}
        $textCoords = self::calculateCoordinates($direction, $width, $height, $text, $fontPath, $optimalTextSize, $xOffset, $yOffset);

        $xFinal = max(0, min($textCoords['x'], $width - 1));
        $yFinal = max(0, min($textCoords['y'], $height - 1)) + $yDefaultOffset;
        $bgColor = imagecolorat($image, $xFinal, $yFinal);

        $l = self::calculateBgColor($bgColor);
        $textColor = self::getImageTextColor($image, $l);
        $strokesColor = self::getTextStrokesColor($image, $l);

        $textOffset = 0; $textYOffset = 0;
        if($optimalTextSize <= 7) {
            $counter = 0;
            do {
                $counter++;
                $longestTextBox = imagettfbbox($optimalTextSize, 0, $fontPath, self::getLongestString($text));
                $longestTextWidth = abs($longestTextBox[2] - $longestTextBox[0]);
                $optimalTextBox = imagettfbbox($optimalTextSize, 0, $fontPath, $text);
                $optimalTextHeight = abs($optimalTextBox[7] - $optimalTextBox[1]);
                if($xFinal + $longestTextWidth > $width - 1) {
                    $textArray = explode(' ', $text);
                    $textStart = implode(' ', explode(' ', $text, -2 * $counter));
                    $textRest = implode(' ', array_slice($textArray, -2 * $counter));
                    $text = "$textStart\r\n$textRest";
                    $isLeft = ($direction & self::TEXT_DIRECTION_LEFT) !== 0;
                    $isRight = ($direction & self::TEXT_DIRECTION_RIGHT) !== 0;
                    $isCenterX = ($direction & self::TEXT_DIRECTION_CENTER) !== 0 && !($isLeft || $isRight);
                    if($isCenterX) {
                        $textRestBox = imagettfbbox($optimalTextSize, 0, $fontPath, $textRest);
                        $textOffset = (int)(abs($textRestBox[2] - $textRestBox[0])/2);
                    }
                }
            }while($xFinal + $longestTextWidth > $width - 1);
            if($isUp) {
                do {
                    if($textCoords['y'] + $optimalTextHeight - $textYOffset > $height - 1) {
                        $textYOffset++;
                    }
                }while($textCoords['y'] + $optimalTextHeight - $textYOffset > $height - 1);
            }
            $y1 = $textCoords['y'] - (int)($yOffset/2) - $smallSizeOffset - $textYOffset;
            $y2 = $textCoords['y'] + $optimalTextHeight - $textYOffset - $smallSizeOffset;

            imagefilledrectangle($image, $xFinal + $textOffset, $y1, $xFinal + $longestTextWidth + $textOffset, $y2, $strokesColor);
        }
        imagettftext($image, $optimalTextSize, 0, $xFinal + $textOffset, $yFinal - 1 - $textYOffset, $strokesColor, $fontPath, $text);
        imagettftext($image, $optimalTextSize, 0, $xFinal + $textOffset - 1, $yFinal - $textYOffset, $strokesColor, $fontPath, $text);
        imagettftext($image, $optimalTextSize, 0, $xFinal + $textOffset, $yFinal + 1 - $textYOffset, $strokesColor, $fontPath, $text);
        imagettftext($image, $optimalTextSize, 0, $xFinal + $textOffset + 1, $yFinal - $textYOffset, $strokesColor, $fontPath, $text);
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        imagettftext($image, $optimalTextSize, 0, $xFinal + $textOffset, $yFinal - $textYOffset, $textColor, $fontPath, $text);

        $result = self::saveImageFromObject($image, $filePath, $outputPath);
        imagedestroy($image);

        return $result;
    }

    public static function getLongestString($text): string
    {
        $textArray = explode("\n", $text);
        $maxString = '';
        foreach ($textArray as $item) {
            if(mb_strlen($item) > mb_strlen($maxString)) {
                $maxString = $item;
            }
        }
        return $maxString;
    }

    /**
     * @param \GdImage $image
     * @param int $l
     * @return int|false
     */
    public static function getImageTextColor(\GdImage $image, int $l): bool|int
    {
        if ($l > 128) {
            $textColor = imagecolorallocate($image, 0, 0, 0);
        } else {
            $textColor = imagecolorallocate($image, 255, 255, 255);
        }

        return $textColor;
    }

    public static function getTextStrokesColor(\GdImage $image, int $l): bool|int
    {
        if ($l > 128) {
            $strokesColor = imagecolorallocate($image, 255, 255, 255);
        } else {
            $strokesColor = imagecolorallocate($image, 0, 0, 0);
        }

        return $strokesColor;
    }

    /**
     * @param int $bgColor
     * @return int
     */
    public static function calculateBgColor(int $bgColor): int
    {
        $r = ($bgColor >> 16) & 0xFF;
        $g = ($bgColor >> 8) & 0xFF;
        $b = $bgColor & 0xFF;

        return (int)(self::RED_COLOR_CHANNEL * $r + self::GREEN_COLOR_CHANNEL * $g + self::BLUE_COLOR_CHANNEL * $b);
    }

    /**
     * @param $direction
     * @param $width
     * @param $height
     * @param $text
     * @param $fontPath
     * @param $fontSize
     * @param $xOffset
     * @param $yOffset
     * @return int[]
     */
    public static function calculateCoordinates($direction, $width, $height, $text, $fontPath, $fontSize, $xOffset, $yOffset): array
    {
        $isLeft = ($direction & MediaFileHelper::TEXT_DIRECTION_LEFT) !== 0;
        $isRight = ($direction & MediaFileHelper::TEXT_DIRECTION_RIGHT) !== 0;
        $isUp = ($direction & MediaFileHelper::TEXT_POSITION_UP) !== 0;
        $isDown = ($direction & MediaFileHelper::TEXT_POSITION_DOWN) !== 0;
        $isCenterX = ($direction & MediaFileHelper::TEXT_DIRECTION_CENTER) !== 0 && !($isLeft || $isRight);
        $isCenterY = ($direction & MediaFileHelper::TEXT_POSITION_CENTER) !== 0 && !($isUp || $isDown);

        $textSize = self::getTextSize($text, $fontPath, $fontSize);

        $textWidth = $textSize['width'];
        $textHeight = $textSize['height'];

        $coordinates = [];

        if ($isLeft && $isDown) {
            $coordinates = ['x' => $xOffset, 'y' => $height - $textHeight - $yOffset - $fontSize];
        }
        if ($isLeft && $isUp) {
            $coordinates = ['x' => $xOffset, 'y' => $yOffset + $fontSize];
        }
        if ($isLeft && $isCenterY) {
            $coordinates = ['x' => $xOffset, 'y' => ($height - $textHeight) / 2 + $fontSize / 2];
        }
        if ($isRight && $isDown) {
            $coordinates = ['x' => $width - $textWidth - $xOffset, 'y' => $height - $textHeight - $yOffset];
        }
        if ($isRight && $isUp) {
            $coordinates = ['x' => $width - $textWidth - $xOffset, 'y' => $yOffset + $fontSize];
        }
        if ($isRight && $isCenterY) {
            $coordinates = ['x' => $width - $textWidth - $xOffset, 'y' => ($height - $textHeight) / 2];
        }
        if ($isCenterX && $isDown) {
            $coordinates = ['x' => ($width - $textWidth) / 2, 'y' => $height - $textHeight - $yOffset];
        }
        if ($isCenterX && $isUp) {
            $coordinates = ['x' => ($width - $textWidth) / 2, 'y' => $yOffset + $fontSize];
        }
        if ($isCenterX && $isCenterY) {
            $coordinates = ['x' => ($width - $textWidth) / 2, 'y' => ($height - $textHeight) / 2];
        }

        if ($coordinates['x'] < 0) $coordinates['x'] = 0;
        if ($coordinates['y'] < 0) $coordinates['y'] = 0;

        return [
            'x' => (int)$coordinates['x'],
            'y' => (int)$coordinates['y']
        ];
    }

    public static function calculateCoordinatesToVideo($direction, $xOffset, $yOffset): array
    {
        $isLeft = ($direction & MediaFileHelper::TEXT_DIRECTION_LEFT) !== 0;
        $isRight = ($direction & MediaFileHelper::TEXT_DIRECTION_RIGHT) !== 0;
        $isUp = ($direction & MediaFileHelper::TEXT_POSITION_UP) !== 0;
        $isDown = ($direction & MediaFileHelper::TEXT_POSITION_DOWN) !== 0;
        $isCenterX = ($direction & MediaFileHelper::TEXT_DIRECTION_CENTER) !== 0 && !($isLeft || $isRight);
        $isCenterY = ($direction & MediaFileHelper::TEXT_POSITION_CENTER) !== 0 && !($isUp || $isDown);

        //x=(w-tw)/2:y=(h-th)/2 = center
        if ($isLeft && $isDown) {
            return ['x' => $xOffset, 'y' => '(h-th)-' . $yOffset];
        }
        if ($isLeft && $isUp) {
            return ['x' => $xOffset, 'y' => $yOffset];
        }
        if ($isLeft && $isCenterY) {
            return ['x' => $xOffset, 'y' => '(h-th)/2'];
        }
        if ($isRight && $isDown) {
            return ['x' => '(w-tw)-' . $xOffset, 'y' => '(h-th)-' . $yOffset];
        }
        if ($isRight && $isUp) {
            return ['x' => '(w-tw)-' . $xOffset, 'y' => $yOffset];
        }
        if ($isRight && $isCenterY) {
            return ['x' => '(w-tw)-' . $xOffset, 'y' => '(h-th)/2'];
        }
        if ($isCenterX && $isDown) {
            return ['x' => '(w-tw)/2', 'y' => '(h-th)-' . $yOffset];
        }
        if ($isCenterX && $isUp) {
            return ['x' => '(w-tw)/2', 'y' => $yOffset];
        }
        if ($isCenterX && $isCenterY) {
            return ['x' => '(w-tw)/2', 'y' => '(h-th)/2'];
        }

        return ['x' => 0, 'y' => 0];
    }

    /**
     * @param string $filePath
     * @return \GdImage|null
     */
    public static function getImageObject(string $filePath): ?\GdImage
    {
        $type = mime_content_type($filePath);

        if (str_ends_with($type, 'jpeg')) {
            $image = \imagecreatefromjpeg($filePath);
        } elseif (str_ends_with($type, 'png')) {
            $image = \imagecreatefrompng($filePath);
        } elseif (str_ends_with($type, 'gif')) {
            $image = \imagecreatefromgif($filePath);
        } else {
            return NULL;
        }

        return $image;
    }

    /**
     * @param \GdImage $image
     * @param string $filePath
     * @param string $outputPath
     * @return bool
     */
    public static function saveImageFromObject(\GdImage $image, string $filePath, string $outputPath): bool
    {
        try {
            $type = mime_content_type($filePath);
            if (str_ends_with($type, 'jpeg')) {
                return \imagejpeg($image, $outputPath);
            } elseif (str_ends_with($type, 'png')) {
                return \imagepng($image, $outputPath);
            } elseif (str_ends_with($type, 'gif')) {
                return \imagegif($image, $outputPath);
            }
        } catch (\Throwable $e) {
            \Yii::error($e);
        }

        return false;
    }

    /**
     * @param string $filePath
     * @return int[]
     */
    public static function getVideoSize(string $filePath): array
    {
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open($filePath);

        $dim = $video->getStreams()->videos()->first()->getDimensions();

        return [
            'width' => (int)$dim?->getWidth(),
            'height' => (int)$dim?->getHeight()
        ];
    }

    /**
     * @param string $text
     * @param string $fontPath
     * @param int $fontSize
     * @return int[]
     */
    public static function getTextSize(string $text, string $fontPath, int $fontSize): array
    {
        $font = imagettfbbox($fontSize, 0, $fontPath, $text);
        $width = abs($font[2] - $font[0]);
        $height = abs($font[7] - $font[1]);

        return [
            'width' => (int)$width,
            'height' => (int)$height
        ];
    }

    /**
     * Adds text to a video
     * @param string $filePath
     * @param string $text Text to be added
     * @param string $outputPath Path to save the resulting video
     * @param string $fontPath Path to TrueType font (.ttf)
     * @param int $direction Text placement direction
     * @param int $fontSize Font size
     * @param int $xOffset Horizontal offset of the text from the left edge
     * @param int $yOffset Vertical offset of the text from the top edge
     * @return bool Whether the video with text was successfully added
     */
    public static function addTextToVideo(
        string $filePath,
        string $text,
        string $outputPath,

        int    $direction = self::TEXT_DIRECTION_LEFT | self::TEXT_POSITION_UP,
        string $fontPath = '/web/local/font/Helvetica.ttf',

        int    $fontSize = 15,
        int    $xOffset = 10,
        int    $yOffset = 10,
    ): bool
    {
        $fontPath = \Yii::$app->basePath . '/' . ltrim($fontPath, '/');

        $ffmpegPath = '/usr/bin/ffmpeg';
        $fontColor = 'ffffff';
        $semibold = '\\\\\\\\:style=Semibold';
        $border = 'borderw=2:bordercolor=#464646';

        if (!file_exists($filePath)) return false;

        $videoSize = self::getVideoSize($filePath);
        $textSize = self::getTextSize($text, $fontPath, $fontSize);
        $optimalTextSize = self::findOptimalFontSize($textSize, $videoSize['width'], $videoSize['height']);
        $textCoords = self::calculateCoordinatesToVideo($direction, $xOffset, $yOffset);

        $escapedText = escapeshellarg(str_replace([':', '"'], ['\\:', '\\"'], $text));
        $outputPath = escapeshellarg($outputPath);

        $cmd = "$ffmpegPath -i $filePath -vf \"drawtext=fontfile={$fontPath}{$semibold}:text=$escapedText:$border:x={$textCoords['x']}:y={$textCoords['y']}:fontsize=$optimalTextSize:fontcolor=$fontColor\" -c:v libx265 -preset fast -crf 28 -tag:v hvc1 -codec:a copy $outputPath";
        exec($cmd, $output, $res);

        return $res === 0 || (file_exists($outputPath) && unlink($outputPath));
    }

    public static function findOptimalFontSize($textSize, $imageWidth, $imageHeight, $reductionCoefficient = 1.7, $minRatio = 7): int
    {
        $percentWidthTextOfTotalSize = ($textSize['width'] / $imageWidth) * 100;
        $percentTextWidth = ($textSize['width'] / $percentWidthTextOfTotalSize) * $reductionCoefficient;

        $percentHeightTextOfTotalSize = ($textSize['height'] / $imageHeight) * 100;
        $percentTextHeight = ($textSize['height'] / $percentHeightTextOfTotalSize) * $reductionCoefficient;

        $optimalRatio = min($percentTextWidth, $percentTextHeight);
        $optimalRatio = max($optimalRatio, $minRatio);
        return (int)$optimalRatio;
    }

    /**
     * @param array $strings
     * @return false|int
     * @deprecated
     */
    private static function getMaxLenStrings(array $strings)
    {
        $maxLen = 0;
        foreach($strings as $string) {
            $strLen = iconv_strlen($string);
            if ($maxLen < $strLen) {
                $maxLen = $strLen;
            }
        }
        return $maxLen;
    }

    public static function addTextToMedia(
        string $filePath,
        string $text,
        string $outputPath,

        int    $direction = self::TEXT_DIRECTION_LEFT | self::TEXT_POSITION_DOWN
    ): bool
    {
        $filePath = \Yii::$app->basePath . '/' . ltrim($filePath, '/');
        $outputPath = \Yii::$app->basePath . '/' . ltrim($outputPath, '/');
        $type = mime_content_type($filePath);

        try {
            if (str_starts_with($type, 'image')) {
                return self::addTextToImage($filePath, $text, $outputPath, $direction);
            }
            if (str_starts_with($type, 'video')) {
                return self::addTextToVideo($filePath, $text, $outputPath, $direction);
            }
        } catch (\Exception $exception) {
            Logger::log($exception, 'media_mark_error');
        }

        return false;
    }
}
