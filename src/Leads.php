<?php
/**
 * Leads plugin for Craft CMS 3.x
 *
 * Lead plugin
 *
 * @link      https://friendventure.de
 * @copyright Copyright (c) 2019 Friendventure GmbH
 */

namespace friendventure\leads;


use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\events\PluginEvent;

use craft\web\UrlManager;
use friendventure\leads\models\Settings;
use friendventure\leads\services\LeadService;
use friendventure\leads\services\LeadStatusService;
use yii\base\Event;

/**
 * Class Leads
 *
 * @author    Friendventure GmbH
 * @package   Leads
 * @since     1.0.0
 *
 */
class Leads extends Plugin
{

    // Static Properties
    // =========================================================================

    /**
     * @var Leads
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;


        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['leads/lead'] = 'leads/lead/index';
                $event->rules['leads/lead/new'] = 'leads/lead/new';
                $event->rules['leads/lead/<leadId:\d+>'] = 'leads/lead/view';

                $event->rules['leads/settings/general'] = 'leads/settings/general';

                $event->rules['leads/settings/ticket-statuses'] = 'leads/ticket-statuses/index';
                $event->rules['leads/settings/ticket-statuses/new'] = 'leads/ticket-statuses/edit';
                $event->rules['leads/settings/ticket-statuses/<id:\d+>'] = 'leads/ticket-statuses/edit';
                $event->rules['leads/settings/emails'] = 'leads/emails/index';
                $event->rules['leads/settings/emails/new'] = 'leads/emails/edit';
                $event->rules['leads/settings/emails/<id:\d+>'] = 'leads/emails/edit';
                $event->rules['leads/settings/attachments'] = 'leads/attachments/index';
            }
        );

        /*
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $event->navItems[] = [
                    'url' => 'leads/dashboard',
                    'label' => 'Leads',
                    'subnav' => [
                        'settings' => [
                            'label' => 'Settings',
                            'url'   => 'leads/settings/general',
                        ]
                    ],
                ];
            }
        );
        */

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/leads'))->send();
                }
            }
        );

        $this->setComponents([
            'leadService' => LeadService::class,
            'leadStatusService' => LeadStatusService::class
        ]);

    }


    public function getCpNavItem()
    {
        $ret = parent::getCpNavItem();
        $ret['label'] = $this->getSettings()->pluginNameOverride ?: $this->name;
        $ret['subnav']['leads'] = [
            'label' => 'Leads',
            'url'   => 'leads/leads',
        ];
        if (Craft::$app->getUser()->getIsAdmin()) {
            $ret['subnav']['settings'] = [
                'label' => 'Settings',
                'url'   => 'leads/settings',
            ];
        }
        return $ret;
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('leads/settings', [
            'settings' => $this->getSettings()
        ]);
    }
}
