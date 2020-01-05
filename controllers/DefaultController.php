<?php

namespace wdmg\sitemap\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\ArrayHelper;

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
        if ($this->module->cacheExpire !== 0 && ($cache = Yii::$app->getCache())) {
            $items = $cache->getOrSet(md5('sitemap'), function () {
                return $this->getSitemapItems();
            }, intval($this->module->cacheExpire));
        } else {
            $items = $this->getSitemapItems();
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->getResponse()->getHeaders()->set('Content-Type', 'text/xml; charset=UTF-8');
        return $this->renderPartial('sitemap', [
            'items' => $items
        ]);
    }

    /**
     * Forms an array of items for building a sitemap
     *
     * @return array
     */
    private function getSitemapItems() {
        $items = [];
        if (is_array($models = $this->module->supportModels)) {
            foreach ($models as $name => $class) {
                if (class_exists($class)) {
                    $append = [];
                    $model = new $class();
                    foreach ($model->getPublished(['in_sitemap' => true]) as $item) {
                        $append[] = [
                            'url' => ($item->url) ? $item->url : false,
                            'updated_at' => ($item->updated_at) ? $item->updated_at : false
                        ];
                    };
                    $items = ArrayHelper::merge($items, $append);
                }
            }
        }

        return $items;
    }
}
