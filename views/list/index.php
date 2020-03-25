<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app/modules/sitemap', 'Sitemap');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="page-header">
    <h1>
        <?= Html::encode($this->title) ?> <small class="text-muted pull-right">[v.<?= $module->version ?>]</small>
    </h1>
    <?php if ($sitemap_url = $module->getSitemapURL()) : ?>
    <p><?= Yii::t('app/modules/sitemap', 'Sitemap of the current site is available at: {url}',
        ['url' => Html::a($sitemap_url, $sitemap_url, ['target' => '_blank', 'data-pjax' => 0])]
    ) ?></p>
    <?php endif; ?>
</div>
<div class="sitemap-index">
    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '{summary}<br\/>{items}<br\/>{summary}<br\/><div class="text-center">{pager}</div>',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => function($data) {
                    return Html::a($data['url'], $data['url'], [
                        'target' => '_blank',
                        'data-pjax' => 0
                    ]);
                }
            ],
            [
                'attribute' => 'changefreq',
                'format' => 'html',
                'filter' => SelectInput::widget([
                    'model' => $searchModel,
                    'attribute' => 'changefreq',
                    'items' => $searchModel->getFrequencyList(true, false),
                    'options' => [
                        'class' => 'form-control'
                    ]
                ]),
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    return $data['changefreq'];
                }
            ],
            [
                'attribute' => 'priority',
                'format' => 'raw',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
            ],
            [
                'attribute' => 'id',
                'label' => Yii::t('app/modules/sitemap', 'Source'),
                'format' => 'html',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    if (isset($data['id']))
                        return '<span class="label label-info" title="'.Yii::t('app/modules/sitemap', 'URL has been added manually').'">'.Yii::t('app/modules/sitemap', 'manually').'</span>';
                    else
                        return '<span class="label label-warning" title="'.Yii::t('app/modules/sitemap', 'URL was auto generated using supported model data').'">'.Yii::t('app/modules/sitemap', 'auto').'</span>';
                }
            ],
            [
                'attribute' => 'updated_at',
                'format' => 'raw',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => Yii::t('app/modules/sitemap', 'Actions'),
                'contentOptions' => [
                    'class' => 'text-center',
                    'style' => 'min-width:120px',
                ],
                'urlCreator' => function($action, $data, $key, $index) {
                    if ($action == 'update') {
                        if (isset($data['id']))
                            return ['list/update', 'id' => $data['id']];
                        else
                            return ['list/create', 'url' => $data['url']];
                    } else if ($action == 'delete' && isset($data['id'])) {
                        return ['list/delete', 'id' => $data['id']];
                    }
                },
                'visibleButtons' => [
                    'view' => false,
                    'update' => function($data, $key, $index) {
                        return true;
                    },
                    'delete' => function($data, $key, $index) {
                        if (isset($data['id']))
                            return true;
                        else
                            return false;
                    }
                ]
            ],
        ],
        'pager' => [
            'options' => [
                'class' => 'pagination',
            ],
            'maxButtonCount' => 5,
            'activePageCssClass' => 'active',
            'prevPageCssClass' => '',
            'nextPageCssClass' => '',
            'firstPageCssClass' => 'previous',
            'lastPageCssClass' => 'next',
            'firstPageLabel' => Yii::t('app/modules/sitemap', 'First page'),
            'lastPageLabel'  => Yii::t('app/modules/sitemap', 'Last page'),
            'prevPageLabel'  => Yii::t('app/modules/sitemap', '&larr; Prev page'),
            'nextPageLabel'  => Yii::t('app/modules/sitemap', 'Next page &rarr;')
        ],
    ]); ?>
    <hr/>
    <div>
        <div class="btn-group">
            <?= Html::a(Yii::t('app/modules/sitemap', 'Clear cache'), ['list/clear'], ['class' => 'btn btn-info']) ?>
            <?= Html::a(Yii::t('app/modules/sitemap', 'Delete all URL'), ['list/delete', 'id' => 'all'], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app/modules/sitemap', 'Are you really sure you want to delete all URLs? This action does not affect the URLs that are generated in automatic mode.'),
                    'method' => 'post',
                ],
            ]) ?>
        </div>
        <?= Html::a(Yii::t('app/modules/sitemap', 'Add URL'), ['list/create'], ['class' => 'btn btn-success pull-right']) ?>
    </div>
    <?php Pjax::end(); ?>
</div>

<?php echo $this->render('../_debug'); ?>
