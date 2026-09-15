<script setup lang="ts">
import { watch, ref } from 'vue';

const props = defineProps<{ success?: string | null; error?: string | null; errors?: Record<string, string> }>();
const toasts = ref<{ id: number; type: 'success' | 'error'; message: string }[]>([]);
let nextId = 0;
function push(type: 'success' | 'error', message: string) { const toast = { id: ++nextId, type, message }; toasts.value.push(toast); window.setTimeout(() => dismiss(toast.id), type === 'success' ? 4000 : 7000); }
function dismiss(id: number) { toasts.value = toasts.value.filter((toast) => toast.id !== id); }
watch(() => props.success, (message) => { if (message) push('success', message); }, { immediate: true });
watch(() => props.error, (message) => { if (message) push('error', message); }, { immediate: true });
watch(() => props.errors, (errors) => { const messages = [...new Set(Object.values(errors ?? {}))]; if (messages.length) push('error', messages.join(' ')); }, { deep: true, immediate: true });
</script>
<template><div class="pointer-events-none fixed right-4 top-20 z-[90] flex w-[min(390px,calc(100vw-2rem))] flex-col gap-2" aria-live="polite" aria-atomic="false"><TransitionGroup enter-active-class="transition duration-200" enter-from-class="translate-x-5 opacity-0" leave-active-class="transition duration-150" leave-to-class="translate-x-5 opacity-0"><div v-for="toast in toasts" :key="toast.id" class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-2xl" :role="toast.type === 'error' ? 'alert' : 'status'" :class="toast.type === 'success' ? 'border-emerald-200 bg-white text-slate-700' : 'border-rose-200 bg-rose-50 text-rose-800'"><span class="grid size-7 shrink-0 place-items-center rounded-lg text-xs font-bold" aria-hidden="true" :class="toast.type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ toast.type === 'success' ? '✓' : '!' }}</span><div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase tracking-wider" :class="toast.type === 'success' ? 'text-emerald-700' : 'text-rose-700'">{{ toast.type === 'success' ? 'Berhasil' : 'Perlu diperiksa' }}</p><p class="mt-1 text-sm leading-5">{{ toast.message }}</p></div><button :aria-label="`Tutup notifikasi ${toast.type === 'success' ? 'berhasil' : 'kesalahan'}`" class="text-slate-400 hover:text-slate-700" @click="dismiss(toast.id)">×</button></div></TransitionGroup></div></template>
