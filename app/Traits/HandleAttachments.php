<?php

namespace App\Traits;

use App\Libs\FileService;
use App\Models\Attachment;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Summary of HandleAttachments
 *
 * @property Model $record
 */
trait HandleAttachments
{
    /**
     * Summary of beforeSave
     */
    protected function beforeSave(): void
    {
        $this->oldTeamId = $this->record->team_id; // @phpstan-ignore-line
    }

    protected function afterCreate(): void
    {
        // Handle attachments
        $this->handleAttachments();
    }

    protected function afterSave(): void
    {
        // Handle attachments
        $this->handleAttachments();
    }

    private function handleAttachments(): void
    {
        $record = $this->record;
        $newTeamId = $record->team_id;
        $attachments = [];

        if (isset($this->oldTeamId) && ($this->oldTeamId != $newTeamId)) {
            $this->changeTeam($record, $newTeamId, $this->oldTeamId);

            $attachments = [];
            foreach ($record->attachments as $ra) {
                $attachments[] = $ra->filename;
            }
        }

        $this->manageResourceAttachments($this->form->getRawState(), $record, $newTeamId, $attachments);
    }

    public static function changeTeam(Model $record, string $newTeam, ?string $oldTeam = null): void
    {
        // Retrieve existing attachments
        $existingAttachments = $record->attachments()->get();

        // Substitute team
        foreach ($existingAttachments as $attachment) {
            try {
                $oldFilename = $attachment->filename;

                $attachmentRecord = Attachment::find($attachment->id);
                $attachmentRecord->team_id = $newTeam;
                $attachmentRecord->update();

                $record->team_id = $newTeam;
                $record->update();

                Notification::make()
                    ->success()
                    ->title('Change of document ownership')
                    ->body(__('All documents have been transferred to the selected Team Successfully!'))
                    ->send();

                Notification::make()
                    ->success()
                    ->title('Change Team ownership')
                    ->body(__('Team has been changed Successfully!'))
                    ->send();
            } catch (\Throwable $th) {
                Notification::make()
                    ->danger()
                    ->title('Change Team ownership')
                    ->body(__('There was a problem changing Ownership'))
                    ->send();
            }

            $move = FileService::movePrivateFile(
                storage_path($oldFilename),
                storage_path($attachmentRecord->filename),
                Str::afterLast($record::class, '\\'),
                $newTeam
            );

            if ($move) {
                Notification::make()
                    ->success()
                    ->title('Change of Documents Folder')
                    ->body(__('The documents were successfully moved'))
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title('No change Documents Folder')
                    ->body(__('There was a problem moving Documents'))
                    ->send();
            }
        }
    }

    protected function manageResourceAttachments($data, $record, $teamId, $attachments = [])
    {
        $attachments = ! $attachments ? $data['attachments'] : $attachments;

        // Try to retrieve existing attachments
        $existingAttachments = $record->attachments;

        // Associate new attachments
        foreach ($attachments as $path) {
            // strip team-{id]/} from $path
            $model = Str::afterLast($record::class, '\\');
            $filename = Str::afterLast($path, 'team-'.$teamId.'/'.$model.'/');

            // Check if the attachment is already associated
            $existingAttachment = $existingAttachments
                ->where('filename', $filename)
                ->where('team_id', $teamId)
                ->first();

            if (! $existingAttachment) {
                // If not, associate the new attachment
                $record->attachments()->create([
                    'team_id' => $teamId,
                    'filename' => $filename,
                    'original_filename' => $data['original_filename'][$path],
                    'description' => '',
                ]);
            }
        }

        // Reload existing attachments
        $existingAttachments = $record->attachments;

        // Detach attachments that are not present in the new set
        $attachmentsToRemove = $existingAttachments->reject(function ($attachment) use ($attachments) {
            return in_array($attachment->filename, $attachments);
        });

        foreach ($attachmentsToRemove as $attachment) {
            $attachment->forceDelete();
            FileService::deletePrivateFiles(
                ['team-'.$attachment->team_id.'/'.Str::afterLast($attachment->attachable_type, '\\').'/'.$attachment->filename]
            );
        }
    }

    public function manageExternalAttachments($record, $teamId, $attachments)
    {
        // Associate new attachments
        foreach ($attachments as $filename => $originalFilename) {
            $record->attachments()->create([
                'team_id' => $teamId,
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'description' => '',
            ]);
        }
    }
}
