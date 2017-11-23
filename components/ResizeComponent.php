<?php

namespace mgcode\imagefly\components;

use Imagine\Image\ImageInterface as ImagineInterface;
use Imagine\Imagick\Image as ImagineImage;
use Imagine\Imagick\Imagine;

class ResizeComponent extends \yii\base\BaseObject
{
    // Parameters
    const PARAM_WIDTH = 'w';
    const PARAM_HEIGHT = 'h';
    const PARAM_JPEG_QUALITY = 'q';
    const PARAM_RATIO = 'r';
    const PARAM_BLUR = 'b';
    const PARAM_NO_ZOOM_IN = 'nz';

    const RATIO_MIN = 'min'; // any of sides is not larger than specified
    const RATIO_MAX = 'max'; // any of sides is smaller larger than specified (Images are not zoomed in)

    /** @var array Default parameters */
    public $defaultParameters = [
        self::PARAM_JPEG_QUALITY => 75,
        self::PARAM_RATIO => self::RATIO_MAX,
        self::PARAM_NO_ZOOM_IN => 1,
    ];

    /** @var ImageComponent */
    public $owner;

    /**
     * Creates image instance from given parameters
     * @param $originalFile
     * @param $params
     * @return ImagineImage
     */
    public function createImage($originalFile, $params)
    {
        $imagine = new Imagine();
        $image = $imagine->open($originalFile);

        $params = array_merge($this->defaultParameters, $params);

        // Additional options
        $options = [];
        if (isset($params[static::PARAM_JPEG_QUALITY])) {
            $options['jpeg_quality'] = $params[static::PARAM_JPEG_QUALITY];
        }

        // Resize image
        if (isset($params[static::PARAM_WIDTH]) || isset($params[static::PARAM_HEIGHT])) {
            $this->resize($image, $params);
        }

        if (isset($params[static::PARAM_BLUR])) {
            $blur = (int) $params[static::PARAM_BLUR];
            $image->effects()->blur($blur);
        }

        return $image;
    }

    /**
     * Performs image resize
     * @param ImagineImage $image
     * @param $params
     */
    protected function resize(ImagineImage $image, $params)
    {
        // Animated gif
        if ($image->layers()->count() > 1) {
            $options['animated'] = true;
            $image->layers()->coalesce();
            foreach ($image->layers() as $frame) {
                $frame->interlace(ImagineInterface::INTERLACE_PLANE);
                $this->resizeLayer($frame, $params);
            }
        } // Standard image
        else {
            $image->interlace(ImagineInterface::INTERLACE_PLANE);
            $this->resizeLayer($image, $params);
        }
    }

    /**
     * Resize image layer
     * @param ImagineImage $image
     * @param $params
     * @return ImagineImage
     */
    protected function resizeLayer(ImagineImage $image, $params)
    {
        $imageSize = $image->getSize();

        // Calculate image ratios
        $ratios = [];
        if (isset($params[static::PARAM_WIDTH])) {
            $ratios[] = (int) $params[static::PARAM_WIDTH] / $imageSize->getWidth();
        }
        if (isset($params[static::PARAM_HEIGHT])) {
            $ratios[] = (int) $params[static::PARAM_HEIGHT] / $imageSize->getHeight();
        }
        if (!$ratios) {
            return $image;
        }

        // Choose ratio by ratio algorithm
        if (isset($params[static::PARAM_RATIO]) && $params[static::PARAM_RATIO] == static::RATIO_MIN) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        // No zoom in image
        $noZoomIn = !isset($params[static::PARAM_NO_ZOOM_IN]) || $params[static::PARAM_NO_ZOOM_IN];
        if ($noZoomIn && $ratio >= 1) {
            return $image;
        }

        // Resize image
        $box = $imageSize->scale($ratio);
        return $image->resize($box);
    }
}