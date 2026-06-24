<div x-data="{ state: $wire.entangle('{{ $getStatePath() }}') }">
    <div class="flex flex-col space-y-2">
        <input
            type="file"
            accept="image/*"
            capture="environment"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
            @change="
                const file = $event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        // Menyimpan base64 atau memprosesnya sesuai kebutuhan Filament
                        state = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            "
        />

        <template x-if="state">
            <img :src="state" class="w-32 h-32 object-cover rounded-lg border border-gray-200 mt-2" alt="Preview">
        </template>
    </div>
</div>
