<?php

namespace App\Libs\AutomationFunctions;

use Illuminate\Database\Eloquent\Model;

class DomainDomain
{
    /**
     * renew Relateds Domain expire date (hosting, dbs & emails account)
     *
     * @return null
     */
    public function renewRelatedRecords(Model $record)
    {
        $hosting = $record->hosting;

        if ($hosting) {
            $this->updateHosting($hosting, $record);

            $dbs = $hosting->dbs;
            if ($dbs) {
                $this->updateDbs($dbs, $record);
            }

            $emails = $hosting->emailAccounts;
            if ($emails) {
                $this->updateEmailAccounts($emails, $record);
            }
        }
    }

    protected function updateHosting($hosting, $record)
    {
        $hosting->expire_date = $record->expire_date;
        $hosting->backup_expire = $record->expire_date;
        $hosting->a_virus_expire = $record->expire_date;
        $hosting->a_spam_expire = $record->expire_date;
        $hosting->save();
    }

    protected function updateDbs($dbs, $record)
    {
        foreach ($dbs as $db) {
            $db->expire_date = $record->expire_date;
            $db->save();
        }
    }

    protected function updateEmailAccounts($emails, $record)
    {
        foreach ($emails as $email) {
            $email->expire_date = $record->expire_date;
            $email->save();
        }
    }
}
