<?php

namespace App\Filament\Resources\ApiTokens\Pages;

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Locked;

class ListApiTokens extends ListRecords
{
    public const string PLAIN_TEXT_TOKEN_SESSION_KEY = 'api_tokens.plain_text_token';

    protected static string $resource = ApiTokenResource::class;

    /**
     * The token just created on the create page, shown once in a modal.
     */
    #[Locked]
    public ?string $plainTextToken = null;

    public function mount(): void
    {
        parent::mount();

        $this->plainTextToken = session()->pull(self::PLAIN_TEXT_TOKEN_SESSION_KEY);

        if ($this->plainTextToken !== null) {
            $this->defaultAction = 'showPlainTextToken';
        }
    }

    public function showPlainTextTokenAction(): Action
    {
        return Action::make('showPlainTextToken')
            ->visible(fn (): bool => $this->plainTextToken !== null)
            ->modalHeading('Copy the new API token')
            ->modalDescription('This is the only time the token is shown. Copy it now; you won\'t see it again.')
            ->modalIcon('heroicon-o-key')
            ->fillForm(fn (): array => ['token' => $this->plainTextToken])
            ->schema([
                TextInput::make('token')
                    ->hiddenLabel()
                    ->readOnly()
                    ->copyable(copyMessage: 'Copied'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done')
            ->closeModalByClickingAway(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
