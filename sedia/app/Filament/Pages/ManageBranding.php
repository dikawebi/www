<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Branding;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class ManageBranding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?string $title = 'Branding Perusahaan';

    protected string $view = 'filament.pages.manage-branding';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => Branding::appName(),
            'app_logo_path' => Setting::get('app_logo_path'),
            'app_favicon_path' => Setting::get('app_favicon_path'),
            'app_primary_color' => Branding::primaryColor(),
            'business_address' => Setting::get('business_address'),
            'business_phone' => Setting::get('business_phone'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Usaha')->schema([
                    TextInput::make('app_name')->label('Nama Usaha')->required()->maxLength(60)->helperText('Tampil di struk sebagai “Nama Usaha - Nama Cabang”'),
                    Textarea::make('business_address')->label('Alamat Usaha (pusat)')->rows(2)->maxLength(255)->helperText('Jika outlet belum punya alamat, alamat ini dipakai di struk.'),
                    TextInput::make('business_phone')->label('No. Telp Usaha')->tel()->maxLength(20),
                ])->columns(2),
                Section::make('Visual & Tema')->schema([
                    FileUpload::make('app_logo_path')->label('Logo Perusahaan')->image()->disk('public')->directory('branding')->visibility('public')->imagePreviewHeight('80')->helperText('PNG/WebP transparan, max 1MB. Muncul di sidebar & struk.'),
                    FileUpload::make('app_favicon_path')->label('Favicon')->image()->disk('public')->directory('branding')->visibility('public')->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml'])->helperText('ICO/PNG 32×32. Muncul di tab browser.'),
                    ColorPicker::make('app_primary_color')->label('Warna Primary')->helperText('Mempengaruhi tombol & accent. Default #f59e0b'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('app_name', $data['app_name'] ?? 'Sedia');
        Setting::set('app_logo_path', $data['app_logo_path'] ?? null);
        Setting::set('app_favicon_path', $data['app_favicon_path'] ?? null);
        Setting::set('app_primary_color', $data['app_primary_color'] ?? '#f59e0b');
        Setting::set('business_address', $data['business_address'] ?? null);
        Setting::set('business_phone', $data['business_phone'] ?? null);

        Cache::forget('setting:app_name');
        Cache::forget('setting:app_logo_path');
        Cache::forget('setting:app_favicon_path');
        Cache::forget('setting:app_primary_color');
        Cache::forget('setting:business_address');
        Cache::forget('setting:business_phone');

        Notification::make()->title('Branding disimpan')->success()->send();
    }
}
