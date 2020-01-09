<?php

namespace wdmg\sitemap\controllers;

use wdmg\sitemap\models\Sitemap;
use Yii;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use wdmg\sitemap\models\SitemapSearch;

/**
 * ListController implements the CRUD actions
 */
class ListController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'roles' => ['admin'],
                        'allow' => true
                    ],
                ],
            ],
        ];

        // If auth manager not configured use default access control
        if(!Yii::$app->authManager) {
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'roles' => ['@'],
                        'allow' => true
                    ],
                ]
            ];
        }

        return $behaviors;
    }

    /**
     * Lists all sitemap URL`s.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SitemapSearch();
        $params = Yii::$app->request->queryParams;
        $dataProvider = new ArrayDataProvider([
            'allModels' => $searchModel->getSitemapItems($params)
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'module' => $this->module
        ]);
    }


    /**
     * Creat new sitemap URL
     * @param null $url
     * @return string|\yii\web\Response
     */
    public function actionCreate($url = null)
    {
        $model = new Sitemap();

        // Creat new by sender URL
        if (!is_null($url)) {
            $model->url = $url;
            $model->changefreq = $this->module->defaultFrequency;
            $model->priority = $this->module->defaultPriority;
        }

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('app/modules/sitemap', 'Sitemap URL has been successfully added!')
                );
                return $this->redirect(['list/index']);
            } else {
                Yii::$app->getSession()->setFlash(
                    'danger',
                    Yii::t('app/modules/sitemap', 'An error occurred while add the URL to sitemap.')
                );
            }
        }

        return $this->render('create', [
            'module' => $this->module,
            'model' => $model
        ]);
    }

    /**
     * Update existing sitemap URL
     * @param $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $model = self::findModel($id);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('app/modules/sitemap', 'Sitemap URL has been successfully updated!')
                );
                return $this->redirect(['list/index']);
            } else {
                Yii::$app->getSession()->setFlash(
                    'danger',
                    Yii::t('app/modules/sitemap', 'An error occurred while update the URL from sitemap.')
                );
            }
        }

        return $this->render('update', [
            'module' => $this->module,
            'model' => $model
        ]);
    }

    /**
     * Delete all or one URL from sitemap
     *
     * @param null $id
     * @return \yii\web\Response
     */
    public function actionDelete($id = null)
    {
        if (!is_null($id)) {
            if ($id == 'all') {
                $model = new Sitemap();
                if ($model->deleteAll()) {
                    Yii::$app->getSession()->setFlash('success', Yii::t('app/modules/sitemap', 'OK! All URL`s successfully deleted from sitemap.'));
                } else {
                    Yii::$app->getSession()->setFlash('danger', Yii::t('app/modules/sitemap', 'An error occurred while deleting all URL`s from sitemap.'));
                }
            } else {
                $model = $this->findModel($id);
                if($model->delete()) {
                    Yii::$app->getSession()->setFlash('success', Yii::t('app/modules/sitemap', 'OK! URL successfully deleted from sitemap.'));
                } else {
                    Yii::$app->getSession()->setFlash('danger', Yii::t('app/modules/sitemap', 'An error occurred while deleting a URL from sitemap.'));
                }
            }
        }

        return $this->redirect(['list/index']);
    }

    /**
     * Clear sitemap cache
     *
     * @return mixed
     */
    public function actionClear()
    {
        if ($cache = Yii::$app->getCache()) {
            if ($cache->delete(md5('sitemap'))) {
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('app/modules/sitemap', 'Sitemap cache has been successfully flushing!')
                );
            } else {
                Yii::$app->getSession()->setFlash(
                    'danger',
                    Yii::t('app/modules/sitemap', 'An error occurred while flushing the sitemap cache.')
                );
            }
        } else {
            Yii::$app->getSession()->setFlash(
                'warning',
                Yii::t('app/modules/sitemap', 'Error! Cache component not configured in the application.')
            );
        }

        return $this->redirect(['list/index']);
    }

    /**
     * Finds the Newsletters model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ActiveRecord model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Sitemap::findOne(['id' => $id])) !== null)
            return $model;

        throw new NotFoundHttpException(Yii::t('app/modules/sitemap', 'The requested URL of sitemap does not found.'));
    }
}
