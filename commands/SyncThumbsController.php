<?php

namespace mgcode\imagefly\commands;

use mgcode\commandLogger\LoggingTrait;
use mgcode\imagefly\models\Image;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Expression;
use yii\helpers\Console;

class SyncThumbsController extends Controller
{
    use LoggingTrait;

    public $types;

    public function init()
    {
        parent::init();
        if (!$this->types) {
            throw new InvalidConfigException('`types` must be defined.');
        }
    }

    public function actionIndex()
    {
        $this->msg('Syncing image thumbs..');
        foreach ($this->types as $key => $params) {
            $this->msg('Syncing type {type}', ['type' => $key]);
            $params = \Yii::$app->image->resize->mergeParamsWithDefault($params);
            $this->_syncType($params);
        }
        $this->msg('Done!');
        sleep(60);
    }

    private function _syncType($params)
    {
        $query = Image::find()
            ->doesNotHaveThumb($params)
            ->orderBy(new Expression('RAND()'));
        $count = (clone $query)->count();
        $processed = 0;
        Console::startProgress($processed, $count, $this->getMemoryUsageMsg());
        foreach ($query->batch(5) as $images) {
            foreach ($images as $image) {
                $this->_syncImage($image, $params);
            }
            $processed += count($images);
            Console::updateProgress($processed, $count, $this->getMemoryUsageMsg());
        }
    }

    /**
     * @param Image $image
     * @param array $params
     */
    private function _syncImage(Image $image, $params)
    {
        $component = \Yii::$app->image;
        $storage = $component->originalStorage;
        $content = $storage->read($image->getFullPath());
        $component->createThumb($image, $content, $params);
    }
}