<?php

namespace App\Providers;

use App\Models\Asset;
use App\Observers\AssetObserver;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Asset::observe(AssetObserver::class);

        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Menambahkan tombol tepat DI SETELAH kolom Global Search (Sebelum Profile)
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            function (): string {
                $currentUrl = request()->getRequestUri();

                // Pastikan hanya muncul jika sedang mengakses panel administrator
                if (str_contains($currentUrl, '/administrator')) {
                    // Otomatis arahkan ke panel user dengan mencocokkan pola URL-nya
                    $userUrl = str_replace('/administrator', '/user', $currentUrl);
                    $userUrl = str_replace('/assets', '/user-views', $userUrl);

                    return Blade::render('
                        <div class="hidden md:flex items-center ms-4 me-2">
                            <a href="' . $userUrl . '" class="fi-btn fi-btn-size-sm fi-btn-color-gray fi-color-custom fi-btn-style-filled inline-flex items-center justify-center font-semibold rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 gap-1.5 shadow-sm transition">
                                <x-heroicon-m-user class="w-3.5 h-3.5 text-gray-500" />
                                Lihat Tampilan User
                            </a>
                        </div>
                    ');
                }

                return '';
            }
        );
    }
}
