<?php

namespace App\Filament\Resources\TelegramConfigResource\Pages;

use App\Filament\Resources\TelegramConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelegramConfigs extends ListRecords
{
    protected static string $resource = TelegramConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
