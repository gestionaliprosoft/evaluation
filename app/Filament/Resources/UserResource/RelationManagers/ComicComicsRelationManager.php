<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Traits\RelationManagers\ComicRelationManager;

class ComicComicsRelationManager extends AbstractRelationManager
{
    use ComicRelationManager;

    protected static string $relationship = 'comicComics';
}
