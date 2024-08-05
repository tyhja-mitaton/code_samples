<?php

namespace app\models;

use Yii;
use app\models\structure\PublishingAccount;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "account_invoice".
 *
 * @property int $id
 * @property int $seller_id
 * @property int $is_paid
 * @property int $created_at
 * @property int $paid_at
 * @property double $usdt_price
 *
 * @property PublishingAccountSeller $seller
 * @property PublishingAccount[] $publishingAccounts
 */
class AccountInvoice extends \yii\db\ActiveRecord
{
    const CONTRACT_ADDRESS = "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t";
    const FACTOR = 1000000;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'account_invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['seller_id', 'is_paid', 'paid_at'], 'integer'],
            [['usdt_price'], 'number'],
            [['seller_id'], 'exist', 'skipOnError' => true, 'targetClass' => PublishingAccountSeller::className(), 'targetAttribute' => ['seller_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'seller_id' => Yii::t('app', 'Seller ID'),
            'is_paid' => Yii::t('app', 'Is Paid'),
            'created_at' => Yii::t('app', 'Created At'),
            'paid_at' => Yii::t('app', 'Paid At'),
            'usdt_price' => Yii::t('app', 'Usdt Price'),
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at'],
                    self::EVENT_BEFORE_UPDATE => null,
                ],
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeller()
    {
        return $this->hasOne(PublishingAccountSeller::className(), ['id' => 'seller_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPublishingAccounts()
    {
        return $this->hasMany(PublishingAccount::className(), ['invoice_id' => 'id']);
    }

    public static function create(PublishingAccount $account)
    {
        $invoice = new self();
        $invoice->seller_id = $account->seller_id;
        $invoice->is_paid = false;
        $invoice->usdt_price = $account->price_usd;
        $invoice->save(false);
        $account->updateAttributes(['invoice_id' => $invoice->id]);
    }

    public static function createOrUpdate(PublishingAccount $account)
    {
        if (!empty($account->invoice_id)) {
            $account->invoice->updateSum();
            return true;
        }

        $invoice = self::find()
            ->where(['seller_id' => $account->seller_id])
            ->andWhere(['is_paid' => false])
            ->one();
        if (!empty($invoice)) {
            $account->updateAttributes(['invoice_id' => $invoice->id]);
            $invoice->updateSum();
            return true;
        }

        self::create($account);
        return true;
    }

    private function updateSum()
    {
        $sum = $this->hasMany(PublishingAccount::class, ['invoice_id' => 'id'])->sum('price_usd');
        $this->usdt_price = $sum;
        $this->save(false);
    }
}
