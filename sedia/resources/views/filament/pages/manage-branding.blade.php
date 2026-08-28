<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary">Simpan Branding</x-filament::button>
        </div>
    </form>
    @php $logo = \App\Support\Branding::appLogoUrl(); $name = \App\Support\Branding::appName(); @endphp
    <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
        <div class="text-xs font-bold uppercase tracking-widest text-gray-400">Preview</div>
        <div class="mt-3 flex items-center gap-3">
            @if($logo)<img src="{{ $logo }}" alt="logo" class="h-10 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-600 object-contain p-1"> @endif
            <div>
                <div class="text-sm font-bold">{{ $name }}</div>
                <div class="text-xs text-gray-500">Warna: <span class="inline-block h-3 w-3 rounded-full align-middle" style="background:{{ \App\Support\Branding::primaryColor() }}"></span> {{ \App\Support\Branding::primaryColor() }} • Favicon: {{ \App\Support\Branding::faviconUrl() }}</div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
