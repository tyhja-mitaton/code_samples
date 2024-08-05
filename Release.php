<?php

namespace app\models\release;

use app\components\bernard\Bernard;
use app\components\bernard\Queue;
use Yii;

/**
 * This is the model class for table "release".
 *
 * @property int $id
 * @property int $date
 * @property string $comments
 * @property int $message_id
 *
 * @property ReleaseLine $lines
 */
class Release extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'release';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'required'],
            [['date', 'message_id'], 'integer'],
            [['appIds', 'releasedAt'], 'safe'],
            [['comments'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'date' => Yii::t('app', 'Date'),
            'appIds' => Yii::t('app', 'Applications'),
            'comments' => Yii::t('app', 'Comments'),
            'message_id' => Yii::t('app', 'Message ID'),
        ];
    }

    public function setReleasedAt($date)
    {
        $this->date = !empty($date) ? strtotime($date) : null;
    }

    public function getReleasedAt()
    {
        return !empty($this->date) ? date('Y-m-d', $this->date) : null;
    }

    public static function create($date, $comments, $messageId)
    {
        $release = new self();
        $release->date = $date;
        $release->comments = $comments;
        $release->message_id = $messageId;
        $release->save(false);

        return $release;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLines()
    {
        return $this->hasMany(ReleaseLine::class, ['release_id' => 'id']);
    }

    public function getAppIds()
    {
        $appIds = [];
        foreach ($this->lines as $line) {
            $appIds[] = $line->application_id;
        }
        return $appIds;
    }

    public function setAppIds($appIds)
    {
        if(empty($appIds)) {$appIds = [];}
        $appIdsOld = [];
        foreach ($this->lines as $line) {
            $appIdsOld[] = $line->application_id;
        }
        $this->updateApplications($appIdsOld, $appIds);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if($insert) {
            $lastLines = ReleaseLine::find()->where(['release_id' => null])->orderBy(['id' => SORT_DESC])->all();
            foreach ($lastLines as $lastLine) {
                $this->link('lines', $lastLine);
            }
        }
    }

    public function updateApplications($appIdsOld, $appIds)
    {
        $deletedAppIds = array_diff($appIdsOld, $appIds);
        $addedAppIds = array_diff($appIds, $appIdsOld);
        if(!empty($deletedAppIds)) {
            $releasesToDelete = $this->getLines()->where(['application_id' => $deletedAppIds])->all();
            foreach ($releasesToDelete as $releaseToDelete) {
                $releaseToDelete->delete();
            }
        }
        if(!empty($addedAppIds)) {
            foreach ($addedAppIds as $addedAppId) {
                ReleaseLine::create($this->id, $addedAppId);
            }
        }
    }

    public function notification($channel, $message, $params = [])
    {
        Bernard::produce(Queue::QUEUE_APPLICATION_NOTIFICATION, [
            'application_id' => null,
            'type' => $channel,
            'params' => $params,
            'message' => $message,
            'reason' => '',
        ]);
    }
}
