<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $module wdmg\sitemap\Module */
/* @var $model wdmg\sitemap\models\Sitemap */

$this->title = Yii::t('app/modules/sitemap', 'Update URL');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app/modules/sitemap', 'Sitemap'), 'url' => ['sitemap/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sitemap-header">
    <h1><?= Html::encode($this->title) ?> <small class="text-muted pull-right">[v.<?= $module->version ?>]</small></h1>
</div>
<div class="sitemap-update">
    <?= $this->render('_form', [
        'model' => $model
    ]); ?>
</div>