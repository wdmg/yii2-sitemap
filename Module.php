<?php

namespace wdmg\sitemap;

/**
 * Yii2 Sitemap manager
 *
 * @category        Module
 * @version         1.0.0
 * @author          Alexsander Vyshnyvetskyy <alex.vyshnyvetskyy@gmail.com>
 * @link            https://github.com/wdmg/yii2-sitemap
 * @copyright       Copyright (c) 2019 W.D.M.Group, Ukraine
 * @license         https://opensource.org/licenses/MIT Massachusetts Institute of Technology (MIT) License
 *
 */

use Yii;
use wdmg\base\BaseModule;
use yii\base\InvalidConfigException;

/**
 * Sitemap module definition class
 */
class Module extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'wdmg\sitemap\controllers';

    /**
     * {@inheritdoc}
     */
    public $defaultRoute = "sitemap/index";

    /**
     * @var string, the name of module
     */
    public $name = "Sitemap";

    /**
     * @var string, the description of module
     */
    public $description = "Sitemap manager";

    /**
     * @var array list of supported models for displaying a sitemap
     */
    public $supportModels = [
        'pages' => 'wdmg\pages\models\Pages',
        'news' => 'wdmg\news\models\News',
    ];

    /**
     * @var int sitemap cache lifetime, `0` - for not use cache
     */
    public $cacheExpire = 43200; // 12 hr.

    /**
     * @var string the module version
     */
    private $version = "1.0.0";

    /**
     * @var integer, priority of initialization
     */
    private $priority = 4;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Set version of current module
        $this->setVersion($this->version);

        // Set priority of current module
        $this->setPriority($this->priority);

    }

    /**
     * {@inheritdoc}
     */
    public function dashboardNavItems($createLink = false)
    {
        $items = [
            'label' => $this->name,
            'url' => [$this->routePrefix . '/'. $this->id],
            'icon' => 'fa-sitemap',
            'active' => in_array(\Yii::$app->controller->module->id, [$this->id])
        ];
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap($app)
    {
        parent::bootstrap($app);

        if (isset(Yii::$app->params["sitemap.supportModels"]))
            $this->supportModels = Yii::$app->params["sitemap.supportModels"];

        if (isset(Yii::$app->params["sitemap.cacheExpire"]))
            $this->cacheExpire = Yii::$app->params["sitemap.cacheExpire"];

        if (!isset($this->supportModels))
            throw new InvalidConfigException("Required module property `supportModels` isn't set.");

        if (!isset($this->cacheExpire))
            throw new InvalidConfigException("Required module property `cacheExpire` isn't set.");

        if (!is_array($this->supportModels))
            throw new InvalidConfigException("Module property `supportModels` must be array.");

        if (!is_integer($this->cacheExpire))
            throw new InvalidConfigException("Module property `cacheExpire` must be integer.");

        // Add route to pass sitemap.xml in frontend
        $app->getUrlManager()->addRules([
            [
                'pattern' => 'sitemap',
                'route' => 'admin/sitemap/default',
                'suffix' => '.xml'
            ],
            'sitemap' => 'admin/sitemap/default',
        ], true);

        // Attach to events of create/change/remove of models for the subsequent clearing cache of sitemap
        if (!($app instanceof \yii\console\Application)) {
            if (is_array($models = $this->supportModels) && $cache = $app->getCache()) {
                foreach ($models as $name => $class) {
                    if (class_exists($class)) {
                        $model = new $class();
                        \yii\base\Event::on($class, $model::EVENT_AFTER_INSERT, function ($event) use ($cache) {
                            $cache->delete(md5('sitemap'));
                        });
                        \yii\base\Event::on($class, $model::EVENT_AFTER_UPDATE, function ($event) use ($cache) {
                            $cache->delete(md5('sitemap'));
                        });
                        \yii\base\Event::on($class, $model::EVENT_AFTER_DELETE, function ($event) use ($cache) {
                            $cache->delete(md5('sitemap'));
                        });
                    }
                }
            }
        }
    }
}