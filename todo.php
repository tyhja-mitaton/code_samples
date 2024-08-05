<?php
use app\components\widgets\checklist\Checklist;
use app\models\application\Application;
use app\models\publishing_checklist\ApplicationPublishingChecklist;
use app\models\publishing_checklist\PublishingChecklist;
use app\models\publishing_checklist\PublishingChecklistGroup;
use app\models\user\User;
use yii\bootstrap\Modal;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use app\models\user\UserPermission;
use unclead\multipleinput\MultipleInput;

/**
 * @var $model Application
 */
?>
<div class="card">
    <div class="card-body">
        <div class="py-4">
            <h4 class="card-title">
                Todo
            </h4>
            <?= MultipleInput::widget([
                'name' => 'todo-new',
                'max'               => 1,
                'min'               => 0,
                'allowEmptyList'    => true,
                'enableGuessTitle'  => false,
                'iconSource' => MultipleInput::ICONS_SOURCE_FONTAWESOME,
                'addButtonPosition' => MultipleInput::POS_HEADER,
                'addButtonOptions' => ['class' => 'add-todo'],
                'columns' => [[
                    'name'  => 'todo-items',
                    'type' => 'checkbox',
                ],
                    [
                        'name'  => 'todo-item-name',
                        'type' => 'textInput',
                        'inputTemplate' => '{input}',
                        'options' => [
                            'data-app-id' => $model->id,
                            'onchange' => <<<JS
                                        let appId = $(this).data('app-id');
                                        let todoName = $(this).val();
                                        if(todoName.length === 0) {
                                            $(this).parent().find('.help-block').text('Name cannot be empty');  
                                        } else if(todoName.length > 255) {
                                            $(this).parent().find('.help-block').text('Name is too long'); 
                                        } else {
                                            $.ajax({
                                                url: "/structure/todo/create",
                                                type: "POST",
                                                data: {id: appId, name: todoName},
                                                error: function(e) {
                                                    console.error(e.responseText)
                                                }
                                            }); 
                                        } 
                                    JS
                        ],
                    ]]
            ]) ?>
            <table class="sortable-table mt-3" style="width: 100%;"><tbody>
                <?php foreach ($model->todos as $key => $todo) {?>
                    <tr>
                        <td>
                            <div class="form-line row <?= $todo->is_done ? 'text-muted text-struck' : '' ?>">
                                <div class="col-10 handle">
                                    <label><?= Html::checkbox("todo$key", (bool)$todo->is_done, [
                                            'class' => 'todo-check',
                                            'data-id' => $todo->id
                                        ]) ?></label> <span class="todo-name" contentEditable="true"
                                                            data-id="<?= $todo->id ?>"><?= $todo->name ?></span>
                                </div>
                                <div class="col-1" style="padding-top: 3px;">
                                    <a class="text-danger delete-button" data-id="<?= $todo->id ?>" data-app-id="<?= $model->id ?>"><i class="fe fe-trash-2"></i></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody></table>
        </div>
    </div>
</div>