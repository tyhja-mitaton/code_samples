<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\release\Release */
/* @var $appList array */

$this->title = Yii::t('app', 'Update Release: {name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Releases'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<!-- page-header -->
<div class="page-header">
    <h1 class="page-title"><span class="subpage-title"><?= Html::encode($this->title) ?></h1>
    <div class="ml-auto">
        <div class="input-group">
            <?= Html::a('<span><i class="fe fe-corner-up-left"></i> Back</span>', ['index'], ['class' => 'btn btn-warning btn-icon mr-2', 'data-toggle' => 'tooltip', 'title' => '', 'data-placement' => 'bottom', 'data-original-title' => 'Chat']) ?>
            <?= Html::a('<span><i class="fe fe-trash"></i> Delete</span>', ['delete', 'id' => $model->id], ['class' => 'btn btn-danger btn-icon mr-2', 'data-toggle' => 'tooltip', 'title' => '', 'data-placement' => 'bottom', 'data-original-title' => 'Delete', 'data-method' => 'post']) ?>
            <?= Html::a('<span><i class="fe fe-plus"></i> Create</span>', ['create'], ['class' => 'btn btn-info btn-icon mr-2', 'data-toggle' => 'tooltip', 'title' => '', 'data-placement' => 'bottom', 'data-original-title' => 'Add New']) ?>
        </div>
    </div>
</div>
<!-- End page-header -->
<?= $this->render('_form', [
    'model' => $model,
    'appList' => $appList,
]) ?>

