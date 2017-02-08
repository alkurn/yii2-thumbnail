Thumbnail Image Helper for Yii2
========================

Yii2 helper for creating and caching thumbnails on real time.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist "alkurn/yii2-thumbnail" "*"
```
 
or add

```json
"alkurn/yii2-thumbnail" : "*"
```

to the require section of your application's `composer.json` file.

* Add a new lines in `bootstrap` file of your application, for example:

```
Yii::setAlias('@uploads', dirname(dirname(__DIR__)) . '/uploads');
Yii::setAlias('@cache', dirname(dirname(__DIR__)) . '/uploads/cache');
Yii::setAlias('@image',   '/uploads/cache');
```

* Add a new component in `components` section of your application's configuration file (optional), for example:

```php
'components' => [
    'thumbnail' => [
                'class' => 'alkurn\thumbnail\Thumbnail',
                'cacheAlias'    => Yii::getAlias('@cache/'),
                'uploadsAlias'  => Yii::getAlias('@uploads/'),
                'imageAlias'    => Yii::getAlias('@image/'),
                'defaultImage'  => 'default.png',
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
    $model->image,
    50,
    50,
    ThumbnailImage::THUMBNAIL_OUTBOUND,
    ['alt' => $model->image]
);
```