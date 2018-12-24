<?php

namespace mgcode\imagefly\components;

use creocoder\flysystem\Filesystem;
use League\Flysystem\AdapterInterface;
use mgcode\helpers\NumberHelper;
use mgcode\helpers\TimeHelper;
use mgcode\imagefly\models\ImageThumb;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\InvalidValueException;
use yii\di\Instance;
use mgcode\imagefly\models\Image;
use yii\helpers\FileHelper;
use yii\httpclient\Client;
use yii\web\UploadedFile;

/**
 * Class ImageComponent
 * @package mgcode\imagefly\components
 */
class ImageComponent extends BaseObject
{
    /**
     * Storage adapter for original images
     * @var Filesystem
     */
    public $originalStorage;

    /**
     * Storage adapter for thumbnails
     * @var Filesystem
     */
    public $thumbStorage;

    /**
     * Template for building urls.
     * You can use {signature}, {path} variables.
     * {signature} and {path} variables are mandatory.
     * @var string
     */
    public $urlTemplate = '/media/{signature}/{path}';

    /**
     * List of supported mime types
     * @var array
     */
    public $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'wbmp' => 'image/vnd.wap.wbmp',
        'xbm' => 'image/xbm',
    ];

    /**
     * Salt used for signature generation.
     * @var string
     */
    public $signatureSalt;

    /**
     * Apply parameters for original image resize
     * @var array
     */
    public $originalImageParams = [
        ResizeComponent::PARAM_WIDTH => 3840,
        ResizeComponent::PARAM_HEIGHT => 2160,
        ResizeComponent::PARAM_RATIO => ResizeComponent::RATIO_MIN,
        ResizeComponent::PARAM_JPEG_QUALITY => 90,
    ];

    /** @var string */
    public $thumbPathTemplate = 'media/{signature}/{path}';

    /**
     * Component that is responsible for image resizing
     * @var ResizeComponent
     */
    public $resize = 'mgcode\imagefly\components\ResizeComponent';

    /**
     * @var array Pre resizes images
     */
    public $preResizeTypes = [];

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        // Set vars
        $this->originalStorage = Instance::ensure($this->originalStorage, Filesystem::className());
        if ($this->thumbStorage) {
            $this->thumbStorage = Instance::ensure($this->thumbStorage, Filesystem::className());
        }
        $this->resize = Instance::ensure($this->resize, ResizeComponent::className());

        // Validate vars
        if ($this->signatureSalt === null) {
            throw new InvalidConfigException('`signatureSalt` must be set.');
        }
        if ($this->preResizeTypes && !$this->thumbStorage) {
            throw new InvalidConfigException('`thumbStorage` must be set.');
        }
    }

    /**
     * Saves image from UploadedFile instance
     * @param UploadedFile $instance
     * @return Image
     * @throws \Exception
     */
    public function saveFromInstance(UploadedFile $instance)
    {
        // Validate mime
        $mimeType = FileHelper::getMimeType($instance->tempName);
        if (!$mimeType || !$this->isMimeTypeValid($mimeType)) {
            throw new \Exception('Wrong mime type. Given: '.$mimeType);
        }
        return $this->saveImage($instance->tempName, $mimeType);
    }

    /**
     * Saves image from local file
     * @param $fileName
     * @return Image
     * @throws \Exception
     */
    public function saveFromFile($fileName)
    {
        if (!file_exists($fileName)) {
            throw new InvalidParamException('file not exists: '.$fileName);
        }

        $data = file_get_contents($fileName);
        $base64 = base64_encode($data);
        return $this->saveFromBase64($base64);
    }

    /**
     * Saves image from url
     * @param $url
     * @return Image
     * @throws \Exception
     */
    public function saveFromUrl($url)
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('get')
            ->setUrl($url)
            ->send();
        if (!$response->isOk) {
            throw new InvalidValueException('Failed to load image contents. Code: '.$response->getStatusCode().'. Url: '.$url);
        }
        $base64 = base64_encode($response->getContent());
        return $this->saveFromBase64($base64);
    }

    /**
     * Saves image from base64 string
     * @param $data
     * @return Image
     * @throws \Exception
     */
    public function saveFromBase64($data)
    {
        $f = finfo_open();
        $mimeType = finfo_buffer($f, base64_decode($data), FILEINFO_MIME_TYPE);
        unset($f);
        if (!$mimeType || !$this->isMimeTypeValid($mimeType)) {
            throw new \Exception('Wrong mime type. Given: '.$mimeType);
        }

        // Save image to tmp file
        $fh = tmpfile();
        stream_filter_append($fh, 'convert.base64-decode', STREAM_FILTER_WRITE);
        fwrite($fh, $data);

        // Save image
        $location = stream_get_meta_data($fh)['uri'];
        $image = $this->saveImage($location, $mimeType);

        // Close handle and return image
        fclose($fh);
        unset($fh);
        return $image;
    }

    /**
     * Saves image from temp location
     * @param string $tmpLocation Image temporary location
     * @return Image Image object
     * @throws \Exception
     */
    protected function saveImage($tmpLocation, $mimeType)
    {
        return Yii::$app->db->transaction(function () use ($tmpLocation, $mimeType) {
            $extension = $this->getExtensionByMimeType($mimeType);
            $size = $this->getImageSize($tmpLocation);

            // Save image with temporary directory
            $image = new Image();
            $image->path = $image->filename = 'temp';
            $image->mime_type = $mimeType;
            $image->height = $size['height'];
            $image->width = $size['width'];
            $image->saveOrFail(false);

            $image->path = $this->generateUploadPath($image->id);
            $image->filename = $image->id.'.'.$extension;
            $image->saveOrFail();

            // Save content
            $content = $this->resize->thumbFromFile($tmpLocation, $extension, $this->originalImageParams);
            $this->originalStorage->put($image->getFullPath(), $content, [
                'visibility' => AdapterInterface::VISIBILITY_PRIVATE
            ]);

            if ($this->preResizeTypes) {
                foreach ($this->preResizeTypes as $params) {
                    $this->createThumb($image, $content, $params);
                }
            }

            return $image;
        });
    }

    public function createThumb(Image $image, $originalContent, $params)
    {
        Yii::$app->db->transaction(function () use ($image, $originalContent, $params) {
            $extension = $this->getExtensionByMimeType($image->mime_type);
            $signature = $this->generateSignature($image->getFullPath(), $params);
            $content = $this->resize->thumbFromContent($originalContent, $extension, $params);

            $model = new ImageThumb(ImageThumb::buildAttributes($params));
            $model->image_id = $image->id;
            $model->signature = $signature;
            $model->saveOrFail(false);

            $path = strtr($this->thumbPathTemplate, [
                '{signature}' => $signature,
                '{path}' => $image->getFullPath(),
                '{directory}' => $image->path,
                '{filename}' => $image->filename,
            ]);
            $this->thumbStorage->put($path, $content, [
                'visibility' => AdapterInterface::VISIBILITY_PUBLIC
            ]);
        });
    }

    /**
     * Returns public url of image
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getImageUrl($path, $params)
    {
        $signatureParams = $this->resize->mergeParamsWithDefault($params);
        $signature = $this->generateSignature($path, $signatureParams);
        $directory = dirname($path);
        $filename = basename($path);
        $url = strtr($this->urlTemplate, [
            '{signature}' => $signature,
            '{path}' => $path,
            '{directory}' => $directory,
            '{filename}' => $filename,
        ]);
        $url .= '?'.http_build_query($params);
        return $url;
    }

    /**
     * Returns extension by mime type
     * @param $mimeType
     * @return string|boolean
     */
    public function getExtensionByMimeType($mimeType)
    {
        return array_search($mimeType, $this->mimeTypes);
    }

    /**
     * Checks if mime type is valid
     * @param $mimeType
     * @return bool
     */
    public function isMimeTypeValid($mimeType)
    {
        return in_array($mimeType, $this->mimeTypes);
    }

    /**
     * Validates image signature
     * @param string $signature
     * @param string $path
     * @param array $params
     * @return bool
     */
    public function validateSignature($signature, $path, $params)
    {
        return $signature == $this->generateSignature($path, $params);
    }

    /**
     * Generates image signature from path and parameters
     * @param string $path
     * @param array $params
     * @return string
     */
    public function generateSignature($path, $params)
    {
        $parts = [$path, $this->signatureSalt];
        foreach ($params as $key => $value) {
            $parts[] = $key.':'.$value;
        }
        array_multisort($parts);
        return md5(json_encode(array_reverse($parts)));
    }

    /**
     * This is native php function wrapper, native function raises error.
     * @param $fileName
     * @param null $imageInfo
     * @return array
     * @throws \yii\base\Exception
     */
    public function getImageSize($fileName, &$imageInfo = null)
    {
        $data = @getimagesize($fileName, $imageInfo);
        if ($data === false) {
            throw new InvalidValueException('Failed to get image size.');
        }
        return [
            'width' => (int) $data[0],
            'height' => (int) $data[1],
            'mimeType' => $data['mime'],
        ];
    }

    /**
     * Returns base path for image
     * @param $id
     * @return string
     */
    protected function generateUploadPath($id)
    {
        $parentFolder = TimeHelper::getNumericMonth();

        $leadingZeros = NumberHelper::leadingZeros($id, 6);
        $folder1 = substr($leadingZeros, 0, 3);
        $folder2 = substr($leadingZeros, 3);
        $path = implode('/', [$parentFolder, $folder1, $folder2]);
        $path = trim($path, '/');

        return $path;
    }
}