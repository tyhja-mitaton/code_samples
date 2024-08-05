<?php

namespace app\models\user;

use app\models\user\traits\Auth;
use app\models\user\traits\Finders;
use app\models\user\traits\Relations;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $access_token
 * @property string $password_hash
 * @property string $oauth_client
 * @property string $oauth_client_user_id
 * @property string $email
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $logged_at
 * @property bool $is_deleted
 * @property integer $status_moderation
 * @property string $sub_id
 *
 * @property string $customerName
 */
class User extends ActiveRecord implements IdentityInterface
{
    use Auth, Finders, Relations;

    const STATUS_NOT_ACTIVE = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_DELETED = 3;

    const STATUS_MODERATION_NEW = 0;
    const STATUS_MODERATION_ACCEPT = 1;
    const STATUS_MODERATION_DECLINE = 2;

    const ROLE_CLIENT = 'client';
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_SUPERADMINISTRATOR = 'superadministrator';

    const EVENT_AFTER_SIGNUP = 'afterSignup';
    const EVENT_AFTER_LOGIN = 'afterLogin';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            'auth_key' => [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'auth_key'
                ],
                'value' => Yii::$app->getSecurity()->generateRandomString()
            ],
            'access_token' => [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'access_token'
                ],
                'value' => function () {
                    return Yii::$app->getSecurity()->generateRandomString(40);
                }
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'unique'],
            ['status', 'default', 'value' => self::STATUS_NOT_ACTIVE],
            ['status', 'in', 'range' => array_keys(self::statuses())],
            ['sub_id', 'safe'],
            [['username'], 'filter', 'filter' => '\yii\helpers\Html::encode']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Login',
            'email' => 'Email',
            'status' => 'Status',
            'access_token' => 'API access token',
            'created_at' => 'Registration date',
            'updated_at' => 'Last change date',
            'logged_at' => 'Last login date',
            'role' => 'Role',
            'status_moderation' => 'Moderation',
        ];
    }

    /**
     * Returns user statuses list
     * @return array|mixed
     */
    public static function statusesModeration()
    {
        return [
            self::STATUS_MODERATION_NEW => 'Moderation',
            self::STATUS_MODERATION_ACCEPT => 'Accepted',
            self::STATUS_MODERATION_DECLINE => 'Declined'
        ];
    }

    /**
     * Returns user statuses list
     * @return array|mixed
     */
    public static function statuses()
    {
        return [
            self::STATUS_NOT_ACTIVE => 'Not active',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DELETED => 'Deleted'
        ];
    }


    public static function statusesColor()
    {
        return [
            0 => 'default',
            self::STATUS_NOT_ACTIVE => 'warning',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_DELETED => 'danger',
        ];
    }

    public static function moderationColor()
    {
        return [
            self::STATUS_MODERATION_NEW => 'primary',
            self::STATUS_MODERATION_ACCEPT => 'success',
            self::STATUS_MODERATION_DECLINE => 'danger',
        ];
    }

    public function beforeSignup()
    {
        //
    }

    /**
     * Creates user profile and application event
     * @param array $profileData
     */
    public function afterSignup(array $profileData = [])
    {
        $this->refresh();
        $profile = new UserProfile();
        $profile->locale = Yii::$app->language;
        $profile->load($profileData, '');
        $this->link('userProfile', $profile);
        $this->trigger(self::EVENT_AFTER_SIGNUP);
        // Default role
        $auth = Yii::$app->authManager;
        $auth->assign($auth->getRole(User::ROLE_CLIENT), $this->getId());
    }

    /**
     * @return string
     */
    public function getPublicIdentity()
    {
        if ($this->username) {
            return $this->username;
        }
        return $this->email;
    }

    /**
     * @param integer $size
     * @return bool|null|string
     */
    public function getAvatar($size = 40)
    {
        if (!empty($this->avatar_url)) {
            return $this->avatar_url;
        }
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mm' . '&s=' . $size;
    }

    public function getRole()
    {
        $list = [];
        foreach (Yii::$app->authManager->getRolesByUser($this->id) as $role) {
            $list[] = $role->name;
        }
        return implode(', ', $list);
    }

    public function checkRole($roleName)
    {
        foreach (Yii::$app->authManager->getRolesByUser($this->id) as $role) {
            if ($role->name == $roleName) {
                return true;
            }
        }
        return false;
    }

    public function can($role)
    {
        return Yii::$app->authManager->checkAccess($this->id, $role);
    }
}
