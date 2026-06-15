<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $relatedResource = AssetResource::class;

    public function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
            TextColumn::make('dari_lokasi')->label('Lokasi Lama'),
            TextColumn::make('ke_lokasi')->label('Lokasi Baru'),
            TextColumn::make('user_lama')->label('User Lama'),
            TextColumn::make('user_baru')->label('User Baru'),
        ])
        ->headerActions([
            CreateAction::make()->label('Catat Perpindahan Baru'),
        ]);
}
}
