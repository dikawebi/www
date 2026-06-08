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
// 💡 KUNCI UTAMA V4: Cukup import class utamanya saja
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
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

                // --- SISI KIRI (KOLOM 2/3): TABS FORM INPUT UTAMA ---
                Tabs::make('Asset Form Management')
                    ->tabs([

                        // 💡 SOLUSI EROR V4: Menulis sub-komponen tab langsung menggunakan array key 'schema' inside Tab::make()
                        Tabs\Tab::make('Informasi Utama')
                            ->icon('heroicon-m-identification')
                            ->schema([
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

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('Kategori Aset')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // TAB 2: LOGISTIK & PENEMPATAN
                        Tabs\Tab::make('Penempatan & Finansial')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                Section::make('Referensi Dokumen Pembelian')
                                    ->description('Informasi administrasi pengadaan aset.')
                                    ->compact()
                                    ->schema([
                                        TextInput::make('pr_number')
                                            ->label('Nomor PR (Purchase Request)')
                                            ->placeholder('PR-XXXXXX'),
                                        TextInput::make('po_number')
                                            ->label('Nomor PO (Purchase Order)')
                                            ->placeholder('PO-XXXXXX'),
                                    ])->columns(2),

                                Section::make('Lokasi & Penanggung Jawab')
                                    ->description('Informasi posisi fisik aset saat ini.')
                                    ->compact()
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

                                        TextInput::make('user_name')
                                            ->label('Nama Pengguna / Pemegang Aset')
                                            ->placeholder('Masukkan nama personil penanggung jawab')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpan(2),

                // --- SISI KANAN (KOLOM 1/3): QR CODE & INTEGRASI MEDIA KAMERA ---
                Section::make()
                    ->schema([
                        Section::make('Live Label QR Code')
                            ->compact()
                            ->schema([
                                ViewField::make('qr_preview')
                                    ->view('filament.forms.components.qr-preview')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Visual Foto Fisik Produk')
                            ->description('Unggah atau ambil gambar langsung lewat kamera.')
                            ->compact()
                            ->schema([
                                FileUpload::make('images')
                                    ->label('Gambar Produk (Maksimal 4 Foto)')
                                    ->multiple()
                                    ->image()
                                    ->maxFiles(4)
                                    ->maxSize(2048)
                                    ->directory('asset-images')
                                    ->columnSpanFull()
                                    ->live()
                                    ->uploadingMessage('Sedang mengunggah foto ke server, mohon tunggu...')
                                    ->keepFiles()
                                    ->downloadable()
                                    ->openable()
                                    ->capture('environment'),
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
