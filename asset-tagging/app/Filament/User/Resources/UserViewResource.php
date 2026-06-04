<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\UserViewResource\Pages\CreateUserView;
use App\Filament\User\Resources\UserViewResource\Pages\ListUserViews;
use App\Filament\User\Resources\UserViewResource\Pages\ViewUserView;
use App\Models\Asset;
use App\Models\AssetSequence;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;// <-- GANTI DENGAN INI
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserViewResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Aset';

    protected static ?string $slug = 'user-views';

    public static function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormSection::make('Formulir Aset Baru')
                    ->description('Masukkan spesifikasi aset yang ingin ditambahkan ke sistem.')
                    ->schema([
                        TextInput::make('asset_id')
                            ->label('ID Aset (Otomatis Berdasar Kategori)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(function (Get $get) {
                                $categoryId = $get('category_id');
                                if (! $categoryId) {
                                    return 'Silakan pilih kategori terlebih dahulu...';
                                }

                                $setting = AssetSequence::where('category_id', $categoryId)->first();
                                $prefix = $setting ? $setting->prefix : 'AST';
                                $nextValue = $setting ? $setting->next_value : 1;
                                $padding = $setting ? $setting->padding : 4;
                                $format = $setting ? $setting->format : '{prefix}-{year}-{sequence}';

                                $sequenceString = str_pad($nextValue, $padding, '0', STR_PAD_LEFT);

                                return str_replace(['{prefix}', '{year}', '{sequence}'], [$prefix, date('Y'), $sequenceString], $format);
                            }),

                        TextInput::make('name')
                            ->label('Nama Barang')
                            ->required()
                            ->placeholder('Masukkan nama barang...'),

                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Kategori')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('location_id')
                            ->relationship('location', 'name')
                            ->label('Lokasi Penempatan')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->label('Penanggung Jawab (Departemen)')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Status Aset')
                            ->options([
                                'Idle' => 'Idle (Tersedia)',
                                'In use' => 'In use (Sedang Digunakan)',
                                'Broke' => 'Broke (Rusak)',
                            ])
                            ->required()
                            ->default('Idle'),

                        FileUpload::make('images')
                            ->label('Foto Fisik Barang')
                            ->image()
                            ->directory('asset-images')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('Detail Verifikasi Aset')
                    ->description('Informasi spesifikasi teknis, gambar fisik, dan kepemilikan barang.')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 4,
                        ])
                        ->schema([
                            Group::make([
                                ImageEntry::make('asset_id')
                                    ->label('Stiker QR Code')
                                    ->state(fn ($record) => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($record->asset_id))
                                    ->height(150)
                                    ->width(150)
                                    ->alignCenter()
                                    ->extraAttributes([
                                        'class' => 'p-2 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mx-auto',
                                    ]),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 1]),

                            Group::make([
                                ImageEntry::make('images')
                                    ->label('Foto Fisik Barang')
                                    ->height(150)
                                    ->width(150)
                                    ->defaultImageUrl(url('images/no-image.png'))
                                    ->alignCenter()
                                    ->extraAttributes([
                                        'class' => 'p-2 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mx-auto object-cover',
                                    ]),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 1]),

                            Group::make([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('asset_id')->label('ID Aset')->icon('heroicon-m-qr-code')->weight('bold'),
                                        TextEntry::make('name')->label('Nama Barang')->icon('heroicon-m-cube'),
                                        TextEntry::make('category.name')->label('Kategori')->icon('heroicon-m-tag'),
                                        TextEntry::make('location.name')->label('Lokasi Penempatan')->icon('heroicon-m-map-pin'),
                                        TextEntry::make('department.name')->label('Penanggung Jawab')->icon('heroicon-m-building-office'),
                                        TextEntry::make('status')
                                            ->label('Status Terkini')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'In use' => 'success',
                                                'Idle' => 'warning',
                                                'Broke' => 'danger',
                                                default => 'gray',
                                            })
                                            ->icon(fn (string $state): string => match ($state) {
                                                'In use' => 'heroicon-m-check-circle',
                                                'Idle' => 'heroicon-m-clock',
                                                'Broke' => 'heroicon-m-x-circle',
                                                default => 'heroicon-m-question-mark-circle',
                                            }),
                                    ]),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_id')->label('ID Aset')->searchable(),
                TextColumn::make('name')->label('Nama Barang')->searchable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'In use' => 'success',
                        'Idle' => 'warning',
                        'Broke' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'In use' => 'heroicon-m-check-circle',
                        'Idle' => 'heroicon-m-clock',
                        'Broke' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Aset Baru'),
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
            'create' => CreateUserView::route('/create'),
            'view' => ViewUserView::route('/{record}'),
        ];
    }
}
