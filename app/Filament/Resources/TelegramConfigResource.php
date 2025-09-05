<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramConfigResource\Pages;
use App\Filament\Resources\TelegramConfigResource\RelationManagers;
use App\Models\TelegramConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelegramConfigResource extends Resource
{
    protected static ?string $model = TelegramConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
                return $form->schema([
            Forms\Components\TextInput::make('bot_token')
                ->label('Bot Token')
                ->required(),

            Forms\Components\TextInput::make('bot_username')
                ->label('Bot Username')
                ->prefix('@')
                ->required(),

            Forms\Components\TextInput::make('channel_username')
                ->label('Channel Username')
                ->prefix('@')
                ->required(),

            Forms\Components\Textarea::make('bot_description')
                ->label('Bot Description'),
        ]);
    }

     public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bot_username')
                    ->label('Bot Username'),

                Tables\Columns\TextColumn::make('channel_username')
                    ->label('Channel'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Last Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramConfigs::route('/'),
            'create' => Pages\CreateTelegramConfig::route('/create'),
            'edit' => Pages\EditTelegramConfig::route('/{record}/edit'),
        ];
    }
    protected function getActions(): array
{
    return [
        \Filament\Pages\Actions\CreateAction::make()
            ->hidden(\App\Models\TelegramConfig::query()->exists()),
    ];
}
}
