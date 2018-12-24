<?php

namespace mgcode\imagefly\controllers;

use mgcode\helpers\ConstantHelper;
use mgcode\imagefly\components\ResizeComponent;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ResizeController extends Controller
{
    /** @inheritdoc */
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                'lastModified' => function () {
                    return time();
                },
                'sessionCacheLimiter' => 'public',
                'cacheControlHeader' => 'public, max-age=31556926', // Max age 1 year
            ],
        ];
    }

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        \Yii::$app->errorHandler->errorAction = $this->getUniqueId().'/error';
    }

    /**
     * Serves image to client
     * @param string $path
     * @param string $signature
     * @return string
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex($path, $signature)
    {
        $component = \Yii::$app->image;
        $request = \Yii::$app->request;
        $params = $this->extractParameters($request->get());

        $signatureParams = $component->resize->mergeParamsWithDefault($params);
        if (!$component->originalStorage->has($path) || !$component->validateSignature($signature, $path, $signatureParams)) {
            throw new NotFoundHttpException();
        }

        // Get and validate mime type
        $mimeType = $component->originalStorage->getMimetype($path);
        if (!$mimeType || !$component->isMimeTypeValid($mimeType)) {
            throw new BadRequestHttpException();
        }

        // Get extension by mime type
        $extension = $component->getExtensionByMimeType($mimeType);

        // Read file content
        $content = $component->originalStorage->read($path);

        $output = $component
            ->resize
            ->thumbFromContent($content, $extension, $params);

        $response = \Yii::$app->response;
        header_remove('Expires');
        header_remove('Cache-Control');
        header_remove('Last-Modified');
        header_remove('Pragma');
        $response->format = Response::FORMAT_RAW;
        $response->headers->add('Content-Type', $mimeType);
        return $output;
    }

    public function actionError()
    {
        if (($exception = \Yii::$app->getErrorHandler()->exception) === null) {
            return '';
        }
        if ($exception instanceof HttpException) {
            $msg = $exception->statusCode;
        } else {
            $msg = $exception->getCode();
        }
        if (YII_DEBUG) {
            $msg .= ' '.$exception->getMessage();
        }
        return $msg;
    }

    /**
     * Extracts only usable parameters
     * @param array $parameters
     * @return array
     */
    protected function extractParameters($parameters)
    {
        $available = array_values(ConstantHelper::getConstantList(ResizeComponent::className(), 'PARAM_'));
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $available) || !is_scalar($value)) {
                unset($parameters[$key]);
            }
        }
        return $parameters;
    }
}
