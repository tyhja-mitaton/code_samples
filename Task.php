<?php

namespace app\models\task;

use app\components\queue\SendComment;
use app\models\comment\traits\Finders;
use app\models\fan_page\FanPage;
use app\models\task\enums\Status;
use app\models\task\traits\Relations;
use app\models\task\traits\Utils;
use Yii;
use app\models\comment_group\CommentGroup;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property string $title
 * @property int $comment_group_id
 * @property array $fan_page_list
 * @property int $date_start
 * @property int $date_end
 * @property int $interval_from
 * @property int $interval_to
 * @property string $post_url
 * @property int $status
 * @property int $processed_comment_count
 * @property int $owner_id
 *
 * @property CommentGroup $commentGroup
 */
class Task extends ActiveRecord
{
    use Relations, Finders, Utils;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'comment_group_id', 'dateStart', 'dateEnd', 'owner_id'], 'required'],
            [[
                'comment_group_id', 'date_start', 'date_end', 'interval_from', 'interval_to',
                'status', 'processed_comment_count', 'owner_id'
            ], 'integer'],
            [['fan_page_list'], 'fanPageValidated'],
            [['title', 'dateStart', 'dateEnd'], 'string', 'max' => 255],
            ['post_url', 'url'],
            [['comment_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => CommentGroup::class, 'targetAttribute' => ['comment_group_id' => 'id']],
            ['post_url', 'validateDomain'],
            ['dateStart', 'validateDate'],

            [['interval_from', 'interval_to'], 'required', 'when' => function ($model) {
                return !($model->interval_from == '' && $model->interval_to == '');
            }, 'whenClient' => 'function (attribute, value) {
                return !($("#task-interval_from").val() == "" && $("#task-interval_to").val() == "");
            }', 'message' => '"interval from" and "interval to" cannot be blank'],
        ];
    }

    /**
     * @param $attribute
     * @return void
     */
    public function fanPageValidated($attribute)
    {
        foreach ($this->$attribute as $item) {
            if (empty($item['account_group_id']) ) {
                $this->addError($attribute, Yii::t('app', 'Account cannot be blank.'));
            }
            if (!isset($item['fanpage']) || empty($item['fanpage'])) {
                $this->addError($attribute, Yii::t('app', 'Fan page cannot be blank.'));
            }
        }
    }

    /**
     * @param $attribute
     * @return void
     */
    public function validateDomain($attribute)
    {
        if(!preg_match('/^(http[s]?\:\/\/)?((\w+)\.)?facebook\.com/', $this->{$attribute})) {
            $this->addError($attribute, Yii::t('app', 'Domain should be facebook.com'));
        }
    }

    /**
     * @param $attribute
     * @return void
     */
    public function validateDate($attribute)
    {
        if (strtotime($this->{$attribute}) > strtotime($this->dateEnd)) {
            $this->addError($attribute, \Yii::t('app', 'Дата начала не может превышать дату окончания'));
        }
    }

    /**
     * @return string|null
     */
    public function getDateStart()
    {
        return !empty($this->date_start) ? date('Y-m-d', $this->date_start) : null;
    }

    /**
     * @return string|null
     */
    public function getDateEnd()
    {
        return !empty($this->date_end) ? date('Y-m-d', $this->date_end) : null;
    }

    /**
     * @param $date
     * @return void
     */
    public function setDateStart($date)
    {
        $this->date_start = strtotime($date);
    }

    /**
     * @param $date
     * @return void
     */
    public function setDateEnd($date)
    {
        $this->date_end = strtotime($date);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'comment_group_id' => Yii::t('app', 'Comment Group'),
            'fan_page_list' => Yii::t('app', 'Fan Page List'),
            'date_start' => Yii::t('app', 'Date Start'),
            'date_end' => Yii::t('app', 'Date End'),
            'interval_from' => Yii::t('app', 'Interval from (seconds)'),
            'interval_to' => Yii::t('app', 'Interval to (seconds)'),
            'post_url' => Yii::t('app', 'Post Url'),
            'status' => Yii::t('app', 'Status'),
            'owner_id' => Yii::t('app', 'Owner'),
        ];
    }

    /**
     * @param Task $task
     * @return void
     */
    public static function addCommentsToQueue(self $task)
    {
        $task->changeStatus(Status::Active);
        $fanPageIds = [];
        foreach ($task->fan_page_list as $accountGroup) {
            $fanPageIds = array_merge($fanPageIds, $accountGroup['fanpage']);
        }

        $fanPages = FanPage::findAll($fanPageIds);
        $comments = $task->commentGroup->comments;

        if (count($comments) > count($fanPages)) {
            $randCommentKeys = array_rand($comments, count($fanPages));
            $randCommentKeys = is_array($randCommentKeys) ? $randCommentKeys : [$randCommentKeys];
            $randComments = array_values(array_intersect_key($comments, array_flip($randCommentKeys)));
            self::sendComments($randComments, $fanPages, $task);
        } else {
            $randFanPageKeys = array_rand($fanPages, count($comments));
            $randFanPageKeys = is_array($randFanPageKeys) ? $randFanPageKeys : [$randFanPageKeys];
            $randFanPages = array_values(array_intersect_key($fanPages, array_flip($randFanPageKeys)));
            self::sendComments($comments, $randFanPages, $task);
        }
    }

    /**
     * @param $comments
     * @param $fanPages
     * @param $task
     * @return void
     */
    private static function sendComments($comments, $fanPages, $task)
    {
        $i = 0;
        $previousDelay = 0;
        foreach ($comments as $comment) {
            $accessToken = $fanPages[$i]?->access_token;
            if (($postId = $task->getPostId()) && isset($fanPages[$i]->external_id)) {
                $postId = $fanPages[$i]->external_id . '_' . $postId;
                $previousDelay = self::addTaskToQueue(new SendComment([
                    'commentId' => $comment->id,
                    'postId' => $postId,
                    'accessToken' => $accessToken,
                    'accountId' => $fanPages[$i]?->account->id,
                    'taskId' => $task->id,
                    'fanPageId' => $fanPages[$i]->id,
                ]), $task, $previousDelay);
            }
            $i++;
        }
    }

    /**
     * @return mixed|null
     */
    private function getPostId()
    {
        if(preg_match('/fbid=(\w+?)&/', $this->post_url, $match) == 1) {
            return $match[1];
        }

        return null;
    }
}
