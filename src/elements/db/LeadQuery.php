<?php
namespace friendventure\leads\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use friendventure\leads\elements\Lead;
use friendventure\leads\models\LeadStatus as LeadStatusModel;

class LeadQuery extends ElementQuery
{

    public $leadStatusId;

    public $leadStatus;

    public $firstName;

    public $lastName;

    public function leadStatusId($value)
    {
        $this->leadStatusId = $value;
        return $this;
    }

    public function firstName($value)
    {
        $this->firstName = $value;

        return $this;
    }

    public function lastName($value)
    {
        $this->lastName = $value;

        return $this;
    }

    public function leadStatus($value)
    {
        if ($value instanceof LeadStatusModel) {
            $this->leadStatusId = $value->id;
        } elseif ($value !== null) {
            $this->leadStatusId = $value;
        } else {
            $this->leadStatusId = null;
        }
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the products table
        $this->joinElementTable('leads');

        // select the price column
        $this->query->select([
            'leads.firstName',
            'leads.lastName',
        ]);

        if ($this->firstName) {
            $this->subQuery->andWhere(Db::parseParam('leads.firstName', $this->firstName));
        }

        if ($this->lastName) {
            $this->subQuery->andWhere(Db::parseParam('leads.lastName', $this->lastName));
        }

        return parent::beforePrepare();
    }
}