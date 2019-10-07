<?php
namespace friendventure\leads\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use friendventure\leads\elements\db\LeadQuery;
use friendventure\leads\Leads;

class Lead extends Element
{


    // Public Properties
    // =========================================================================
    public $leadStatusId;
    public $authorId;
    public $_leadStatus;
    public $_author;
    public $_messages;

    /**
     * @var string firstName
     */
    public $firstName;

    /**
     * @var string Currency code
     */
    public $lastName;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Lead';
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return 'Leads';
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function statuses(): array
    {
        return [];
    }

    public static function find(): ElementQueryInterface
    {
        return new LeadQuery(static::class);
    }

    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%leads}}', [
                    'id' => $this->id,
                    'firstName' => $this->firstName,
                    'lastName' => $this->lastName,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%leads}}', [
                    'firstName' => $this->firstName,
                    'lastName' => $this->lastName,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritDoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key'      => '*',
                'label'    => ' Alle Leads',
                'criteria' => [],
            ]
        ];

        $sources[] = ['heading' => 'Lead Status'];

        $statuses = Leads::getInstance()->leadStatusService->getAllLeadStatuses();
        foreach ($statuses as $status) {
            $sources[] = [
                'key'         => 'status:'.$status['handle'],
                'status'      => $status['colour'],
                'label'       => $status['name'],
                'criteria'    => [
                    'leadStatusId' => $status['id'],
                ],
                'defaultSort' => ['dateCreated', 'desc'],
            ];
        }


        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'id' => 'ID',
            'leadStatus' => 'Status',
            'firstName'   => 'First Name',
            'lastName'   => 'Last Name',
            'dateCreated'  => 'Date Created',
            'dateUpdated'  => 'Date Updated'
        ];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [
            'firstName'   => 'First Name',
            'lastName'   => 'Test Name',
            'dateCreated'  => 'Date Created',
            'dateUpdated'  => 'Date Updated'
        ];

        return $attributes;
    }

    public function getTableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'leadStatus':
                $status = $this->getLeadStatus();
                return '<span class="status '.$status['colour'].'"></span>'.$status['name'];
            default:
            {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'leadStatusId'];
    }

    public function getLeadStatus()
    {
        if ($this->_leadStatus !== null) {
            return $this->_leadStatus;
        }
        if ($this->leadStatusId === null) {
            return null;
        }
        $this->_leadStatus = Leads::getInstance()->leadStatusService->getLeadStatusById($this->leadStatusId);
        return $this->_leadStatus;
    }


    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('leads/'.$this->id);
    }

}