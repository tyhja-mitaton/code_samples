<?php

namespace common\actions\user;

use common\actions\EditableAction;
use common\models\user\enums\UserRoleEnum;
use Yii;

class RoleEditableAction extends EditableAction
{
    /**
     * @return array|mixed
     * @throws \yii\web\NotFoundHttpException
     */
    public function run()
    {
        $post = Yii::$app->request->post();
        $key = $post['editableKey'] ?? null;
        $model = $this->findModel($key);

        if (isset($post['hasEditable'])) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $attribute = $post;
            unset($attribute['hasEditable'], $attribute['editableKey'], $attribute['editableIndex']);
            $attribute = array_keys($attribute)[0];

            $oldValue = UserRoleEnum::tryFrom($oldValue ?? '')?->label();
            $userRoleName = $post[$attribute];
            if(empty($userRoleName)) {
                return ['output' => $oldValue, 'message' => Yii::t('user', 'Role cannot be blank')];
            }
            try {
                $model->setRole($userRoleName, Yii::$app->user->identity);
                $value = UserRoleEnum::tryFrom($userRoleName)?->label();

                return ['output' => $value, 'message' => ''];
            } catch (\Exception $exception) {
                return ['output' => $oldValue, 'message' => $exception->getMessage()];
            }
        }

        return $this->render('index', ['model' => $model]);
    }
}