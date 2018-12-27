<?php

namespace mgcode\imagefly\models;

use mgcode\helpers\ActiveRecordHelperTrait;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "image".
 * @property string $fullPath
 * @property ImageThumb[] $thumbs
 */
class Image extends AbstractImage
{
    use ActiveRecordHelperTrait;

    private $_signatures;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThumbs()
    {
        return $this->hasMany(ImageThumb::class, ['image_id' => 'id']);
    }

    /**
     * Builds srcset from array of sizes
     * @param array $sizes E.g. ['1600w' => ImageType::EXTRA_LARGE, '1200w' => ImageType::LARGE]
     * @return string
     */
    public function buildSrcset($sizes = [])
    {
        $result = [];
        foreach ($sizes as $size => $type) {
            $result[] = $this->getUrl($type).' '.$size;
        }
        return implode(', ', $result);
    }

    /**
     * Returns public url of image
     * @param int $type
     * @return string
     */
    public function getUrl($type)
    {
        if ($this->_signatures === null) {
            $this->_signatures = ArrayHelper::map($this->thumbs, 'type', 'signature');
        }
        $signature = $this->_signatures[$type];
        return \Yii::$app->image->getImageUrl($this->getFullPath(), $signature);
    }

    /**
     * Returns full path of image
     * @return string
     */
    public function getFullPath()
    {
        return $this->path.'/'.$this->filename;
    }
}
