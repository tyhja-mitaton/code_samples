<?php

namespace app\models\user\traits;


use app\models\user\UserProfile;

/**
 * @property UserProfile $userProfile
 */
trait Relations
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserProfile()
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }
}
