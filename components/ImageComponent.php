<?php
namespace mgcode\imagefly\components;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Class ImageComponent
 * @package mgcode\imagefly\components
 * @property string|null $uploadPath
 * @property string|null $scheme
 */
class ImageComponent extends BaseObject
{
    /**
     * Upload path prefix, where all images will be stored.
     * Usually usable if you use shared server for storing images from different environments.
     * @var string
     */
    public $pathPrefix;

    /**
     * Template for building urls.
     * You can use {scheme}, {signature}, {path} variables.
     * {signature} and {path} variables are mandatory.
     * To use hardcoded scheme (e.g. for console commands) set [[scheme]]
     * parameter in components configuration.
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
        ResizeComponent::PARAM_JPEG_QUALITY => 100,
    ];

    /**
     * Component that is responsible for image resizing
     * @var ResizeComponent
     */
    public $resize = 'mgcode\imagefly\components\ResizeComponent';

    /**
     * Parent directory where all uploaded images will be stored.
     * @var string|null
     */
    private $_uploadPath;

    /**
     * Current scheme
     * @var string|null
     */
    private $_scheme;

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        if ($this->signatureSalt === null) {
            throw new InvalidConfigException('`signatureSalt` must be set.');
        }
        if ($this->_uploadPath === null) {
            throw new InvalidConfigException('Please set `uploadPath` to path where you want to store images.');
        }

        $this->resize = Instance::ensure($this->resize, ResizeComponent::className());
        $this->resize->owner = $this;
    }

    /**
     * Returns public url of image
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getImageUrl($path, $params)
    {
        $signature = $this->generateSignature($path, $params);
        $url = strtr($this->urlTemplate, [
            '{scheme}' => $this->getScheme(),
            '{signature}' => $signature,
            '{path}' => $path,
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
     * Returns current upload path
     * @return string|null
     */
    public function getUploadPath()
    {
        return $this->_uploadPath;
    }

    /**
     * Changes the current upload path.
     * @param string $value
     */
    public function setUploadPath($value)
    {
        $value = Yii::getAlias($value);
        $value = realpath($value);
        $this->_uploadPath = $value;
    }

    /**
     * Returns full path of image
     * @param $path
     * @return array
     */
    public function getOriginalFile($path)
    {
        if (!$this->pathInsideRoot($path)) {
            return false;
        }
        $fullPath = $this->getRealPath($path);
        if (!$fullPath || !file_exists($fullPath)) {
            return false;
        }
        return $fullPath;
    }

    /**
     * Returns current scheme
     * @return null|string
     */
    public function getScheme()
    {
        if ($this->_scheme !== null) {
            return $this->_scheme;
        }
        $isSecure = \Yii::$app->request->getIsSecureConnection();
        return $isSecure ? 'https' : 'http';
    }

    /**
     * Changes the current scheme
     * @param $value
     */
    public function setScheme($value)
    {
        $this->_scheme = $value;
    }

    /**
     * Whether path is inside upload path
     * @param $path
     * @return bool
     */
    protected function pathInsideRoot($path)
    {
        $path = $this->getRealPath($path);
        if (!$path) {
            return false;
        }
        return strpos($path, $this->getUploadPath()) === 0;
    }

    /**
     * Returns real path of path
     * @param $path
     * @return bool|string
     */
    protected function getRealPath($path)
    {
        $path = $this->getUploadPath().DIRECTORY_SEPARATOR.$path;
        $path = realpath($path);
        if (!$path) {
            return false;
        }
        return $path;
    }
}