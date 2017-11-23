<?php

namespace mgcode\imagefly\models;

use mgcode\helpers\ActiveRecordHelperTrait;

/**
 * This is the model class for table "image".
 * @property string $fullPath
 */
class Image extends AbstractImage
{
    use ActiveRecordHelperTrait;

    /**
     * Returns public url of image
     * @param $params
     * @return string
     */
    public function getUrl($params)
    {
        return \Yii::$app->image->getImageUrl($this->getFullPath(), $params);
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
