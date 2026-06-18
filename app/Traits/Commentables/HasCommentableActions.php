<?php

namespace App\Traits\Commentables;

use app\Libs\UserService;
use Illuminate\Database\Eloquent\Collection;
use Kirschbaum\Commentions\Filament\Actions\CommentsAction;
use Kirschbaum\Commentions\Filament\Actions\CommentsTableAction;

trait HasCommentableActions
{
    public static function commentableHeaderAction(): CommentsAction
    {
        return CommentsAction::make()
            ->mentionables(function (): array|Collection {
                return UserService::getAllowedEloquentUsers();
            })
            ->label(__('commentions::comments.label'))
            ->badge(function ($record) {
                return $record->comments->count();
            })
            ->perPage(5);
    }

    public static function commentableAction(): CommentsTableAction
    {
        return CommentsTableAction::make()
            ->mentionables(function (): array|Collection {
                return UserService::getAllowedEloquentUsers();
            })
            ->label('')
            ->badge(function ($record) {
                return $record->comments->count();
            });
    }
}
