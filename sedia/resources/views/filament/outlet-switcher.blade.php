@php
    use App\Support\OutletContext;

    $user = OutletContext::user();
    $currentOutletId = OutletContext::currentOutletId();
    $currentOutlet = OutletContext::currentOutlet();
    $outletOptions = OutletContext::selectableOutletOptions();
@endphp

@if ($user)
    <div class="fi-topbar-start flex items-center gap-3">
        @if ($user->isAdmin())
            <form method="POST" action="{{ route('dashboard.outlet-context.update') }}" class="flex items-center gap-2">
                @csrf
                <label for="selected_outlet_id" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    Outlet
                </label>
                <select
                    id="selected_outlet_id"
                    name="selected_outlet_id"
                    onchange="this.form.submit()"
                    class="fi-input block w-56 rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none ring-0 transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Semua outlet</option>
                    @foreach ($outletOptions as $id => $name)
                        <option value="{{ $id }}" @selected((int) $currentOutletId === (int) $id)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </form>
        @elseif ($currentOutlet)
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                Outlet: <span class="font-semibold">{{ $currentOutlet->name }}</span>
            </div>
        @endif
    </div>
@endif
