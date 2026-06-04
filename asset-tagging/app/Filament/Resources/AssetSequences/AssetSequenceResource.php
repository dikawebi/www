<?php

namespace App\Filament\Resources\AssetSequences;

use App\Filament\Resources\AssetSequences\Pages\CreateAssetSequence;
use App\Filament\Resources\AssetSequences\Pages\EditAssetSequence;
use App\Filament\Resources\AssetSequences\Pages\ListAssetSequences;
use App\Models\AssetSequence;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // <-- PENTING: Menggunakan core blueprint Schema v4
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class AssetSequenceResource extends Resource
{
    protected static ?string $model = AssetSequence::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Number Sequence Asset';

    protected static \UnitEnum|string|null $navigationGroup = 'Sistem Konfigurasi';

    protected static bool $shouldRegisterNavigation = true;

    /**
     * UBAH KE STRUKTUR SCHEMA V4:
     * Mengganti form(Form $form) menjadi schema(Schema $schema)
     */


    // 🚀 TAMBAHKAN METHOD INI: Memaksa Filament mengabaikan Policy dan mengizinkan menu diakses
    public static function canViewAny(): bool
    {
        return true;
    }
    public static function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aturan Penomoran Per Kategori')
                    ->description('Tentukan format penomoran unik untuk setiap kategori barang.')
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori Aset')
                            ->relationship('category', 'name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->searchable()
                            ->preload(),

                        TextInput::make('prefix')
                            ->label('Prefix (Awalan Kode)')
                            ->required()
                            ->placeholder('Contoh: LAP, FUR'),

                        TextInput::make('format')
                            ->label('Format Penomoran')
                            ->required()
                            ->default('{prefix}-{year}-{sequence}'),

                        TextInput::make('next_value')
                            ->label('Nomor Urut Berikutnya')
                            ->numeric()
                            ->required()
                            ->default(1),

                        Select::make('padding')
                            ->label('Digit Angka (Padding)')
                            ->options([
                                3 => '3 Digit (001)',
                                4 => '4 Digit (0001)',
                                5 => '5 Digit (00001)',
                            ])
                            ->required()
                            ->default(4),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Kategori Aset')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('prefix')
                    ->label('Prefix')
                    ->badge(),

                TextColumn::make('format')
                    ->label('Format Grid'),

                TextColumn::make('next_value')
                    ->label('Urutan Berikutnya')
                    ->alignCenter(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetSequences::route('/'),
            'create' => CreateAssetSequence::route('/create'),
            'edit' => EditAssetSequence::route('/{record}/edit'),
        ];
    }
}
