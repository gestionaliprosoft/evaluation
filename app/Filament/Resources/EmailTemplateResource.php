<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Filament\Resources\EmailTemplateResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Models\Email\EmailTemplate;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected static ?string $model = EmailTemplate::class;

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'view' => Pages\ViewEmailTemplate::route('/{record}'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
            'activities' => Pages\ListEmailTemplateActivities::route('/{record}/activities'),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('subject')
                ->label(__('email-template.subject'))
                ->wrap()
                ->sortable()
                ->searchable(),
            static::filePreviewTable('logo', __('Logo'), '150'),
            static::team(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ReplicateAction::make()
                ->label('')
                ->tooltip(__('Replicate'))
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->name = '[NEW] '.$replica->name;
                })
                ->successRedirectUrl(fn (Model $replica): string => self::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
                self::forceDeleteAction(),
                ActivityAction::make('activities'),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                self::changeRecordOwnership(),
                self::deleteBulkAction(),
                self::forceDeleteBulkAction(),
                self::restoreBulkAction(),
            ]),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('subject')
                            ->label(__('email-template.subject'))
                            ->required()
                            ->maxLength(255),

                        // A helpful guide showing operators which tags they can type
                        Forms\Components\Placeholder::make('placeholder_guide')
                            ->label(__('email-template.available_placeholders'))
                            ->content(__('email-template.available_placeholders_message').':
                                {{name}} = '.__('email-template.recipient_name').',
                                {{email}} = '.__('email-template.recipient_email').',
                                {{url}} = '.__('email-template.dinamic_url'))
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('message')
                            ->label(__('email-template.message_body'))
                            ->required()
                            ->columnSpanFull(),

                        FormService::attachmentImageFileUploadFormSection(
                            'EmailTemplate',
                            __('Upload Logo'),
                            1,
                            true,
                            'EmailTemplate'
                        ),
                    ])->columns(2),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedTeamSection(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.email_templates');
    }
}
