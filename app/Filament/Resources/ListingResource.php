<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Filament\Resources\ListingResource\RelationManagers;
use App\Models\Listing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;


class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    // protected static ?string $model = \App\Models\Listing::class;


    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('price')->numeric()->prefix('ETB'),
            Forms\Components\TextInput::make('location'),
            Forms\Components\Select::make('type')
                ->options([
                    'property' => 'Property',
                    'car' => 'Car',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'available' => 'Available',
                    'sold' => 'Sold',
                    'rented' => 'Rented',
                ])
                ->default('available'),
            Forms\Components\TextInput::make('year'),
            Forms\Components\TextInput::make('condition'),
            Forms\Components\TextInput::make('size'),
            Forms\Components\TextInput::make('capacity'),
            Forms\Components\TagsInput::make('features'),
            Forms\Components\Textarea::make('description'),
    //        SpatieMediaLibraryFileUpload::make('images')
    // ->collection('images')
    // ->multiple()
    // ->enableReordering(),
    SpatieMediaLibraryFileUpload::make('primary')
    ->collection('primary')
    ->image(),

SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->multiple()
    ->enableReordering(),
        ]);
}


    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('type')->sortable(),
            Tables\Columns\TextColumn::make('price')->money('etb')->sortable(),
            Tables\Columns\TextColumn::make('location')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('year')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('type')
                ->options([
                    'car' => 'Car',
                    'property' => 'Property',
                ]),
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'available' => 'Available',
                    'sold' => 'Sold',
                    'rented' => 'Rented',
                ]),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}


