<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asset_id'),
                TextEntry::make('name'),
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('location.name')
                    ->label('Location'),
                TextEntry::make('department.name')
                    ->label('Department'),
                TextEntry::make('pr_number')
                    ->placeholder('-'),
                TextEntry::make('po_number')
                    ->placeholder('-'),
                TextEntry::make('user_name')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('images')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
