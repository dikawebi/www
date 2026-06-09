<?php

namespace App\Filament\User\Resources;

use Filament\Forms\Components\ViewField;
use App\Models\Asset;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

// Komponen Input Utama Khusus Form (Create & Edit)
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;

// Komponen Tata Letak Layouting Filament v4
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Actions\ViewAction;
use Filament\Actions\ButtonAction;

class UserViewResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Assets';

    protected static ?string $slug = 'assets';

    protected static ?string $recordTitleAttribute = 'Asset';

    /**
     * 💡 PERISAI ANTI-CRASH V4:
     * Jika file CreateUserView123.php lama Anda masih tersangkut di cache Livewire
     * dan nekat memanggil method ->schema(), fungsi di bawah ini akan menangkapnya
     * dan mengalirkannya dengan aman ke method form() tanpa memicu BadMethodCallException!
     */
    public static function schema(Schema $schema): Schema
    {
        return static::form($schema);
    }

    /**
     * 1. SKEMA FORM (UNTUK HALAMAN CREATE & EDIT DI PANEL USER)
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                // --- SISI KIRI (2/3 LAYAR): INPUT DATA ---
                Section::make()
                    ->schema([

                        Section::make('Informasi Utama Aset')
                            ->icon('heroicon-m-identification')
                            ->compact()
                            ->schema([

                                Placeholder::make('asset_id_preview')
                                    ->label('Pratinjau ID Aset Baru')
                                    ->content(function ($get) {
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

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('Kategori Aset')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
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
                            ])->columns(2),

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

                // --- SISI KANAN (1/3 LAYAR): QR CODE & CAMERA HP ---
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
                            ->description('Klik area di bawah untuk memicu kamera belakang HP Anda.')
                            ->compact()
                            ->schema([
                                FileUpload::make('images')
                                    ->label('Foto Fisik Aset')
                                    ->image()
                                    ->maxSize(2048)
                                    ->directory('asset-images')
                                    ->columnSpanFull()
                                    ->live()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->extraInputAttributes([
                                        'accept' => 'image/*',
                                        'capture' => 'camera'
                                    ]),
                            ]),
                    ])
                    ->columnSpan(1),

            ])->columns(3);
    }

    /**
     * 2. SKEMA INFOLIST (UNTUK HALAMAN VIEW DETAIL / READ-ONLY DI PANEL USER)
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Section::make('Informasi Utama Aset')
                            ->icon('heroicon-m-identification')
                            ->compact()
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('asset_id')->label('ID Aset')->weight('bold')->columnSpanFull(),
                                \Filament\Infolists\Components\TextEntry::make('category.name')->label('Kategori Aset')->columnSpanFull(),
                                \Filament\Infolists\Components\TextEntry::make('name')->label('Nama Barang'),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->label('Status Kontrol')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'In use' => 'success',
                                        'Idle' => 'warning',
                                        'Broke' => 'danger',
                                    }),
                            ])->columns(2),

                        Section::make('Lokasi, Departemen & Pengadaan')
                            ->icon('heroicon-m-map-pin')
                            ->compact()
                            ->schema([
                                Grid::make(2)->schema([
                                    \Filament\Infolists\Components\TextEntry::make('pr_number')->label('Nomor PR')->placeholder('-'),
                                    \Filament\Infolists\Components\TextEntry::make('po_number')->label('Nomor PO')->placeholder('-'),
                                ]),
                                Grid::make(2)->schema([
                                    \Filament\Infolists\Components\TextEntry::make('location.name')->label('Lokasi Penempatan'),
                                    \Filament\Infolists\Components\TextEntry::make('department.name')->label('Departemen Pemilik'),
                                ]),
                                \Filament\Infolists\Components\TextEntry::make('user_name')->label('Nama Pengguna / Pemegang Aset')->placeholder('-')->columnSpanFull(),
                            ]),
                    ])->columnSpan(2),

                Section::make()
                    ->schema([
                        Section::make('Label QR Code')
                            ->compact()
                            ->schema([
                                \Filament\Infolists\Components\ViewEntry::make('qr_preview')->view('filament.forms.components.qr-preview')->columnSpanFull(),
                            ]),
                        Section::make('Visual Foto Fisik Produk')
                            ->compact()
                            ->schema([
                                \Filament\Infolists\Components\ImageEntry::make('images')->label('Foto Fisik Aset')->columnSpanFull(),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    /**
     * 3. KONFIGURASI TABEL INDEKS DATA PANEL USER
     */
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
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'In use' => 'success',
                    'Idle' => 'warning',
                    'Broke' => 'danger',
                }),
            ])
            ->actions([
                ViewAction::make(),
                ButtonAction::make('print_qr')
                    ->label('Print QR')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Asset $record): string => route('asset.print-qr', $record->id))
                    ->openUrlInNewTab(),
            ]);
    }

    /**
     * 4. ROUTING PAGES
     */
public static function getPages(): array
{
    return [
        'index' => \App\Filament\User\Resources\UserViewResource\Pages\ListUserViews::route('/'),
        'create' => \App\Filament\User\Resources\UserViewResource\Pages\CreateUserView::route('/create'),
        'view' => \App\Filament\User\Resources\UserViewResource\Pages\ViewUserView::route('/{record}'),
    ];
}
}
