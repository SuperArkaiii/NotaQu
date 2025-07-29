<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">
                    Selamat Datang, {{ Auth::user()->name }}
                </h2>
                <p class="text-sm text-gray-500">
                    Hari ini: {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <div>
                <span class="px-3 py-1 text-sm rounded-lg bg-primary-100 text-primary-700">
                    {{ Auth::user()->email }}
                </span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
