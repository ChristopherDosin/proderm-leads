<?php
namespace friendventure\leads\controllers;

use craft\web\Controller;
use Craft;
use friendventure\leads\elements\Lead;

class LeadController extends Controller {

    public function init()
    {
        parent::init();
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveLead()
    {
        $this->requirePostRequest();

        $lead = new Lead();

        $lead->firstName = Craft::$app->request->getBodyParam('firstName');
        $lead->lastName = Craft::$app->request->getBodyParam('lastName');

        $success = Craft::$app->elements->saveElement($lead, false, false);

        Craft::$app->getSession()->setNotice('Lead created.');

        return $this->redirectToPostedUrl();
    }
}