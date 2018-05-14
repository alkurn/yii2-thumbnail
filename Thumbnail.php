<?php
/**
 * @link https://gitlab.com/ganesh.alkurn/alkurn/yii2-thumbnail
 * @copyright Copyright (c) 2016 Alkurn
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace alkurn\thumbnail;
use yii\base\BaseObject;


/**
 * ThumbnailImage global configuration component.
 *
 * @author Ganesh
 * @package alkurn\thumbnail
 */
class Thumbnail extends BaseObject
{
    /** @var string $cacheAlias path alias relative with @web where the cache files are kept */
    public $cacheAlias      = 'assets/thumbnails';
    public $uploadsAlias    = 'assets/thumbnails';
    public $imageAlias      = 'assets/thumbnails';
    public $defaultImage    = 'default.png';

    /** @var int $cacheExpire seconds */
    public $cacheExpire = 0;

    public function init(){
        ThumbnailImage::$uploadsAlias   = $this->uploadsAlias;
        ThumbnailImage::$cacheAlias     = $this->cacheAlias;
        ThumbnailImage::$imageAlias     = $this->imageAlias;
        ThumbnailImage::$cacheExpire    = $this->cacheExpire;
        ThumbnailImage::$defaultImage   = $this->defaultImage;
    }
}
