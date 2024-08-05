<?php

namespace app\models\user\traits;

use app\models\user\query\UserQuery;
use Yii;
use yii\helpers\ArrayHelper;

trait Finders
{
    /**
     * @return UserQuery
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    public static function getModerationAmount()
    {
        return self::find()->filterWhere(['status_moderation' => self::STATUS_MODERATION_NEW])->count();
    }

    public static function getList()
    {
        $users = self::find()->all();

        return ArrayHelper::map($users, 'id', 'username');
    }

    public function getRoleList()
    {
        $list = [];
        foreach (Yii::$app->authManager->getRoles() as $role) {
            $list[$role->name] = $role->name;
        }
        return $list;
    }

    public function getCustomerName()
    {
        if (empty($this->alias)) {
            return $this->username;
        }

        return Yii::$app->user->isGuest || Yii::$app->user->id != $this->id ? $this->alias : $this->username;
    }

    public static function getActiveList($clientsOnly = true)
    {
        $query = self::find()->notDeleted()->active();
        $users = $query->all();
        $usernameMap = ArrayHelper::map($users, 'id', 'username');
        $aliasMap = ArrayHelper::map($users, 'id', 'alias');
        if ($clientsOnly) {
            $clients = [];
            foreach ($usernameMap as $id => $username) {
                if (Yii::$app->authManager->checkAccess($id, self::ROLE_CLIENT)) {
                    $clients[$id] = $username;
                }
            }
            $usernameMap = $clients;
        }
        array_walk($usernameMap, function (&$item, $key, $aliases) {

            if (!empty($aliases[$key])) {
                if (Yii::$app->user->isGuest || Yii::$app->user->id != $key) {
                    $item = $aliases[$key];
                }
            }
        }, $aliasMap);

        return $usernameMap;
    }
}
