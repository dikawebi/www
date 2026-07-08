<div x-data="{
    images: @entangle($getStatePath()),
    lightboxImg: null,
    handleFiles(e, index) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            if(!Array.isArray(this.images)) this.images = [];
            this.images[index] = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}" class="w-full">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <template x-for="i in [0, 1, 2, 3]" :key="i">
            <div style="border: 2px dashed #999; height: 120px; position: relative; background: #f9f9f9; display: flex; align-items: center; justify-content: center;">

                <template x-if="!images[i]">
                    <div style="text-align: center; cursor: pointer;">
                        <span style="font-size: 10px; color: #555;">Klik untuk Foto <span x-text="i+1"></span></span>
                        <input type="file" accept="image/*" capture="environment"
                               style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0;"
                               @change="handleFiles($event, i)">
                    </div>
                </template>

                <template x-if="images[i]">
                    <div style="width: 100%; height: 100%; position: relative;">
                        <img :src="images[i]" @click="lightboxImg = images[i]"
                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;">

                        <div style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: white; padding: 2px 8px; font-size: 10px; border-radius: 4px;">
                            Ganti
                            <input type="file" accept="image/*" capture="environment"
                                   style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0;"
                                   @change="handleFiles($event, i)">
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <template x-if="lightboxImg">
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;"
             @click="lightboxImg = null">
            <img :src="lightboxImg" style="max-width: 90%; max-height: 80%; border: 2px solid white;">
            <p style="position: absolute; bottom: 20px; color: white;">Klik gambar untuk tutup</p>
        </div>
    </template>
</div>
