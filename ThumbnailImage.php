<?php
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
    const THUMBNAIL_INSET_BOX = 'inset_box';
    const IMAGE_QUALITY = 90;
    const MKDIR_MODE = 0755;

    /** @var string $cacheAlias path alias relative with @web where the cache files are kept */
    public static $cacheAlias = 'assets/thumbnails';
    public static $uploadsAlias = 'assets/thumbnails';
    public static $imageAlias = 'assets/thumbnails';
    public static $storageAlias = 'assets/thumbnails';
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
    public static function thumbnail($filename, $width, $height, $mode = self::THUMBNAIL_OUTBOUND, $quality = null)
    {
        return Image::getImagine()->open(self::thumbnailFile($filename, $width, $height, $mode, $quality));
    }

    public static function awsFile($file, $width, $height)
    {

        $thumbnailFileExt = pathinfo($file, PATHINFO_EXTENSION);
        $realFilePath = pathinfo($file, PATHINFO_DIRNAME);
        $thumbnailFileName = pathinfo($file, PATHINFO_FILENAME) . '-' . $width . 'x' . $height;
        $file = $realFilePath . DIRECTORY_SEPARATOR . $thumbnailFileName . '.' . $thumbnailFileExt;
        return $file;
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
    public static function thumbnailFile($file, $width, $height, $mode = self::THUMBNAIL_OUTBOUND, $quality = null)
    {
        $cachePath = self::$cacheAlias;
        $file = empty($file) ? self::$defaultImage : $file;
        $filename = FileHelper::normalizePath(self::$uploadsAlias . $file);
        if (!is_file($filename)) {
            $filename = FileHelper::normalizePath(self::$defaultImage);
        }

        list($_width, $_height) = getimagesize($filename);
        if (empty($width) && empty($height)) {
            $width = $_width;
            $height = $_height;
        } /*elseif (empty($width)) {
            $width = ($height * ($_width / $_height));
        } elseif (empty($height)) {
            $height = $width / ($_width / $_height);
        }*/

        if (isset(Yii::$app->s3)) {
            $thumbFile = self::awsFile($file, $width, $height);
            $isExist = Yii::$app->s3->doesObjectExist('cache/' . $thumbFile);
            if ($isExist) {
                return self::$imageAlias . $thumbFile;
            }
        }

        $thumbnailFileExt = pathinfo($filename, PATHINFO_EXTENSION);
        $thumbnailFileName = pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . 'x' . $height;
        $thumbnailFilePath = $cachePath . pathinfo($file, PATHINFO_DIRNAME);
        $thumbnailFile = $thumbnailFilePath . DIRECTORY_SEPARATOR . $thumbnailFileName . '.' . $thumbnailFileExt;
        $realFilePath = pathinfo($file, PATHINFO_DIRNAME);

        if ($realFilePath == '.' || $realFilePath == '..') {
            $file = $thumbnailFileName . '.' . $thumbnailFileExt;
        } else {
            $file = $realFilePath . DIRECTORY_SEPARATOR . $thumbnailFileName . '.' . $thumbnailFileExt;
        }

        $orgFile = $file;
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

        $image = Image::getImagine()->open($filename);
        if ($mode === self::THUMBNAIL_INSET_BOX) {
            $image = $image->thumbnail(new Box($width, $height), ManipulatorInterface::THUMBNAIL_INSET);
        } else {
            $image = Image::thumbnail($image, $width, $height, $mode);
        }

        //$image = $image->thumbnail(new Box($width, $height), $mode);
        //$image->save($thumbnailFile, ['quality' => self::IMAGE_QUALITY]);

        $options = ['quality' => $quality === null ? self::IMAGE_QUALITY : $quality];
        $image->save($thumbnailFile, $options);
        if (file_exists($thumbnailFile) && isset(Yii::$app->s3)) {
            Yii::$app->s3->putObject('cache/' . $orgFile, Yii::getAlias("@cache/{$orgFile}"), 'public-read');
        }
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
    public static function thumbnailFileUrl($filename, $width, $height, $mode = self::THUMBNAIL_OUTBOUND, $quality = null)
    {
        $filename = FileHelper::normalizePath($filename);
        return self::thumbnailFile($filename, $width, $height, $mode, $quality);
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
    public static function thumbnailImg($filename, $width = null, $height = null, $mode = self::THUMBNAIL_OUTBOUND, $options = [], $quality = null)
    {
        $filename = FileHelper::normalizePath($filename);
        try {
            $thumbnailFileUrl = self::thumbnailFileUrl($filename, $width, $height, $mode, $quality);
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

    public static function waterMark($filename, $width = null, $height = null, $start = [0, 0])
    {
        $watermarkImage = self::thumbnailFile('watermark.png', $width, $height);
        $watermarkImage = basename($watermarkImage);
        $watermarkImage = Yii::getAlias("@cache/$watermarkImage");
        return Image::watermark($filename, $watermarkImage, $start)->save();
    }
}
