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

    public function actionIndex($type = null)
    {
        $this->msg('Syncing image thumbs..');
        $types = \Yii::$app->image->types;
        foreach ($types as $key => $params) {
            if ($type !== null && $key != $type) {
                continue;
            }
            $this->msg('Syncing type {type}', ['type' => $key]);
            $this->_syncType($key, $params);
        }
        $this->msg('Done!');
        sleep(60);
    }

    private function _syncType($type, $params)
    {
        $query = Image::find()
            ->doesNotHaveThumb($type)
            ->orderBy(new Expression('RAND()'));
        $count = (clone $query)->count();
        $processed = 0;
        Console::startProgress($processed, $count, $this->getMemoryUsageMsg());
        foreach ($query->batch(5) as $images) {
            foreach ($images as $image) {
                $this->_syncImage($image, $type, $params);
            }
            $processed += count($images);
            Console::updateProgress($processed, $count, $this->getMemoryUsageMsg());
        }
    }

    /**
     * @param Image $image
     * @param string $type
     * @param array $params
     */
    private function _syncImage(Image $image, $type, $params)
    {
        $component = \Yii::$app->image;
        $storage = $component->originalStorage;
        $content = $storage->read($image->getFullPath());
        $component->createThumb($image, $content, $type);
    }
}