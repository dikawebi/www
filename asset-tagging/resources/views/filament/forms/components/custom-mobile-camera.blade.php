<div x-data="{
    images: @entangle($getStatePath()),
    uploading: false,
    addPhoto(e) {
        const file = e.target.files[0];
        if (!file) return;

        this.uploading = true;
        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            if (!Array.isArray(this.images)) {
                this.images = [];
            }
            if (this.images.length < 4) {
                this.images.push(readerEvent.target.result);
            } else {
                alert('Maksimal 4 foto telah tercapai.');
            }
            this.uploading = false;
        };
        reader.readAsDataURL(file);
    },
    removePhoto(index) {
        this.images = this.images.filter((_, i) => i !== index);
    }
}" class="space-y-4">

    <div x-show="!images || images.length < 4" class="relative">
        <input
            type="file"
            accept="image/*"
            capture="environment"
            @change="addPhoto($event)"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
        >
        <button type="button" class="w-full py-4 px-6 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 text-white font-semibold rounded-xl shadow-lg flex items-center justify-center space-x-3 transition duration-200 transform active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 9 9" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a3 3 0 100-6 3 3 0 000 6z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8V6a2 2 0 00-2-2H5a2 2 0 00-2 2v2m2-3h.01" />
            </svg>
            <span x-text="images && images.length > 0 ? 'Tambah Foto Lainnya (' + images.length + '/4)' : 'Ambil Foto Aset (Kamera / Galeri)'"></span>
        </button>
        <p class="text-xs text-gray-500 text-center mt-2">Pastikan menekan tombol "Simpan" di bawah setelah foto selesai diambil.</p>
    </div>

    <div x-show="uploading" class="text-center py-2 text-sm text-primary-600 font-medium animate-pulse">
        Memproses gambar...
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mt-4">
        <template x-if="Array.isArray(images)" x-for="(img, index) in images" :key="index">
            <div class="relative group aspect-square rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white p-1">
                <img :src="img" class="w-full h-full object-cover rounded-lg" alt="Foto Aset">
                <button type="button" @click="removePhoto(index)" class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-1.5 shadow" title="Hapus foto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="absolute bottom-2 left-2 bg-gray-900/60 text-white text-xs px-2 py-0.5 rounded-md" x-text="'Foto ' + (index + 1)"></div>
            </div>
        </template>
    </div>
</div>
