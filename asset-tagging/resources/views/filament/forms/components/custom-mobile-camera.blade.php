<div x-data="{
    images: @entangle($getStatePath()),
    lightboxImg: null,
    init() {
        if (!this.images || !Array.isArray(this.images)) {
            this.images = [null, null, null, null];
        }
    },
    handleFiles(e, index) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = 800;
                canvas.height = (img.height * 800) / img.width;
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                let newImages = [...this.images];
                newImages[index] = canvas.toDataURL('image/jpeg', 0.7);
                this.images = newImages;
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    },
    removePhoto(index) {
        let newImages = [...this.images];
        newImages[index] = null;
        this.images = newImages;
    }
}" style="width: 100%;">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <template x-for="i in [0, 1, 2, 3]" :key="i">
            <div style="border: 2px dashed #cbd5e1; height: 140px; position: relative; background: #f8fafc; border-radius: 12px; overflow: hidden;">

                <template x-if="!images[i]">
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: relative;">
                        <span style="font-size: 11px; color: #64748b; font-weight: bold; pointer-events: none;">FOTO <span x-text="i+1"></span></span>
                        <input type="file" accept="image/*" capture="environment"
                               style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;"
                               @change="handleFiles($event, i)">
                    </div>
                </template>

                <template x-if="images[i]">
                    <div style="width: 100%; height: 100%; position: relative;">
                        <img :src="images[i]" @click="lightboxImg = images[i]"
                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; position: relative; z-index: 5;">

                        <button type="button" @click.stop="removePhoto(i)"
                                style="position: absolute; top: 6px; right: 6px; background: rgba(220, 38, 38, 0.9); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; z-index: 20;">
                            &times;
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <template x-if="lightboxImg">
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;"
             @click="lightboxImg = null">
            <img :src="lightboxImg" style="max-width: 100%; max-height: 90%; border-radius: 8px;">
        </div>
    </template>
</div>
