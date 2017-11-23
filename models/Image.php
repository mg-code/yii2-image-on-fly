<?php

namespace mgcode\imagefly\models;

use mgcode\helpers\ActiveRecordHelperTrait;

/**
 * This is the model class for table "image".
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

    protected function getFullPath()
    {
        return $this->path.'/'.$this->filename;
    }
}
