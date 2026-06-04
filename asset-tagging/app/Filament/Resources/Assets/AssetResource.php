<?php

namespace App\Filament\Resources\Assets;


use Filament\Forms\Components\ViewField;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
//use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Schemas\AssetInfolist;
//use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Models\Asset;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
//use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
//use Filament\Tables\Actions\Action;
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
        //return AssetForm::configure($schema);
            return $schema
            ->schema([
             Section::make()
                ->schema([
                    Section::make('Informasi Utama')
                        ->schema([
                            TextInput::make('asset_id')
                                ->label('ID Aset (Otomatis Berdasar Kategori)')
                                ->disabled() // Kunci agar admin tidak mengetik manual
                                ->dehydrated(false) // Jangan kirim data kosong, karena digenerate di sisi server (CreateAsset)
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
                                }),
                            TextInput::make('name')
                                ->label('Nama Barang')
                                ->required(),
                            Select::make('category_id')
                                ->relationship('category', 'name')
                                ->label('Kategori')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live(), // !!! WAJIB AKTIF: Agar form admin reaktif me-refresh placeholder ID Aset
                            Select::make('status')
                                ->options([
                                    'In use' => 'In use',
                                    'Idle' => 'Idle',
                                    'Broke' => 'Broke',
                                ])->required(),
                        ])->columns(2),

                    Section::make('Penempatan & Dokumen')
                        ->schema([
                            TextInput::make('pr_number')->label('Nomor PR'),
                            TextInput::make('po_number')->label('Nomor PO'),
                            Select::make('location_id')
                                ->label('Lokasi')
                                ->relationship('location', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('department_id')
                                ->label('Departemen')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('user_name')->label('Nama Pengguna'),
                        ])->columns(2),
                ])->columnSpan(2),

            // Sisi Kanan: Untuk menampilkan Live QR Code dan Foto Produk
            Section::make()
                ->schema([
                    // FITUR BARU: Menampilkan QR Code langsung di dalam halaman detail/edit
                    ViewField::make('qr_preview')
                        ->view('filament.forms.components.qr-preview')
                        ->columnSpanFull(),

                    Section::make('Foto Produk')
                        ->schema([
                            FileUpload::make('images')
                                ->label('Gambar Produk (Maksimal 4 Foto)')
                                ->multiple()
                                ->image()
                                ->maxFiles(4)
                                ->directory('asset-images')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(1),

        ])->columns(3);

    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
       // return AssetsTable::configure($table);
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

                // Fitur: Button Print QR & ID
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
