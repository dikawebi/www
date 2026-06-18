<?php

namespace App\Providers;


use App\Models\Asset;
use App\Observers\AssetObserver;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        // Trik wajib InfinityFree agar mengenali folder root publik baru
        $this->app->bind('path.public', function() {
            return base_path('../htdocs');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Asset::observe(AssetObserver::class);

        if (config('app.env') === 'production' || isset($_SERVER['HTTP_X_VERCEL_ID'])) {
            URL::forceScheme('https');
        }
        //URL::forceScheme('https'); // Pastikan semua URL menggunakan HTTPS

        //Mendaftarkan tombol tepat DI SETELAH kolom Global Search (Sebelum Profile)
    FilamentView::registerRenderHook(
        PanelsRenderHook::GLOBAL_SEARCH_AFTER,
        function (): string {
            $currentUrl = request()->getRequestUri();

            // Pastikan hanya muncul jika sedang mengakses panel administrator
            if (str_contains($currentUrl, '/administrator')) {
                // Otomatis arahkan ke panel user dengan mencocokkan pola URL-nya
                $userUrl = str_replace('/administrator', '/user', $currentUrl);
                $userUrl = str_replace('/assets', '/user-views', $userUrl);

                // Menggunakan margin-left (ms-4) dan margin-right (me-2) agar pas di sela-sela topbar
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
