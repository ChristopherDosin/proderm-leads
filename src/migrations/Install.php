<?php
namespace friendventure\leads\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use friendventure\leads\records\LeadStatus as LeadStatusRecord;

class Install extends Migration
{
    public $driver;

    public function safeUp()
    {
        if (!$this->db->tableExists('{{%leads}}')) {
            // create the products table
            $this->createTable('{{%leads}}', [
                'id' => $this->integer()->notNull(),
                'leadNumber' => $this->integer(),
                'studyCity' => $this->string(),
                'phone' => $this->string(),
                'firstName' => $this->string(),
                'lastName' => $this->string(),
                'email' => $this->string(),
                'address' => $this->string(),
                'postcode' => $this->string(),
                'city' => $this->string(),
                'authorId'  => $this->integer(),
                'leadStatusId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->createTable(
                '{{%leads_leadstatuses}}',
                [
                    'id'          => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    // Custom columns in the table
                    'name'        => $this->string()->notNull(),
                    'handle'      => $this->string()->notNull(),
                    'colour'      => $this->enum('colour', ['green', 'orange', 'red', 'blue', 'yellow', 'pink', 'purple', 'turquoise', 'light', 'grey', 'black'])->notNull()->defaultValue('green'),
                    'sortOrder'   => $this->integer(),
                    'default'     => $this->boolean(),
                    'newMessage'  => $this->boolean(),
                ]
            );

            // give it a FK to the elements table
            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%leads}}', 'id'),
                '{{%leads}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);

            $this->addForeignKey(null, '{{%leads}}', ['leadStatusId'], '{{%leads_leadstatuses}}', ['id'], null, 'CASCADE');

            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }
    }

    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%leads}}', [
                    'id' => $this->id,
                    'firstName' => $this->firstName,
                    'lastName' => $this->lastName,
                ])
                ->execute();
        } else {
            \Craft::$app->db->createCommand()
                ->update('{{%products}}', [
                    'firstName' => $this->firstName,
                    'lastName' => $this->lastName,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    public function safeDown()
    {
        $this->driver = \Craft::$app->getConfig()->getDb()->driver;
        $this->dropForeignKeys();
        $this->dropTables();
        return true;
    }

    protected function dropForeignKeys()
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%leads}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%leads_leadstatuses}}', $this);
    }

    protected function dropTables()
    {
        $this->dropTable('{{%leads}}');
        $this->dropTable('{{%leads_leadstatuses}}');
    }

    protected function insertDefaultData()
    {
        $this->_defaultTicketStatuses();
    }

    // Private Methods
    // =========================================================================
    private function _defaultTicketStatuses()
    {
        // Default ticket statuses
        $data = [
            'name' => 'Neu',
            'handle' => 'new',
            'colour' => 'blue',
            'sortOrder' => 1,
            'default' => true
        ];
        $this->insert(LeadStatusRecord::tableName(), $data);

        $data = [
            'name' => 'Telefontermin proDERM',
            'handle' => 'telefonterminProderm',
            'colour' => 'blue',
            'sortOrder' => 2,
        ];
        $this->insert(LeadStatusRecord::tableName(), $data);

        $data = [
            'name' => 'Tel. nicht erreicht',
            'handle' => 'telefonischNichtErreicht',
            'colour' => 'yellow',
            'sortOrder' => 3,
        ];
        $this->insert(LeadStatusRecord::tableName(), $data);

        $data = [
            'name' => 'Nicht erschienen',
            'handle' => 'nichtErschienen',
            'colour' => 'orange',
            'sortOrder' => 4,
        ];
        $this->insert(LeadStatusRecord::tableName(), $data);
    }
}