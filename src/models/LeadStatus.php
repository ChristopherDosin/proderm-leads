<?php

namespace friendventure\leads\models;


use Craft;
use craft\base\Model;
use craft\base\VolumeInterface;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
use friendventure\leads\records\LeadStatus as LeadStatusRecord;

class LeadStatus extends Model
{
    // Public Properties
    // =========================================================================

    public $id;

    public $name;

    public $handle;

    public $colour = 'green';

    public $sortOrder;

    public $default;

    public $newMessage;

    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return (string) $this->name;
    }

    public function rules()
    {
        return [
            [['name', 'handle'], 'required'],
            [['handle'], UniqueValidator::class, 'targetClass' => LeadStatusRecord::class],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('leads/settings/lead-statuses/'.$this->id);
    }

    public function getLabelHtml(): string
    {
        $html  = '<div class="element small hasstatus">';
        $html .= '<span class="status '.$this->colour.'"></span>';
        $html .= '<div class="label"><span class="title">';
        $html .= '<a href="'.$this->getCpEditUrl().'">'.$this->name.'</a>';
        $html .='</span></div>';
        $html .= '</div>';

        return $html;
    }
}