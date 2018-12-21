<?php

namespace mgcode\imagefly\models\queries;

/**
 * This is the ActiveQuery class for [[\mgcode\imagefly\models\ImageThumb]].
 *
 * @see \mgcode\imagefly\models\ImageThumb
 */
class ImageThumbQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return \mgcode\imagefly\models\ImageThumb[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \mgcode\imagefly\models\ImageThumb|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
