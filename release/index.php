<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\Release */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Releases');
$this->params['breadcrumbs'][] = $this->title;
?>
<!-- page-header -->
<div class="page-header">
    <h1 class="page-title"><span class="subpage-title"><?= Html::encode($this->title) ?></h1>
    <div class="ml-auto">
        <div class="input-group">
            <a href="<?= \yii\helpers\Url::to(['create']) ?>" class="btn btn-info btn-icon mr-2"
               data-toggle="tooltip" title="" data-placement="bottom" data-original-title="Add New">
                    <span>
                        <i class="fe fe-plus"></i> Create
                    </span>
            </a>
        </div>
    </div>
</div>
<!-- End page-header -->

<div class="row">
    <div class="col-md-12 col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div>
                    <h3 class="card-title">Release list</h3>
                </div>
            </div>
            <div class="table-responsive">
                <?= GridView::widget([
                    'id' => 'releases',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'headerOptions' => [
                                'style' => [
                                    'width' => '70px',
                                ]
                            ]
                        ],
                        [
                            'attribute' => 'date',
                            'value' => function($model) {
                                return Yii::$app->formatter->asDate($model->date);
                            },
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'attribute' => 'dateRange',
                                'convertFormat' => true,
                                'startAttribute' => 'dateStart',
                                'endAttribute' => 'dateEnd',
                                'presetDropdown' => true,
                                'pluginOptions' => [
                                    "timePicker24Hour" => true,
                                    'locale' => [
                                        'format' => 'Y-m-d H:i',
                                        'showDropdowns' => true,
                                        'firstDay' => 1,
                                    ]
                                ],
                            ]),
                            'headerOptions' => [
                                'style' => [
                                    'width' => '120px',
                                ]
                            ]
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'headerOptions' => [
                                'style' => [
                                    'width' => '50px',
                                ]
                            ]
                        ],
                        [
                            'attribute' => 'application',
                            'format' => 'raw',
                            'value' => function($model) {
                                return GridView::widget([
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
                                    ]);
                            },
                            'filterInputOptions' => [
                                'class' => "form-control select2-app-list-filter",
                                'id' => 'application_list'
                            ],
                            'filter' => []
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
<script>
    $("select.select2-app-list-filter").select2({
        placeholder: "",
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            url: "/structure/release/application-list",
            dataType: "json",
            type: "GET",
            data: function (params) {
                var query = {
                    search: params.term
                }

                return query;
            },
            processResults: function (data) {
                let transformedData = [];
                data.forEach(function (item, i, arr) {
                    transformedData.push(JSON.parse('{"id": ' + item.id + ', "text": "' + item.name + '"}'));
                });
                return {
                    results: transformedData
                };
            }
        }
    });
</script>
