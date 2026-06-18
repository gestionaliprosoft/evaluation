<?php

namespace App\Models\Abstract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

abstract class BaseModel extends Model
{
    use HasFactory;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'tenant';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->table);
    }

    /**
     * Get the destination email address for the notification.
     */
    public function getRecipientEmail(): ?string
    {
        return null;
    }

    /**
     * Get the display name of the recipient.
     */
    public function getRecipientName(): ?string
    {
        return null;
    }
}
