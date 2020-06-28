<?php

namespace wdmg\sitemap;

/**
 * Yii2 Sitemap manager
 *
 * @category        Module
 * @version         1.1.4
 * @author          Alexsander Vyshnyvetskyy <alex.vyshnyvetskyy@gmail.com>
 * @link            https://github.com/wdmg/yii2-sitemap
 * @copyright       Copyright (c) 2019 - 2020 W.D.M.Group, Ukraine
 * @license         https://opensource.org/licenses/MIT Massachusetts Institute of Technology (MIT) License
 *
 */

use Yii;
use wdmg\base\BaseModule;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

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
    public $defaultRoute = "list/index";

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
        'blog' => 'wdmg\blog\models\Posts'
    ];

    /**
     * @var int sitemap cache lifetime, `0` - for not use cache
     */
    public $cacheExpire = 43200;

    /**
     * @var string default update frequency for sitemap.xml items
     * See https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    public $defaultFrequency = 'weekly';

    /**
     * @var float default update priority for sitemap.xml items
     * See https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    public $defaultPriority = 0.5;

    /**
     * @var string default route to rendered sitemap.xml (use "/" - for root)
     */
    public $sitemapRoute = "/";

    /**
     * @var string the module version
     */
    private $version = "1.1.4";

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

        // Process and normalize route for sitemap in frontend
        $this->sitemapRoute = self::normalizeRoute($this->sitemapRoute);

    }

    /**
     * {@inheritdoc}
     */
    public function dashboardNavItems($createLink = false)
    {
        $items = [
            'label' => $this->name,
            'icon' => 'fa fa-fw fa-sitemap',
            'url' => [$this->routePrefix . '/'. $this->id],
            'active' => (in_array(\Yii::$app->controller->module->id, [$this->id]) &&  Yii::$app->controller->id == 'list'),
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

        if (isset(Yii::$app->params["sitemap.defaultFrequency"]))
            $this->defaultFrequency = Yii::$app->params["sitemap.defaultFrequency"];

        if (isset(Yii::$app->params["sitemap.defaultPriority"]))
            $this->defaultPriority = Yii::$app->params["sitemap.defaultPriority"];

        if (isset(Yii::$app->params["sitemap.sitemapRoute"]))
            $this->sitemapRoute = Yii::$app->params["sitemap.sitemapRoute"];

        if (!isset($this->supportModels))
            throw new InvalidConfigException("Required module property `supportModels` isn't set.");

        if (!isset($this->cacheExpire))
            throw new InvalidConfigException("Required module property `cacheExpire` isn't set.");

        if (!isset($this->defaultFrequency))
            throw new InvalidConfigException("Required module property `defaultFrequency` isn't set.");

        if (!isset($this->defaultPriority))
            throw new InvalidConfigException("Required module property `defaultPriority` isn't set.");

        if (!isset($this->sitemapRoute))
            throw new InvalidConfigException("Required module property `sitemapRoute` isn't set.");

        if (!is_array($this->supportModels))
            throw new InvalidConfigException("Module property `supportModels` must be array.");

        if (!is_integer($this->cacheExpire))
            throw new InvalidConfigException("Module property `cacheExpire` must be integer.");

        if (!is_string($this->defaultFrequency))
            throw new InvalidConfigException("Module property `defaultFrequency` must be a string.");

        if (!in_array($this->defaultFrequency, ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']))
            throw new InvalidConfigException("Module property `defaultFrequency` must be one of the values: always, hourly, daily, weekly, monthly, yearly, never.");

        if (!is_float($this->defaultPriority))
            throw new InvalidConfigException("Module property `defaultPriority` must be float integer.");

        if (!is_string($this->sitemapRoute))
            throw new InvalidConfigException("Module property `sitemapRoute` must be a string.");

        // Add route to pass sitemap.xml in frontend
        $sitemapRoute = $this->sitemapRoute;
        if (empty($sitemapRoute) || $sitemapRoute == "/") {
            $app->getUrlManager()->addRules([
                [
                    'pattern' => '/sitemap',
                    'route' => 'admin/sitemap/default',
                    'suffix' => '.xml'
                ],
                '/sitemap.xml' => 'admin/sitemap/default'
            ], true);
        } else if (is_string($sitemapRoute)) {
            $app->getUrlManager()->addRules([
                [
                    'pattern' => $sitemapRoute . '/sitemap',
                    'route' => 'admin/sitemap/default',
                    'suffix' => '.xml'
                ],
                $sitemapRoute . '/sitemap.xml' => 'admin/sitemap/default'
            ], true);
        }

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

    /**
     * Generate current sitemap.xml URL
     *
     * @return null|string
     */
    public function getSitemapURL() {
        $url = null;
        $sitemapRoute = $this->sitemapRoute;
        if (empty($sitemapRoute) || $sitemapRoute == "/") {
            $url = Url::to('/sitemap.xml', true);
        } else {
            $url = Url::to($sitemapRoute . '/sitemap.xml', true);
        }
        return $url;
    }
}