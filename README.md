Easy Thumbnail Image Helper for Yii2
========================

Yii2 helper for creating and caching thumbnails on real time.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require "alkurn/yii2-thumbnail" "*"
```
or add

```json
"alkurn/yii2-thumbnail" : "*"
```

to the require section of your application's `composer.json` file.

* Add a new component in `components` section of your application's configuration file (optional), for example:

```php
'components' => [
    'thumbnail' => [
        'class' => 'alkurn\thumbnail\Thumbnail',
        'cacheAlias' => 'assets/gallery_thumbnails',
    ],
],
```

and in `bootstrap` section, for example:

```php
'bootstrap' => ['log', 'thumbnail'],
```

It is necessary if you want to set global helper's settings for the application.

Usage
-----
For example:

```php
use alkurn\thumbnail\ThumbnailImage;

echo ThumbnailImage::thumbnailImg(
    $model->pictureFile,
    50,
    50,
    ThumbnailImage::THUMBNAIL_OUTBOUND,
    ['alt' => $model->pictureName]
);
```

For other functions please see the source code.

If you want to handle errors that appear while converting to thumbnail by yourself, please make your own class and inherit it from ThumbnailImage. In your class replace only protected method errorHandler. For example

```php
class ThumbHelper extends \alkurn\thumbnail\ThumbnailImage
{

    protected static function errorHandler($error, $filename)
    {
        if ($error instanceof \alkurn\thumbnail\FileNotFoundException) {
            return \yii\helpers\Html::img('@web/images/notfound.png');
        } else {
            $filename = basename($filename);
            return \yii\helpers\Html::a($filename,"@web/files/$filename");
        }
    }
} 
```
