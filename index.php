<?php

use app\components\widgets\UserFilterWidget;
use app\models\currency\Currency;
use app\models\log\WithdrawalTransaction;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $this \yii\web\View */
/** @var $searchModel app\models\search\Withdrawal */
/** @var $dataProvider yii\data\ActiveDataProvider */
/* @var $currencies */
/* @var $users */

$this->title = Yii::t('withdraw', 'Вывод средств');
$this->params['breadcrumbs'][] = ['label' => 'Settings', 'url' => '/administrator'];
?>

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <section class="panel panel-default">
                <header class="panel-heading">
                    <h3 class="panel-title"><?= Yii::t('withdraw', 'Запросы на вывод средств') ?></h3>
                    <div class="panel-toolbar">
                    </div>
                </header>
                <div class="panel-body">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            'created_at' => [
                                'attribute' => 'created_at',
                                'headerOptions' => ['style' => 'width:250px'],
                                'value' => function (\app\models\search\Withdrawal $data) {
                                    return date('d.m.Y H:i', $data->created_at);
                                },
                                'filter' => \app\components\widgets\DateRangePickerDefault::widget([
                                    'model' => $searchModel,
                                    'attribute' => 'createTimeRange',
                                    'pluginOptions' => [
                                        'opens' => 'right'
                                    ],

                                ]),
                            ],
                            'user_id',
                            [
                                'attribute' => 'username',
                                'format' => 'raw',
                                'value' => function (WithdrawalTransaction $model) {
                                    if ($model->user) {
                                        return Html::a($model->user->username, Url::to(['/administrator/users/user/view', 'id' => $model->user_id]), ['target' => '_blank']);
                                    }
                                    return Html::a('#' . $model->user_id, Url::to(['/administrator/users/user/view', 'id' => $model->user_id]), ['target' => '_blank']);
                                },
                                'filter' => UserFilterWidget::widget([
                                    'id' => 'username_select2',
                                    'model' => $searchModel,
                                    'attribute' => 'username',
                                    'data' => $users,
                                    'theme' => UserFilterWidget::THEME_KRAJEE_BS5,
                                ])
                            ],
                            [
                                'attribute' => 'withdrawn_money_amount',
                                'value' => function ($model) {
                                    $currency = Currency::getIsoById($model->withdrawn_money_currency_id);
                                    if ($model->withdrawn_money_currency_id && $model->withdrawn_money_amount) {
                                        return Money::of(
                                                $model->withdrawn_money_amount,
                                                Yii::$app->moneyConverter->determineCurrency($model->withdrawn_money_currency_id),
                                                null,
                                                RoundingMode::DOWN
                                            )->getAmount() . ' ' . $currency;
                                    }
                                    return $model->withdrawn_money_amount . ' ' . $currency;
                                }
                            ],
                            [
                                'attribute' => 'fee',
                                'value' => function ($model) {
                                    $currency = Currency::getIsoById($model->withdrawn_money_currency_id);
                                    if ($model->withdrawn_money_currency_id && $model->fee) {
                                        return Money::of(
                                                $model->fee,
                                                Yii::$app->moneyConverter->determineCurrency($model->withdrawn_money_currency_id),
                                                null,
                                                RoundingMode::DOWN
                                            )->getAmount() . ' ' . $currency;
                                    }
                                    return $model->fee . ' ' . $currency;
                                }
                            ],
                            [
                                'attribute' => 'amountWithFee',
                                'value' => function ($model) {
                                    $currency = Currency::getIsoById($model->withdrawn_money_currency_id);
                                    if ($model->withdrawn_money_currency_id && $model->amountWithFee) {
                                        return Money::of(
                                                $model->amountWithFee,
                                                Yii::$app->moneyConverter->determineCurrency($model->withdrawn_money_currency_id),
                                                null,
                                                RoundingMode::DOWN
                                            )->getAmount() . ' ' . $currency;
                                    }
                                    return $model->amountWithFee . ' ' . $currency;
                                }
                            ],
                            [
                                'attribute' => 'address',
                                'value' => function (WithdrawalTransaction $transaction) {
                                    return implode('<br>', $transaction->getFullAddress());
                                },
                                'format' => 'html',
                            ],
                            [
                                'attribute' => 'payment_system_type',
                                'value' => function (WithdrawalTransaction $model) {
                                    return $model->paymentSystemType ? $model->paymentSystemType->name : null;
                                },
                                'label' => 'Платёжная система',
                                'filterInputOptions' => [
                                    'class' => "form-control select2-payment-filter",
                                    'id' => 'payment_system_type'
                                ],
                                'filter' => []
                            ],
                            [
                                'attribute' => 'payment_system',
                                'value' => function (WithdrawalTransaction $model) {
                                    return $model->paymentSystem ? $model->paymentSystem->name : null;
                                },
                                'label' => 'Шлюз',
                                'filter' => \app\models\payment\system\PaymentSystem::getList()
                            ],
                            [
                                'attribute' => 'txid',
                                'value' => function (WithdrawalTransaction $transaction) {
                                    $url = $transaction->txIdUrl;
                                    if (!empty($url)) {
                                        return Html::a($transaction->txid, $url, ['target' => '_blank']);
                                    }
                                    return $transaction->txid;
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function (WithdrawalTransaction $model) {
                                    return $model->statusName;
                                },
                                'filter' => $searchModel::statuses(),
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{approve} {reject}',
                                'headerOptions' => ['style' => 'width:85px'],
                                'buttons' => [
                                    'approve' => function ($url, $model) {
                                        /**
                                         * @var WithdrawalTransaction $model
                                         */
                                        if ($model->getFlag('is_withdraw_manual')) {
                                            return \yii\helpers\Html::a('<span class="fa fa-thumbs-up"></span>', $url, [
                                                'title' => Yii::t('yii', 'Одобрить вручную'),
                                                'data-confirm' => Yii::t('yii', 'Выплата проводится вручную. Уверены, что хотите одобрить?'),
                                                'data-method' => 'post',
                                                'class' => 'btn btn-sm btn-default bg-success-100',
                                                'data-toggle' => 'tooltip',
                                                'data-placement' => 'top',
                                            ]);
                                        }
                                        return \yii\helpers\Html::a('<span class="fa fa-check-circle-o"></span>', $url, [
                                            'title' => Yii::t('yii', 'Одобрить'),
                                            'data-confirm' => Yii::t('yii', 'Уверены, что хотите одобрить?'),
                                            'data-method' => 'post',
                                            'class' => 'btn btn-sm btn-default bg-success-100',
                                            'data-toggle' => 'tooltip',
                                            'data-placement' => 'top',
                                        ]);
                                    },
                                    'reject' => function ($url, $model) {
                                        return \yii\helpers\Html::a('<span class="fa fa-ban"></span>', $url, [
                                            'title' => Yii::t('yii', 'Отклонить'),
                                            'data-confirm' => Yii::t('yii', 'Уверены, что хотите отклонить?'),
                                            'data-method' => 'post',
                                            'class' => 'btn btn-sm btn-default bg-danger-100',
                                            'data-toggle' => 'tooltip',
                                            'data-placement' => 'top',
                                        ]);
                                    },
                                    'view' => function ($url, $model) {
                                        return \yii\helpers\Html::a('<span class="fa fa-eye"></span>', $url, [
                                            'title' => Yii::t('yii', 'Просмотр'),
                                            'class' => 'btn btn-sm btn-default bg-success-100',
                                            'data-toggle' => 'tooltip',
                                            'data-placement' => 'top',
                                        ]);
                                    },
                                ],
                                'visibleButtons' => [
                                    'approve' => function (WithdrawalTransaction $model) {
                                        return $model->canBeApproved(Yii::$app->user->identity);
                                    },
                                    'reject' => function (WithdrawalTransaction $model) {
                                        return $model->canBeRejected(Yii::$app->user->identity);
                                    },
                                ]
                            ],
                        ],
                        'rowOptions' => function (WithdrawalTransaction $model, $index, $widget, $grid) {
                            if ($model->status == $model::STATUS_REJECTED) {
                                return ['class' => 'danger'];
                            } elseif ($model->status == $model::STATUS_APPROVED) {
                                return ['class' => 'success'];
                            }

                            return [];
                        },
                    ]); ?>
                </div>
            </section>
        </div>
    </div>
    <script>
        $("select.select2-payment-filter").select2({
            placeholder: "",
            allowClear: true,
            minimumInputLength: 3,
            ajax: {
                url: "load-system-types",
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
<?php
$this->registerCssFile('@web/local/css/daterangepicker.css', [
    'depends' => \app\assets\AppAsset::class
]);
$this->registerJsFile('/base/plugins/select2/dist/js/select2.min.js', [
    'depends' => \app\assets\AppAsset::class,
    'position' => \yii\web\View::POS_HEAD
]);
