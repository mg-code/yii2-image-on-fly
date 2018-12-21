<?php

namespace mgcode\imagefly\models;

use Yii;

/**
 * This is the model class for table "image_thumb".
 *
 * @property string $signature
 * @property integer $image_id
 * @property integer $width
 * @property integer $height
 * @property integer $jpeg_quality
 * @property string $ratio
 * @property integer $blur
 * @property integer $no_zoom_in
 * @property integer $crop
 * @property string $background
 * @property integer $normalize
 * @property integer $auto_gamma
 */
abstract class AbstractImageThumb extends \yii\db\ActiveRecord
{
    /** @inheritdoc */
    public static function tableName()
    {
        return 'image_thumb';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['signature', 'image_id'], 'required'],
            [['image_id', 'width', 'height', 'jpeg_quality', 'blur', 'no_zoom_in', 'crop', 'normalize', 'auto_gamma'], 'integer'],
            [['ratio'], 'string'],
            [['signature'], 'string', 'max' => 32],
            [['background'], 'string', 'max' => 7],
            [['image_id', 'width', 'height', 'jpeg_quality', 'ratio', 'blur', 'no_zoom_in', 'crop', 'background', 'normalize', 'auto_gamma'], 'unique', 'targetAttribute' => ['image_id', 'width', 'height', 'jpeg_quality', 'ratio', 'blur', 'no_zoom_in', 'crop', 'background', 'normalize', 'auto_gamma']],
            [['signature'], 'unique'],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Image::className(), 'targetAttribute' => ['image_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'signature' => 'Signature', 
            'image_id' => 'Image ID', 
            'width' => 'Width', 
            'height' => 'Height', 
            'jpeg_quality' => 'Jpeg Quality', 
            'ratio' => 'Ratio', 
            'blur' => 'Blur', 
            'no_zoom_in' => 'No Zoom In', 
            'crop' => 'Crop', 
            'background' => 'Background', 
            'normalize' => 'Normalize', 
            'auto_gamma' => 'Auto Gamma', 
        ];
    }

    /**
     * @inheritdoc
     * @return \mgcode\imagefly\models\queries\ImageThumbQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \mgcode\imagefly\models\queries\ImageThumbQuery(get_called_class());
    }
}