<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\release\Release */
/* @var $form yii\widgets\ActiveForm */
/* @var $appList array */
?>

<?php $form = ActiveForm::begin(); ?>
<div class="row">
    <div class="col-md-12 col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div>
                    <h3 class="card-title">Release data</h3>
                </div>
            </div>
            <div class="card-body">
                <?= $form->field($model, 'releasedAt')->input('date') ?>

                <?= $form->field($model, 'appIds[]')->dropDownList($appList, ['multiple' => true]) ?>

                <?= $form->field($model, 'comments')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
<script>
    $("select#release-appids").select2({
        placeholder: "",
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            url: "application-list",
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
    $("select#release-appids").val(<?=json_encode($model->getAppIds())?>);
    $("select#release-appids").trigger('change');
</script>
