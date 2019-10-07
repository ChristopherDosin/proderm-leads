<?php
namespace friendventure\leads\services;

use craft\base\Component;
use friendventure\leads\elements\Lead;

class LeadService extends Component {

    public function createLead($submission = null)
    {
        if ($submission) {

            $lead = new Lead();
            $lead->firstName = $submission->post('firstName');
            $lead->lastName = $submission->post('lastName');

            $res = \Craft::$app->getElements()->saveElement($lead);
            if ($res) {
                return $lead;
            }
        }

        return null;
    }

}