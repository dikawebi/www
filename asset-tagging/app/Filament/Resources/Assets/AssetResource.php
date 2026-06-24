<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\{CreateAsset, EditAsset, ListAssets, ViewAsset};
use App\Models\{Asset, AssetSequence};
use Filament\Schemas\Schema;
use Filament\Schemas\Components\{Section, Grid};
use Filament\Forms\Components\{TextInput, Select, FileUpload, ViewField};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Tables};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\{ViewAction, EditAction, DeleteAction, ButtonAction, BulkAction, DeleteBulkAction};
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
// Tambahkan tanda '\' di depan semua import Filament

use \Filament\Forms\Components\Repeater;
use \Filament\Forms\Components\DatePicker;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'Asset';

    /**
     * 1. SKEMA FORM (CREATE & EDIT)
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Utama')
                ->columns(2)
                ->schema([
                    TextInput::make('asset_id')
                        ->label('ID Aset')
                        ->default(function () {
                            $user = Auth::user();
                            $setting = AssetSequence::where('department_id', $user->department_id)->first();

                            if (!$setting) return 'Menunggu pengaturan...';

                            // Logika untuk menampilkan format (sama dengan preview)
                            $sequenceString = str_pad($setting->next_value, $setting->padding, '0', STR_PAD_LEFT);
                            return str_replace(['{prefix}', '{year}', '{sequence}'], [$setting->prefix, date('Y'), $sequenceString], $setting->format);
                        })
                        ->disabled() // User tidak boleh ganti manual
                        ->dehydrated(true), // Tetap kirim ke database
                    Placeholder::make('sequence_warning')
                        ->label('')
                        ->content(fn () => AssetSequence::where('department_id', Auth::user()->department_id)->exists()
                            ? '✅ Sequence siap.' : '⚠️ Perhatian: Sequence belum diatur.')
                        ->columnSpanFull(),

                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->required()
                        ->label('Kategori Aset'),

                    Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->required()
                        ->label('Brand'),

                    TextInput::make('name')
                        ->required()
                        ->label('Tipe / Seri'),

                    TextInput::make('serial_number')
                        ->required()
                        ->label('Serial Number'),

                    Select::make('status')
                        ->options(['In use' => 'In use', 'Idle' => 'Idle', 'Broke' => 'Broke'])
                        ->required(),
                ]),

            Section::make('Lokasi & Kepemilikan')
                ->columns(2)
                ->schema([
                    TextInput::make('pr_number')->label('Nomor PR'),
                    TextInput::make('po_number')->label('Nomor PO'),
                    Select::make('location_id')->relationship('location', 'name')->required(),
                    Select::make('department_id')->relationship('department', 'name')->required(),
                    TextInput::make('user_name')->label('Pemegang')->columnSpanFull()->required(),
                ]),
                Section::make('Dokumentasi Foto Aset')
    ->description('Ambil hingga 4 foto untuk mendokumentasikan kondisi fisik aset saat ini.')
    ->schema([
        ViewField::make('images')
            ->view('filament.forms.components.mobile-camera-upload')
            ->columnSpan('full'),
    ]),
        ]);
    }




    /**
     * 2. SKEMA INFOLIST (VIEW DETAIL)
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()
                ->columns(3)
                ->schema([
                    Section::make('Informasi Utama Aset')
                        ->icon('heroicon-m-identification')
                        ->columnSpan(2)
                        ->compact()
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('asset_id')->label('ID Aset')->weight('bold')->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('brand.name')->label('Merek Aset')->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('name')->label('Nama Barang'),
                            \Filament\Infolists\Components\TextEntry::make('status')->label('Status Kontrol')->badge(),
                            Grid::make(2)->schema([
                                \Filament\Infolists\Components\TextEntry::make('pr_number')->label('Nomor PR')->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('po_number')->label('Nomor PO')->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('location.name')->label('Lokasi Penempatan'),
                                \Filament\Infolists\Components\TextEntry::make('department.name')->label('Departemen Pemilik'),
                            ]),
                            \Filament\Infolists\Components\TextEntry::make('user_name')->label('Pemegang')->columnSpanFull(),
                        ]),

                    Section::make('Label & Foto')
                        ->columnSpan(1)
                        ->schema([
                            ViewField::make('qr_preview')->view('filament.forms.components.qr-preview'),
                            \Filament\Infolists\Components\ImageEntry::make('images')->label('Foto Fisik'),
                        ]),

                        // Tambahkan ini di dalam Schema di AssetResource.php
                    Section::make('Riwayat Perubahan & Perpindahan')
                        ->schema([
                            \Filament\Forms\Components\Repeater::make('histories')
                                ->relationship('histories') // Pastikan model Asset punya relasi 'histories'
                                ->schema([
                                    \Filament\Forms\Components\DatePicker::make('date')->required(),
                                    \Filament\Forms\Components\TextInput::make('description')->required(),
                                    \Filament\Forms\Components\TextInput::make('old_location'),
                                    \Filament\Forms\Components\TextInput::make('new_location'),
                                ])
                                ->columns(2)
                                ->collapsible(),
                        ]),
                ]),
        ]);
    }


     // 3. TABLE SCHEMA

    public static function table(Table $table): Table
    {
        return $table->columns([

            TextColumn::make('asset_id')->label('ID Aset')->searchable()->sortable(),
            TextColumn::make('brand.name')->label('Brand / Merek')->searchable(),
            TextColumn::make('name')->label('Nama')->searchable(),
            TextColumn::make('category.name')->label('Kategori'),
            TextColumn::make('location.name')->label('Lokasi'),
            TextColumn::make('department.name')->label('Dept'),
            TextColumn::make('user_name')->label('Pengguna'),
            TextColumn::make('status')
              ->badge()
              ->color(fn (string $state): string => match ($state) {
                'In use' => 'success',
                'Idle' => 'warning',
                'Broke' => 'danger',
              }),
                  ])
                  ->actions([
                            EditAction::make(),
                            DeleteAction::make(),
                            ButtonAction::make('print_qr')
                            ->label('Print QR')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->url(fn (Asset $record): string => route('asset.print-qr', $record->id))
                            ->openUrlInNewTab(),
                  ])
                  ->bulkActions([
                            BulkAction::make('print_qr_bulk')
                                ->label('Cetak QR Terpilih')
                                ->icon('heroicon-o-printer')
                                ->color('success')
                                ->action(function (Collection $records) {
                                    $ids = $records->pluck('id')->implode(',');
                                    // Pastikan nama rute di sini SAMA dengan yang di web.php
                                    return redirect()->route('asset.print-qr-bulk', ['ids' => $ids]);
                                }),
                            DeleteBulkAction::make(),
                  ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit'   => EditAsset::route('/{record}/edit'),
            'view'   => ViewAsset::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
    return [
        \App\Filament\Resources\Assets\RelationManagers\HistoriesRelationManager::class,
    ];
    }
}
