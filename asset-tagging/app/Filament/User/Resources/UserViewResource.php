<?php

namespace App\Filament\User\Resources;

use App\Models\Asset;
use App\Filament\User\Resources\UserViewResource\Pages\ListUserViews;
use App\Filament\User\Resources\UserViewResource\Pages\ViewUserView;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry; // <-- IMPORT KOMPONEN DETAIL V4
//use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
//use Filament\Tables\Actions\ViewAction;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Actions\ViewAction;
class UserViewResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Daftar Aset';
    protected static ?string $slug = 'user-views';

    /**
     * UNTUK HALAMAN INPUT / EDIT DATA (Menggunakan TextInput & Select)
     */
    public static function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulir Aset')
                    ->schema([
                        TextInput::make('asset_id')->label('ID Aset'),
                        TextInput::make('name')->label('Nama Barang'),
                        Select::make('category_id')->relationship('category', 'name')->label('Kategori'),
                        Select::make('location_id')->relationship('location', 'name')->label('Lokasi'),
                        Select::make('department_id')->relationship('department', 'name')->label('Departemen'),
                        TextInput::make('status')->label('Status'),
                    ])
                    ->columns(2)
            ]);
    }

    /**
     * UNTUK HALAMAN DETAIL / VIEW RECORD (Menggunakan TextEntry)
     * Ini dijamin 100% langsung menarik data dari database secara otomatis di v4
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Lengkap Aset')
                    ->schema([
                        TextEntry::make('asset_id')->label('ID Aset'),
                        TextEntry::make('name')->label('Nama Barang'),
                        TextEntry::make('category.name')->label('Kategori'), // Langsung panggil relasinya
                        TextEntry::make('location.name')->label('Lokasi'),
                        TextEntry::make('department.name')->label('Departemen'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge() // Membuat status menjadi badge berwarna agar lebih interaktif
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'maintenance' => 'warning',
                                'broken' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_id')->label('ID Aset')->searchable(),
                TextColumn::make('name')->label('Nama Barang')->searchable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('status')->label('Status'),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserViews::route('/'),
            'view' => ViewUserView::route('/{record}'),
        ];
    }
}
