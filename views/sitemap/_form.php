<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $model wdmg\sitemap\models\Sitemap */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="sitemap-form">
    <?php $form = ActiveForm::begin([
        'id' => "addSitemapURLForm"
    ]); ?>
    <?= $form->field($model, 'url'); ?>
    <?= $form->field($model, 'changefreq')->widget(SelectInput::class, [
        'items' => $model->getFrequencyList(false, false),
        'options' => [
            'class' => 'form-control'
        ]
    ]); ?>
    <?= $form->field($model, 'priority'); ?>
    <hr/>
    <div class="form-group">
        <?= Html::a(Yii::t('app/modules/sitemap', '&larr; Back to list'), ['sitemap/index'], ['class' => 'btn btn-default pull-left']) ?>&nbsp;
        <?= Html::submitButton(Yii::t('app/modules/sitemap', 'Save'), ['class' => 'btn btn-success pull-right']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>