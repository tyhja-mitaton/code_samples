<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\release\Release */

$this->title = date('d.m.Y', $model->date);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Releases'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => date('d.m.Y', $model->date), 'url' => \yii\helpers\Url::to(['view', 'id' => $model->id])];

$this->params['links'][] = ['label' => 'Releases List', 'url' => \yii\helpers\Url::to(['index'])];
$this->params['links'][] = ['label' => 'Create New', 'url' => \yii\helpers\Url::to(['create'])];
$this->params['links'][] = ['label' => 'Update', 'url' => \yii\helpers\Url::to(['update', 'id' => $model->id])];
\yii\web\YiiAsset::register($this);
?>
<!-- page-header -->
<div class="page-header">
    <h1 class="page-title"><span class="subpage-title"><?= Html::encode($this->title) ?></h1>
    <div class="ml-auto">
        <div class="input-group">
            <a href="<?= \yii\helpers\Url::to(['index']) ?>" class="btn btn-warning btn-icon mr-2 app-crud-btn" data-toggle="tooltip" title="" data-placement="bottom" data-original-title="Chat">
                <span><i class="fe fe-corner-up-left" style="font-size: 1em;"></i></span>
                <div class="hidden-xs">&nbsp;Back</div>
            </a>
            <a href="<?= \yii\helpers\Url::to(['update', 'id' => $model->id]) ?>" class="btn btn-info btn-icon mr-2 app-crud-btn" data-toggle="tooltip" title="" data-placement="bottom" data-original-title="Add New">
                <span><i class="fe fe-edit" style="font-size: 1em;"></i></span>
                <div class="hidden-xs">&nbsp;Update</div>
            </a>
            <a href="<?= \yii\helpers\Url::to(['send', 'id' => $model->id]) ?>" class="btn btn-green btn-icon mr-2 app-crud-btn" data-toggle="tooltip" title="" data-placement="bottom" data-original-title="Send">
                <span><i class="fe fe-send" style="font-size: 1em;"></i></span>
                <div class="hidden-xs">&nbsp;Send data</div>
            </a>
        </div>
    </div>
</div>
<!-- End page-header -->
<div class="row">
    <div class="col-xs-12 col-md-12">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="card">
                    <div class="card-body">
                        <div class="py-4">
                            <h4 class="card-title">Release data</h4>
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [
                                    'id',
                                    'date:date',
                                    'comments:ntext',
                                ],
                            ]) ?>
                            <?= \yii\grid\GridView::widget([
                                'tableOptions' => [
                                    'class' => 'table card-table table-vcenter',
                                    'style' => 'border: 0',
                                ],
                                'showHeader'=> false,
                                'dataProvider' => new \yii\data\ArrayDataProvider(['models' => $model->lines, 'key' => 'id']),
                                'columns' => [
                                    [
                                        'attribute' => 'application_id',
                                        'contentOptions' => [
                                            'style' => [
                                                'word-break' => 'break-word',
                                            ],
                                        ],
                                        'format' => 'raw',
                                        'value' => function (\app\models\release\ReleaseLine $releaseLine) {
                                            return Html::a('#' . $releaseLine->application->id . ' ' . $releaseLine->application->name,
                                                ['/structure/application/view', 'id' => $releaseLine->application->id]);
                                        },
                                    ],
                                    [
                                        'attribute' => 'tool_version',
                                        'value' => function (\app\models\release\ReleaseLine $releaseLine) {
                                            return isset($releaseLine->application->tool_version) ? "v{$releaseLine->application->tool_version}" : 'v0.0.0';
                                        },
                                        'format' => 'raw',
                                        'contentOptions' => [
                                            'style' => [
                                                'width' => '60px',
                                            ]
                                        ]
                                    ],
                                    [
                                        'attribute' => 'status',
                                        'value' => function (\app\models\release\ReleaseLine $releaseLine) {
                                            return Html::tag('span', \app\models\application\Application::statuses()[$releaseLine->application->status], [
                                                'class' => 'badge badge-' . \app\models\application\Application::statusesColor()[$releaseLine->application->status],
                                            ]);
                                        },
                                        'format' => 'raw',
                                        'contentOptions' => [
                                            'style' => [
                                                'width' => '120px',
                                            ]
                                        ]
                                    ],
                                    [
                                        'attribute' => 'application.donorAccount.fingerprint_name',
                                        'label' => 'Donor',
                                        'contentOptions' => [
                                            'style' => [
                                                'width' => '200px',
                                            ]
                                        ],
                                        'value' => function(\app\models\release\ReleaseLine $releaseLine) {
                                            if (empty($releaseLine->application->donorAccount->fingerprint_name)) {
                                                return null;
                                            }
                                            return '→ ' . $releaseLine->application->donorAccount->fingerprint_name;
                                        }
                                    ],
                                ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


