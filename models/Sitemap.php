<?php

namespace wdmg\sitemap\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\httpclient\Client;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%sitemap}}".
 *
 * @property int $id
 * @property string $url
 * @property string $changefreq
 * @property int $priority
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 *
 * @property Sitemap $user
 */

class Sitemap extends ActiveRecord
{

    const SITEMAP_CHANGE_FREQ_ALWAYS = 'always';
    const SITEMAP_CHANGE_FREQ_HOURLY = 'hourly';
    const SITEMAP_CHANGE_FREQ_DAILY = 'daily';
    const SITEMAP_CHANGE_FREQ_WEEKLY = 'weekly';
    const SITEMAP_CHANGE_FREQ_MONTHLY = 'monthly';
    const SITEMAP_CHANGE_FREQ_YEARLY = 'yearly';
    const SITEMAP_CHANGE_FREQ_NEVER = 'never';

    public $module; // Base Sitemap module

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sitemap}}';
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (!($this->module = Yii::$app->getModule('admin/sitemap')))
            $this->module = Yii::$app->getModule('sitemap');

    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => new Expression('NOW()'),
            ],
            'blameable' =>  [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ]
        ];

        return $behaviors;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['url', 'changefreq', 'priority'], 'required'],
            ['url', 'unique', 'message' => Yii::t('app/modules/sitemap', 'The sitemap URL must be unique.'), 'on' => 'create'],
            ['url', 'string', 'max' => 255],
            ['url', 'checkSitemapUrl'],
            ['changefreq', 'string', 'max' => 16],
            ['changefreq', 'in', 'range' => $this->getFrequencyList(false, true)],
            ['priority', 'double', 'min' => 0.0, 'max' => 1.0],
            [['created_at', 'updated_at'], 'safe'],
        ];

        if (class_exists('\wdmg\users\models\Users') && (Yii::$app->hasModule('admin/users') || Yii::$app->hasModule('users'))) {
            $rules[] = [['created_by', 'updated_by'], 'safe'];
        }

        return $rules;
    }

    /**
     * Check sitemap URL has be exist
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function checkSitemapUrl($attribute, $params, $validator) {
        if ($this->url) {
            $client = new Client();
            $response = $client->get($this->url)->send();
            if (!$response->isOk && !(intval($response->headers["http-code"]) == 200 || intval($response->headers["http-code"]) == 301)) {
                $this->addError('url', Yii::t('app/modules/sitemap', 'The sitemap URL must be exist and returning 200/301 HTTP code.'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app/modules/sitemap', 'ID'),
            'url' => Yii::t('app/modules/sitemap', 'URL'),
            'changefreq' => Yii::t('app/modules/sitemap', 'Change frequency'),
            'priority' => Yii::t('app/modules/sitemap', 'Priority'),
            'created_at' => Yii::t('app/modules/sitemap', 'Created at'),
            'created_by' => Yii::t('app/modules/sitemap', 'Created by'),
            'updated_at' => Yii::t('app/modules/sitemap', 'Updated at'),
            'updated_by' => Yii::t('app/modules/sitemap', 'Updated by'),
        ];
    }

    /**
     * Forms an array of items for building a sitemap
     *
     * @return array
     */
    public function getSitemapItems($params = null) {
        $items = [];
        $module = $this->module;
        $query = self::find();

        // Apply filtering conditions
        if (!is_null($params)) {

            $this->load($params);
            if ($this->validate()) {

                $query->andFilterWhere([
                    'id' => $this->id,
                    'url' => $this->url,
                    'priority' => $this->priority,
                    'created_at' => $this->created_at,
                    'created_by' => $this->created_by,
                    'updated_at' => $this->updated_at,
                    'updated_by' => $this->updated_by,
                ]);

                if($this->changefreq !== "*")
                    $query->andFilterWhere(['like', 'changefreq', $this->changefreq]);

            }
        }

        // Get saved items
        if ($append = $query->indexBy('url')->asArray()->all())
            $items = ArrayHelper::merge($items, $append);

        // Get autogenerated items from support models
        if (is_array($models = $module->supportModels)) {

            foreach ($models as $name => $class) {

                // @TODO: Optimize default frequency
                if ($priority = $this->priority) {
                    if (!(floatval($module->defaultPriority) == floatval($priority))) {
                        break;
                    }
                }

                // @TODO: Optimize default priority
                if ($changefreq = $this->changefreq) {
                    if (!($module->defaultFrequency == $changefreq) && !$changefreq == '*') {
                        break;
                    }
                }

                // If class of model exist
                if (class_exists($class)) {

                    $model = new $class();

                    // If module is loaded
                    if ($model->getModule()) {
                        $append = [];
                        $model = new $class();

                        foreach ($model->getAllPublished(['in_sitemap' => true]) as $item) {
                            if (!is_null($item->url) && !isset($items[$item->url])) {
                                $append[$item->url] = [
                                    'url' => $item->url,
                                    'updated_at' => ($item->updated_at) ? $item->updated_at : false,
                                    'changefreq' => $module->defaultFrequency,
                                    'priority' => $module->defaultPriority
                                ];
                            }
                        };

                        if ($url = $this->url) {
                            $append = array_filter($append, function ($key) use ($url) {
                                return $key == $this->url;
                            }, ARRAY_FILTER_USE_KEY);
                        }

                        $items = ArrayHelper::merge($items, $append);
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Return array of frequency list
     *
     * @param bool $allFrequency
     * @param bool $onlyKeys
     * @return array
     */
    public static function getFrequencyList($allFrequency = false, $onlyKeys = false)
    {
        $list = [];
        if ($allFrequency)
            $list = [
                '*' => Yii::t('app/modules/sitemap', 'All frequency')
            ];

        $list = ArrayHelper::merge($list, [
            self::SITEMAP_CHANGE_FREQ_ALWAYS => Yii::t('app/modules/sitemap', 'Always'),
            self::SITEMAP_CHANGE_FREQ_HOURLY => Yii::t('app/modules/sitemap', 'Hourly'),
            self::SITEMAP_CHANGE_FREQ_DAILY => Yii::t('app/modules/sitemap', 'Daily'),
            self::SITEMAP_CHANGE_FREQ_WEEKLY => Yii::t('app/modules/sitemap', 'Weekly'),
            self::SITEMAP_CHANGE_FREQ_MONTHLY => Yii::t('app/modules/sitemap', 'Monthly'),
            self::SITEMAP_CHANGE_FREQ_YEARLY => Yii::t('app/modules/sitemap', 'Yearly'),
            self::SITEMAP_CHANGE_FREQ_NEVER => Yii::t('app/modules/sitemap', 'Never')
        ]);

        if ($onlyKeys) {
            unset($list['*']);
            return \array_keys($list);
        }

        return $list;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreated()
    {
        if (class_exists('\wdmg\users\models\Users') && (Yii::$app->hasModule('admin/users') || Yii::$app->hasModule('users')))
            return $this->hasOne(\wdmg\users\models\Users::class, ['id' => 'created_by']);
        else
            return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdated()
    {
        if (class_exists('\wdmg\users\models\Users') && (Yii::$app->hasModule('admin/users') || Yii::$app->hasModule('users')))
            return $this->hasOne(\wdmg\users\models\Users::class, ['id' => 'updated_by']);
        else
            return null;
    }
}
