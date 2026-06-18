<?php

namespace App\Filament\Tables\HeaderActions;

use Filament\Actions\Action;

class CloseAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Chiudi')
            ->color('gray')
            ->icon('heroicon-o-x-mark')
            ->url(function () {
                // DYNAMIC RESOURCE RETRIEVAL FROM THE CURRENT PAGE
                // Since this action lives on an Edit or View page,
                // the Livewire component is the page itself.
                $page = $this->getLivewire();

                if (method_exists($page, 'getResource')) {
                    return $page::getResource()::getUrl('index');
                }

                return '#';
            });
    }
}
