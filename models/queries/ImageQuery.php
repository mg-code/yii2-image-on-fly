<?php

namespace mgcode\imagefly\models\queries;

/**
 * This is the ActiveQuery class for [[\mgcode\imagefly\models\Image]].
 *
 * @see \mgcode\imagefly\models\Image
 */
class ImageQuery extends \yii\db\ActiveQuery
{

    /**
     * @inheritdoc
     * @return \mgcode\imagefly\models\Image[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \mgcode\imagefly\models\Image|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
