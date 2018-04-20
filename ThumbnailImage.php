<?php

/**
 * @link https://gitlab.com/ganesh.alkurn/alkurn/yii2-thumbnail
 * @copyright Copyright (c) 2016 Alkurn
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace alkurn\thumbnail;

use Yii;
use yii\helpers\Html;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use Imagine\Image\Box;
use Imagine\Image\ManipulatorInterface;

/**
 * Yii2 helper for creating and caching thumbnails on real time
 * @author Ganesh
 * @package alkurn\thumbnail
 */
class ThumbnailImage
{
    const THUMBNAIL_OUTBOUND = ManipulatorInterface::THUMBNAIL_OUTBOUND;
    const THUMBNAIL_INSET = ManipulatorInterface::THUMBNAIL_INSET;

    /** @var string $cacheAlias path alias relative with @web where the cache files are kept */
    public static $cacheAlias = 'assets/thumbnails';
    public static $uploadsAlias = 'assets/thumbnails';
    public static $imageAlias = 'assets/thumbnails';
    public static $defaultImage = 'default.png';

    /** @var int $cacheExpire */
    public static $cacheExpire = 0;

    /**
     * Creates and caches the image thumbnail and returns ImageInterface.
     *
     * @param string $filename the image file path or path alias
     * @param integer $width the width in pixels to create the thumbnail
     * @param integer $height the height in pixels to create the thumbnail
     * @param string $mode self::THUMBNAIL_INSET, the original image
     * is scaled down so it is fully contained within the thumbnail dimensions.
     * The specified $width and $height (supplied via $size) will be considered
     * maximum limits. Unless the given dimensions are equal to the original image’s
     * aspect ratio, one dimension in the resulting thumbnail will be smaller than
     * the given limit. If self::THUMBNAIL_OUTBOUND mode is chosen, then
     * the thumbnail is scaled so that its smallest side equals the length of the
     * corresponding side in the original image. Any excess outside of the scaled
     * thumbnail’s area will be cropped, and the returned thumbnail will have
     * the exact $width and $height specified
     * @return \Imagine\Image\ImageInterface
     */
    public static function thumbnail($filename, $width, $height, $mode = self::THUMBNAIL_OUTBOUND)
    {
        return Image::getImagine()->open(self::thumbnailFile($filename, $width, $height, $mode));
    }

    /**
     * Creates and caches the image thumbnail and returns full path from thumbnail file.
     *
     * @param string $filename
     * @param integer $width
     * @param integer $height
     * @param string $mode
     * @return string
     * @throws FileNotFoundException
     */
    public static function thumbnailFile($file, $width, $height, $mode = self::THUMBNAIL_OUTBOUND)
    {
        //'default.png'
        $cachePath = self::$cacheAlias;
        $file = empty($file) ? self::$defaultImage : $file;

        $filename = FileHelper::normalizePath(self::$uploadsAlias . $file);

        if (!is_file($filename)) {
            $filename = FileHelper::normalizePath(self::$uploadsAlias . self::$defaultImage);
        }

        [$_width, $_height] = getimagesize($filename);
        if (empty($width) && empty($height)) {
            $width = $_width;
            $height = $_height;
        } elseif (empty($width)) {
            $width = ($height * ($_width / $_height));
        } elseif (empty($height)) {
            $height = $width / ($_width / $_height);
        }

        $thumbnailFileExt = pathinfo($filename, PATHINFO_EXTENSION);
        $thumbnailFileName = pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . 'x' . $height;
        $thumbnailFilePath = $cachePath . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_DIRNAME);
        $thumbnailFile = $thumbnailFilePath . DIRECTORY_SEPARATOR . $thumbnailFileName . '.' . $thumbnailFileExt;
        $realFilePath = pathinfo($file, PATHINFO_DIRNAME);


        if ($realFilePath == '.' || $realFilePath == '..') {
            $file = $thumbnailFileName . '.' . $thumbnailFileExt;
        } else {
            $file = $realFilePath . DIRECTORY_SEPARATOR . $thumbnailFileName . '.' . $thumbnailFileExt;
        }

        $file = self::$imageAlias . $file;

        if (file_exists($thumbnailFile)) {
            if (self::$cacheExpire !== 0 && (time() - filemtime($thumbnailFile)) > self::$cacheExpire) {
                unlink($thumbnailFile);
            } else {
                return $file;
            }
        }

        if (!is_dir($thumbnailFilePath)) {
            mkdir($thumbnailFilePath, 0755, true);
        }

        $box = new Box($width, $height);
        $image = Image::getImagine()->open($filename);
        $image = $image->thumbnail($box, $mode);
        $image->save($thumbnailFile);
        return $file;
    }


    /**
     * Creates and caches the image thumbnail and returns URL from thumbnail file.
     *
     * @param string $filename
     * @param integer $width
     * @param integer $height
     * @param string $mode
     * @return string
     */
    public static function thumbnailFileUrl($filename, $width, $height, $mode = self::THUMBNAIL_OUTBOUND)
    {

        $filename = FileHelper::normalizePath($filename);
        return self::thumbnailFile($filename, $width, $height, $mode);
    }

    /**
     * Creates and caches the image thumbnail and returns <img> tag.
     *
     * @param string $filename
     * @param integer $width
     * @param integer $height
     * @param string $mode
     * @param array $options options similarly with \yii\helpers\Html::img()
     * @return string
     */
    public static function thumbnailImg($filename, $width = null, $height = null, $mode = self::THUMBNAIL_OUTBOUND, $options = [])
    {

        $filename = FileHelper::normalizePath($filename);
        try {
            $thumbnailFileUrl = self::thumbnailFileUrl($filename, $width, $height, $mode);
        } catch (\Exception $e) {
            return static::errorHandler($e, $filename);
        }

        return Html::img($thumbnailFileUrl, $options);
    }

    /**
     * Clear cache directory.
     *
     * @return bool
     */
    public static function clearCache()
    {
        $cacheDir = Yii::getAlias(self::$cacheAlias);
        self::removeDir($cacheDir);
        return @mkdir($cacheDir, 0755, true);
    }

    protected static function removeDir($path)
    {
        if (is_file($path)) {
            @unlink($path);
        } else {
            array_map('self::removeDir', glob($path . DIRECTORY_SEPARATOR . '*'));
            @rmdir($path);
        }
    }

    protected static function errorHandler($error, $filename)
    {
        if ($error instanceof FileNotFoundException) {
            return 'File doesn\'t exist';
        } else {
            Yii::warning("{$error->getCode()}\n{$error->getMessage()}\n{$error->getFile()}");
            return 'Error ' . $error->getCode();
        }
    }

}
