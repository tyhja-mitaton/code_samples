<?php

namespace core\modules\api\report\controllers;

use common\models\arbitration\report\lines\ReportLines;

class ReportController extends \yii\rest\Controller
{
    /**
     * @param $reportId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionGetReportLinesByReportId($reportId)
    {
        return ReportLines::Find()->where(['report_id' => $reportId])->all();
    }
}
