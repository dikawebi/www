<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use App\Models\Location;
use App\Models\Department;


class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    public function form(Schema $schema): Schema
{
    $asset = $this->getOwnerRecord();
    $lastHistory = $asset->histories()->latest()->first();

    // Tentukan nama lokasi lama
    $lokasiLamaId = $lastHistory ? $lastHistory->ke_lokasi : $asset->location_id;
    $lokasiLamaNama = Location::find($lokasiLamaId)?->name ?? 'Gudang';

    return $schema->schema([
        Forms\Components\TextInput::make('dari_lokasi_nama')
            ->label('Lokasi Lama')
            ->default($lokasiLamaNama)
            ->disabled()
            ->dehydrated(true),

        Forms\Components\Hidden::make('dari_lokasi')
        ->default($lokasiLamaId),
        //Forms\Components\TextInput::make('dari_lokasi')
        //    ->label('Lokasi Lama')
        //    ->default($lastHistory ? $lastHistory->ke_lokasi : ($asset->location?->name ?? 'Gudang'))
        //    ->disabled()
        //    ->dehydrated(),

        Forms\Components\Select::make('ke_lokasi')
            ->label('Lokasi Baru')
            ->options(\App\Models\Location::query()->select('id', 'name')->pluck('name', 'id'))
            ->required(),

        Forms\Components\Select::make('ke_departemen') // Pastikan nama kolom sesuai database
            ->label('Departemen Tujuan')
            ->options(\App\Models\Department::query()->select('id', 'name')->pluck('name', 'id'))
            ->required(),

        Forms\Components\TextInput::make('user_lama')
            ->label('User Lama')
            ->default($lastHistory ? $lastHistory->user_baru : $asset->user_name)
            ->disabled()
            ->dehydrated(),

        Forms\Components\TextInput::make('user_baru')
            ->label('User Baru')
            ->required(),
    ]);
}
public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('created_at')
                ->label('Waktu')
                ->dateTime('d M Y H:i'),

            // Gunakan relasi agar Filament mengambil 'name' dari model terkait
            Tables\Columns\TextColumn::make('dariLokasi.name')
                ->label('Lokasi Lama'),

            Tables\Columns\TextColumn::make('keLokasi.name')
                ->label('Lokasi Baru'),

            Tables\Columns\TextColumn::make('user_lama')
                ->label('User Lama'),

            Tables\Columns\TextColumn::make('user_baru')
                ->label('User Baru'),

            Tables\Columns\TextColumn::make('departemenTujuan.name')
                ->label('Departemen Tujuan'),
        ])
        ->headerActions([
            CreateAction::make()->label('Catat Perpindahan Baru')
            ->mutateFormDataUsing(function (array $data): array {
                $asset = $this->getOwnerRecord();

                // Pastikan data yang diperlukan terpenuhi
                $data['ke_departemen'] = $data['ke_departemen'] ?? $asset->department_id;

                // Logika dari lokasi/user sebelumnya
                $lastHistory = $asset->histories()->latest()->first();
                $data['dari_lokasi'] = $lastHistory ? $lastHistory->ke_lokasi : $asset->location_id;
                $data['user_lama'] = $lastHistory ? $lastHistory->user_baru : $asset->user_name;

                return $data;
    })
                   // ->after(function ($record) {
                   //     // Opsional: Update data di tabel Asset utama agar sinkron
                   //     $asset = $this->getOwnerRecord();
                   //     $asset->update([
                   //         'location_id' => $record->ke_lokasi,
                   //         'user_name' => $record->user_baru,
                   //     ]);
                   // }),
            ]);
    }
}
