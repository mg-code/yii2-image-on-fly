<?php

namespace mgcode\imagefly\models;

use mgcode\helpers\ActiveRecordHelperTrait;
use mgcode\imagefly\components\ResizeComponent;

/**
 * This is the model class for table "image_thumb".
 * @property Image $image
 */
class ImageThumb extends AbstractImageThumb
{
    use ActiveRecordHelperTrait;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::className(), ['id' => 'image_id']);
    }

    public static function buildAttributes($params)
    {
        $attributes = [
            'width' => static::_nullIfNotExists(ResizeComponent::PARAM_WIDTH, $params),
            'height' => static::_nullIfNotExists(ResizeComponent::PARAM_HEIGHT, $params),
            'jpeg_quality' => static::_nullIfNotExists(ResizeComponent::PARAM_JPEG_QUALITY, $params),
            'ratio' => static::_nullIfNotExists(ResizeComponent::PARAM_RATIO, $params),
            'blur' => static::_nullIfNotExists(ResizeComponent::PARAM_BLUR, $params),
            'no_zoom_in' => static::_nullIfNotExists(ResizeComponent::PARAM_NO_ZOOM_IN, $params, true),
            'crop' => static::_nullIfNotExists(ResizeComponent::PARAM_CROP, $params, true),
            'background' => static::_nullIfNotExists(ResizeComponent::PARAM_BACKGROUND, $params),
            'normalize' => static::_nullIfNotExists(ResizeComponent::PARAM_NORMALIZE, $params, true),
            'auto_gamma' => static::_nullIfNotExists(ResizeComponent::PARAM_AUTO_GAMMA, $params, true),
        ];
        return $attributes;
    }

    private static function _nullIfNotExists($key, $params, $boolColumn = false)
    {
        $result = array_key_exists($key, $params) ? $params[$key] : null;
        if (!$boolColumn || $result !== null) {
            return $result;
        }
        return $boolColumn ? 1 : 0;
    }
}