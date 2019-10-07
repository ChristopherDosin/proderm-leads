<?php

namespace friendventure\leads\services;

use friendventure\leads\elements\Lead;
use friendventure\leads\models\LeadStatus as LeadStatusModel;

use Craft;
use craft\base\Component;
use craft\db\Query;

use yii\base\Exception;

class LeadStatusService extends Component
{

    // Properties
    // =========================================================================

    private $_fetchedAllStatuses = false;

    private $_leadStatusesById = [];

    private $_leadStatusesByHandle = [];

    // Public Methods
    // =========================================================================

    public function getAllLeadStatuses()
    {
        if (!$this->_fetchedAllStatuses) {
            $results = $this->_createLeadStatusQuery()->all();

            foreach ($results as $row) {
                $this->_memoizeLeadStatus(new LeadStatusModel($row));
            }

            $this->_fetchedAllStatuses = true;
        }

        return $this->_leadStatusesById;
    }

    public function getLeadStatusById($id)
    {
        $result = $this->_createLeadStatusQuery()
            ->where(['id' => $id])
            ->one();

        return new LeadStatusModel($result);
    }

    public function getDefaultLeadStatus()
    {
        $result = $this->_createLeadStatusQuery()
            ->where(['default' => 1])
            ->one();

        return new LeadStatusModel($result);
    }

    public function getNewMessageTicketStatus()
    {
        $result = $this->_createLeadStatusQuery()
            ->where(['newMessage' => 1])
            ->one();

        return new LeadStatusModel($result);
    }

    public function checkIfLeadStatusInUse($id)
    {
        $result = Lead::find()
            ->leadStatusId($id)
            ->one();

        return $result;
    }

    public function reorderLeadStatuses(array $ids)
    {
        foreach ($ids as $sortOrder => $id) {
            Craft::$app->getDb()->createCommand()
                ->update('{{%leads_leadstatuses}}', ['sortOrder' => $sortOrder + 1], ['id' => $id])
                ->execute();
        }

        return true;
    }

    public function saveTicketStatus(TicketStatusModel $model, array $emailIds, bool $runValidation = true)
    {
        if ($model->id) {
            $record = TicketStatusRecord::findOne($model->id);

            if (!$record->id) {
                throw new Exception(Craft::t('support', 'No ticket status exists with the ID "{id}"',
                    ['id' => $model->id]));
            }
        } else {
            $record = new TicketStatusRecord();
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Ticket status not saved due to a validation error.', __METHOD__);

            return false;
        }

        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->colour = $model->colour;
        $record->sortOrder = $model->sortOrder ?: 999;
        $record->default = $model->default;
        $record->newMessage = $model->newMessage;

        // Validate email ids
        $exist = EmailRecord::find()->where(['in', 'id', $emailIds])->exists();
        $hasEmails = (boolean) count($emailIds);

        if (!$exist && $hasEmails) {
            $model->addError('emails', 'One or more emails do not exist in the system.');
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Only one default status can be among statuses
            if ($record->default) {
                TicketStatusRecord::updateAll(['default' => 0]);
            }

            // Only one status can be triggered by new messages
            if ($record->newMessage) {
                TicketStatusRecord::updateAll(['newMessage' => 0]);
            }

            // Save it
            $record->save(false);

            // Delete old email links
            if ($model->id) {
                $rows = TicketStatusEmailRecord::find()->where(['ticketStatusId' => $model->id])->all();

                foreach ($rows as $row) {
                    $row->delete();
                }
            }

            // Save new email links
            $rows = array_map(
                function ($id) use ($record) {
                    return [$id, $record->id];
                }, $emailIds);

            $cols = ['emailId', 'ticketStatusId'];
            $table = TicketStatusEmailRecord::tableName();
            Craft::$app->getDb()->createCommand()->batchInsert($table, $cols, $rows)->execute();

            // Now that we have a record ID, save it on the model
            $model->id = $record->id;

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    public function deleteTicketStatusbyId($id)
    {
        $statuses = $this->getAllTicketStatuses();

        $existingTicket = $this->checkIfTicketStatusInUse($id);

        // Don't delete if it's still in use
        if ($existingTicket) {
            return false;
        }

        // Don't delete if it's the only status left
        if (count($statuses) > 1) {
            $record = TicketStatusRecord::findOne($id);

            return $record->delete();
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    private function _memoizeLeadStatus(LeadStatusModel $leadStatus)
    {
        $this->_leadStatusesById[$leadStatus->id] = $leadStatus;
        $this->_leadStatusesByHandle[$leadStatus->handle] = $leadStatus;
    }

    private function _createLeadStatusQuery()
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'colour',
                'sortOrder',
                'default',
                'newMessage',
            ])
            ->orderBy('sortOrder')
            ->from(['{{%leads_leadstatuses}}']);
    }
}