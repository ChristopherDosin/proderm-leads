<?php

namespace friendventure\leads\records;

use craft\db\ActiveRecord;

class LeadStatus extends ActiveRecord
{
    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%leads_leadstatuses}}';
    }
}