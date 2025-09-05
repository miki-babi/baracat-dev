<?php

namespace App\Filament\Resources\TelegramConfigResource\Pages;

use App\Filament\Resources\TelegramConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelegramConfig extends EditRecord
{
    protected static string $resource = TelegramConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
