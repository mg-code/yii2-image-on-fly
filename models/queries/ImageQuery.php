<?php

namespace mgcode\imagefly\models\queries;

use mgcode\helpers\ActiveQueryHelperTrait;
use mgcode\imagefly\components\ResizeComponent;
use mgcode\imagefly\models\ImageThumb;
use yii\db\Query;

/**
 * This is the ActiveQuery class for [[\mgcode\imagefly\models\Image]].
 * @see \mgcode\imagefly\models\Image
 */
class ImageQuery extends \yii\db\ActiveQuery
{
    use ActiveQueryHelperTrait;

    public function doesNotHaveThumb($params)
    {
        $alias = $this->getTableAlias();
        $subQuery = (new Query())
            ->select(['t.image_id'])
            ->from('image_thumb t')
            ->andWhere("t.image_id = {$alias}.id")
            ->andWhere(ImageThumb::buildAttributes($params));
        $this->andWhere(['not exists', $subQuery]);
        return $this;
    }

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
