<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\Statistics */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var array $summary */

$this->registerCss('
.pagination {
    padding-top: 10px;
}

.table-bordered, .table-bordered th, .table-bordered td {
    border: 1px solid #EFF2F5;
}

.category-color a {
    color: #1E1E2D;
}

tfoot tr td {
    background: #373750 !important;
    color: #fff !important;
}

#w1::-webkit-scrollbar-thumb {
    background-color: #009EF7;
}
');

$columns = [];

if (!empty($searchModel->groupBy)) {
    foreach ($searchModel->groupBy as $id => $field) {
        if ($id == 0) {
            $columns[] = [
                'attribute'     => 'report' . $id,
                'label'         => $searchModel->getReportFieldTitle($field),
                'group'           => $searchModel->getReportFieldTitle($field),
                'value'         => function (\app\models\search\Statistics $statistics) use ($searchModel, $id, $field) {
                    $statistics->groupBy = $searchModel->groupBy;
                    $statistics->event_date = $searchModel->event_date;
                    $statistics->internal = $searchModel->internal;
                    $statistics->setScenario($searchModel->scenario);

                    $value = $statistics->getReportFieldValue($field, $id, Yii::$app->user->can(\app\models\user\User::ROLE_MANAGER));
                    if ($value == $statistics::DELETED_NAME) {
                        return Html::tag('span', $value, ['style' => 'opacity: 0.3']);
                    }

                    return $value;
                },
                'class'         => \app\components\DataColumn::class,
                'headerOptions' => [
                    'style' => [
                        'border-bottom' => 'none',
                    ]
                ],
                'filterOptions' => [
                    'style' => [
                        'border-top' => 'none',
                        'background' => '#F3F6F9'
                    ]
                ],
                'format'        => 'raw',
                'footer' => '<strong>Total</strong>',
            ];
        } else {
            $columns[] = [
                'attribute'     => 'report' . $id,
                'label'         => $searchModel->getReportFieldTitle($field),
                'group'           => $searchModel->getReportFieldTitle($field),
                'value'         => function (\app\models\search\Statistics $statistics) use ($searchModel, $id, $field) {
                    $statistics->groupBy = $searchModel->groupBy;
                    $statistics->event_date = $searchModel->event_date;
                    $statistics->internal = $searchModel->internal;
                    $statistics->setScenario($searchModel->scenario);

                    $value = $statistics->getReportFieldValue($field, $id, Yii::$app->user->can(\app\models\user\User::ROLE_MANAGER));
                    if ($value == $statistics::DELETED_NAME) {
                        return Html::tag('span', $value, ['style' => 'opacity: 0.3']);
                    }

                    return $value;
                },
                'class'         => \app\components\DataColumn::class,
                'headerOptions' => [
                    'style' => [
                        'border-bottom' => 'none',
                    ]
                ],
                'filterOptions' => [
                    'style' => [
                        'border-top' => 'none',
                        'background' => '#F3F6F9'
                    ]
                ],
                'format'        => 'raw',
                'footer'        => '',
            ];
        }
    }
}


$fields = $searchModel->fields;
if (empty($fields)) {
    $fields = array_keys($searchModel->reportColumns());
}
foreach ($fields as $id => $field) {
    if(isset($searchModel->addNotAggregateData()[$field])) {
        unset($fields[$id]);
    }
}
$fieldsGroupped = [];
foreach ($fields as $field) {
    if (isset($searchModel->reportColumns()[$field])) {
        $settings = $searchModel->reportColumns()[$field];
        if (!isset($fieldsGroupped[$settings['group']])) {
            $fieldsGroupped[$settings['group']] = [];
        }
        $settings['field'] = $field;
        $fieldsGroupped[$settings['group']][] = $settings;
    }
}
foreach ($fieldsGroupped as $group => $fields) {
    $index = 0;
    foreach ($fields as $settings) {
        if (isset($settings['visible']) && !$settings['visible']) {
            continue;
        }
        $fieldName = $settings['field'];
        if (count($fields) > 1) {
            $colspan = 0;
            foreach ($fields as $field) {
                if (!isset($field['visible']) || $field['visible']) {
                    $colspan++;
                }
            }
            $columns[] = [
                'attribute' => $fieldName,
                'group' => $group,
                'label' => $index == 0 ? $group : false,
                'filter' => $settings['title'],
                'value'         => isset($settings['value']) ? $settings['value'] : null,
                'headerOptions' => $index == 0 ? [
                    'colspan' => $colspan,
                    'style' => [
                        'border-bottom' => 'none',
                    ],
                ] : [
                    'style' => [
                        'display' => 'none',
                        'border-bottom' => 'none',
                    ],
                ],
                'filterOptions' => [
                    'style' => [
                        'border-top' => 'none',
                        'background' => '#F3F6F9'
                    ],
                    'class' => 'category-color'
                ],
                'format' => 'raw',
                'visible' => (!empty($settings['admin']) ? Yii::$app->user->can(\app\models\user\User::ROLE_MANAGER) : true) && (!isset($settings['visible']) || $settings['visible']),
                'class' => \app\components\MergedDataColumn::class,
                'footer' => isset($settings['footer']) ? $settings['footer']($summary) : $searchModel->getSummaryField($summary, $fieldName),
                'contentOptions' => \yii\helpers\ArrayHelper::merge([
                    'class' => 'data-field'
                ], isset($settings['options']) ? $settings['options'] : []),
                'footerOptions' => \yii\helpers\ArrayHelper::merge([
                    'class' => 'data-field',
                ], isset($settings['options']) ? $settings['options'] : []),
            ];
        } else {
            $columns[] = [
                'attribute'     => $fieldName,
                'group' => $group,
                'label'         => $group,
                'value'         => isset($settings['value']) ? $settings['value'] : (function(\app\models\Statistics $statistics) use ($fieldName) {
                    return $statistics->{$fieldName};
                }),
                'headerOptions' => [
                    'style' => [
                        'border-bottom' => 'none',
                    ]
                ],
                'filterOptions' => [
                    'style' => [
                        'border-top' => 'none',
                        'background' => '#F3F6F9'
                    ]
                ],
                'format' => 'raw',
                'visible' => (!empty($settings['admin']) ? Yii::$app->user->can(\app\models\user\User::ROLE_MANAGER) : true) && (!isset($settings['visible']) || $settings['visible']),
                'class'         => \app\components\MergedDataColumn::class,
                'footer'        => isset($settings['footer']) ? $settings['footer']($summary) : $searchModel->getSummaryField($summary, $fieldName),
                'contentOptions' => \yii\helpers\ArrayHelper::merge([
                    'class' => 'data-field'
                ], isset($settings['options']) ? $settings['options'] : []),
                'footerOptions' => \yii\helpers\ArrayHelper::merge([
                    'class' => 'data-field',
                ], isset($settings['options']) ? $settings['options'] : []),
            ];
        }
        $index++;
    }
}

if (Yii::$app->request->get('from') == 'partner') {
    [$columns[0], $columns[1]] = [$columns[1], $columns[0]];
}

?>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'showFooter' => true,
    'columns'      => $columns,
    'tableOptions' => [
        'class' => 'table table-bordered',
    ],
    'pager' => [
        'class' => \app\components\widgets\CustomLinkPager::class,

        'pageCssClass' => 'page-item',
        'firstPageCssClass' => 'page-item first',
        'lastPageCssClass' => 'page-item last',
        'prevPageCssClass' => 'page-item prev',
        'nextPageCssClass' => 'page-item next',
        'nextPageLabel' => '',
        'prevPageLabel' => '',
        'linkOptions' => [
            'class' => 'page-link',
        ],
    ],
]); ?>