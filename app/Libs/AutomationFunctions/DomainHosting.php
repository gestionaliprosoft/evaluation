<?php

namespace App\Libs\AutomationFunctions;

use Illuminate\Database\Eloquent\Model;

class DomainHosting
{
    /**
     * enable or disable Relateds DomainFtp, DomainDb & DomaninEmailAccount when enable or disable DomainHosting record
     *
     * @return null
     */
    public function enableDisableRelatedRecords(Model $record)
    {
        $ftps = $record->ftps;
        foreach ($ftps as $ftp) {
            $ftp->enabled = $record->enabled;
            $ftp->save();
        }

        $dbs = $record->dbs;
        foreach ($dbs as $db) {
            $db->enabled = $record->enabled;
            $db->save();
        }

        $emails = $record->emailAccounts;
        foreach ($emails as $email) {
            $email->enabled = $record->enabled;
            $email->save();
        }
    }
}
