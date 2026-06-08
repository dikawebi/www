<?php

namespace App\Filament\Resources\Assets;

use Filament\Forms\Components\ViewField;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\Schemas\AssetInfolist;
use App\Models\Asset;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ButtonAction;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Asset';

    public static function form(Schema $schema): Schema
{
    return $schema
        ->schema([

            // --- SISI KIRI (MEMAKAN RUANG 2/3 LAYAR): BLOK INPUT DATA ---
            Section::make()
                ->schema([

                    // KELOMPOK 1: IDENTITAS UTAMA ASET
                    Section::make('Informasi Utama Aset')
                        ->icon('heroicon-m-identification')
                        ->compact()
                        ->schema([
                            // 1. ID Aset ditaruh paling atas (Full Width) sebagai indikator kode utama
                            TextInput::make('asset_id')
                                ->label('ID Aset (Otomatis Berdasar Kategori)')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder(function ($get) {
                                    $categoryId = $get('category_id');
                                    if (! $categoryId) {
                                        return 'Silakan pilih kategori terlebih dahulu...';
                                    }

                                    $setting = \App\Models\AssetSequence::where('category_id', $categoryId)->first();
                                    $prefix = $setting ? $setting->prefix : 'AST';
                                    $nextValue = $setting ? $setting->next_value : 1;
                                    $padding = $setting ? $setting->padding : 4;
                                    $format = $setting ? $setting->format : '{prefix}-{year}-{sequence}';

                                    $sequenceString = str_pad($nextValue, $padding, '0', STR_PAD_LEFT);
                                    return str_replace(['{prefix}', '{year}', '{sequence}'], [$prefix, date('Y'), $sequenceString], $format);
                                })
                                ->columnSpanFull(),

                            // 2. Kategori Aset dipindah ke atas (Full Width) tepat di bawah ID Aset agar memicu reaktivitas secara instan
                            Select::make('category_id')
                                ->relationship('category', 'name')
                                ->label('Kategori Aset')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->columnSpanFull(),

                            // 3. Nama Barang dan Status Kontrol berdampingan di baris berikutnya setelah kategori ditentukan
                            TextInput::make('name')
                                ->label('Nama Barang')
                                ->placeholder('Contoh: Laptop MacBook Pro M3')
                                ->required(),

                            Select::make('status')
                                ->label('Status Kontrol')
                                ->options([
                                    'In use' => 'In use (Aktif Digunakan)',
                                    'Idle' => 'Idle (Tersedia di Gudang)',
                                    'Broke' => 'Broke (Rusak / Butuh Perbaikan)',
                                ])
                                ->native(false)
                                ->required(),
                        ])->columns(2),

                    // KELOMPOK 2: LOGISTIK & ADMINISTRASI PENEMPATAN
                    Section::make('Lokasi, Departemen & Pengadaan')
                        ->icon('heroicon-m-map-pin')
                        ->compact()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('pr_number')
                                        ->label('Nomor PR (Purchase Request)')
                                        ->placeholder('PR-XXXXXX'),
                                    TextInput::make('po_number')
                                        ->label('Nomor PO (Purchase Order)')
                                        ->placeholder('PO-XXXXXX'),
                                ]),

                            Grid::make(2)
                                ->schema([
                                        Select::make('location_id')
                                        ->label('Lokasi Penempatan')
                                        ->relationship('location', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Select::make('department_id')
                                        ->label('Departemen Pemilik')
                                        ->relationship('department', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                ]),

                            TextInput::make('user_name')
                                ->label('Nama Pengguna / Pemegang Aset')
                                ->placeholder('Masukkan nama personil penanggung jawab')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpan(2),

            // --- SISI KANAN (MEMAKAN RUANG 1/3 LAYAR): PREVIEW QR & AKSES MEDIA ---
            Section::make()
                ->schema([

                    // PREVIEW QR CODE
                    Section::make('Live Label QR Code')
                        ->compact()
                        ->schema([
                            ViewField::make('qr_preview')
                                ->view('filament.forms.components.qr-preview')
                                ->columnSpanFull(),
                        ]),

                    // PENGATURAN INTEGRASI FOTO
                    Section::make('Visual Foto Fisik Produk')
    ->description('Ambil foto langsung melalui kamera HP Anda untuk dokumentasi aset.')
    ->compact()
    ->schema([
        FileUpload::make('images')
            ->label('Ambil Foto Aset (Gunakan Kamera HP)')
            ->image()
            ->maxSize(2048)
            ->directory('asset-images')
            ->columnSpanFull()
            ->live()
            ->uploadingMessage('Sedang memproses gambar, mohon tunggu...')
            ->downloadable()
            ->openable()

            // 💡 TRIK UI/UX MUTLAK UNTUK HP:
            // 1. Hilangkan ->multiple() karena kamera HP tidak bisa menjepret beberapa foto sekaligus dalam satu klik.
            // 2. Gunakan pembatasan tipe mime murni untuk memicu modul kamera internal OS.
            ->acceptedFileTypes(['image/jpeg', 'image/png'])
            ->extraInputAttributes([
                'accept' => 'image/*',
                'capture' => 'camera' // Menggunakan 'camera' sebagai fallback universal jika 'environment' diblokir OS
            ]),
    ]),
                ])
                ->columnSpan(1),

        ])->columns(3);

    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_id')->label('ID Aset')->searchable()->sortable(),
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
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
