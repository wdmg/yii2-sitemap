<?php

namespace wdmg\sitemap\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use wdmg\sitemap\models\Sitemap;

/**
 * DefaultController implements actions
 */
class DefaultController extends Controller
{

    public $defaultAction = 'sitemap';

    /**
     * Displays the sitemap.xml in the frontend.
     *
     * @return string
     */
    public function actionSitemap() {

        $model = new Sitemap();
        if ($this->module->cacheExpire !== 0 && ($cache = Yii::$app->getCache())) {
            $items = $cache->getOrSet(md5('sitemap'), function () use ($model) {
                return $model->getSitemapItems();
            }, intval($this->module->cacheExpire));
        } else {
            $items = $model->getSitemapItems();
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->getResponse()->getHeaders()->set('Content-Type', 'text/xml; charset=UTF-8');
        return $this->renderPartial('sitemap', [
            'items' => $items
        ]);
    }
}
