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

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
            Section::make('Detail Verifikasi Aset')
                ->description('Informasi spesifikasi teknis dan kepemilikan barang.')
                ->schema([

                    // Cukup panggil Grid::make() langsung karena sudah di-import di atas
                    Grid::make([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([

                        // Cukup panggil Group::make() langsung
                        Group::make([
    ImageEntry::make('asset_id')
        ->label('Stiker QR Code')
        ->state(fn ($record) => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($record->asset_id))
        ->height(180)
        ->width(180)
        ->alignCenter() // <-- PERBAIKAN: Taruh alignCenter di dalam ImageEntry, BUKAN di Group
        ->extraAttributes([
            'class' => 'p-2 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mx-auto' // Ditambahkan 'mx-auto' untuk memastikan posisi tengah di CSS
        ]),
])
->columnSpan(['default' => 1, 'md' => 1]), // <-- Hapus ->alignCenter() yang tadinya menggantung di sini

                        // Cukup panggil Group::make() langsung
                        Group::make([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('asset_id')
                                        ->label('ID Aset')
                                        ->icon('heroicon-m-qr-code')
                                        ->weight('bold'),

                                    TextEntry::make('name')
                                        ->label('Nama Barang')
                                        ->icon('heroicon-m-cube'),

                                    TextEntry::make('category.name')
                                        ->label('Kategori')
                                        ->icon('heroicon-m-tag'),

                                    TextEntry::make('location.name')
                                        ->label('Lokasi Penempatan')
                                        ->icon('heroicon-m-map-pin'),

                                    TextEntry::make('department.name')
                                        ->label('Penanggung Jawab')
                                        ->icon('heroicon-m-building-office'),

                                    TextEntry::make('status')
                                        ->label('Status Terkini')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'active' => 'success',
                                            'maintenance' => 'warning',
                                            'broken' => 'danger',
                                            default => 'gray',
                                        }),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'md' => 2]),

                    ]),
                ])
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
