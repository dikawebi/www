<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?int $navigationSort = 99;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('User')->default('Sistem')->sortable(),
                TextColumn::make('outlet.name')->label('Outlet')->default('-'),
                TextColumn::make('subject_type')->label('Entitas')->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')->badge()->color('gray'),
                TextColumn::make('subject_id')->label('ID'),
                TextColumn::make('action')->label('Aksi')->badge()->color(fn ($state) => match ($state) {
                    'created' => 'success',
                    'updated' => 'warning',
                    'deleted' => 'danger',
                    'voided' => 'danger',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled' => 'danger',
                    'sent' => 'info',
                    'received' => 'success',
                    default => 'gray',
                }),
                TextColumn::make('description')->label('Deskripsi')->limit(60)->wrap(),
            ])
            ->filters([
                SelectFilter::make('action')->options([
                    'created' => 'Created',
                    'updated' => 'Updated',
                    'deleted' => 'Deleted',
                    'voided' => 'Voided',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
