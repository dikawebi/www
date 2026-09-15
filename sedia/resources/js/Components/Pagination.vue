<script setup lang="ts">
import { router } from '@inertiajs/vue3';
defineProps<{ links: { url: string | null; label: string; active: boolean }[] }>();
function go(url: string | null) { if (url) router.get(url, {}, { preserveScroll: true, preserveState: true }); }
</script>
<template><nav v-if="links?.length > 3" class="flex flex-wrap items-center justify-center gap-1 border-t border-stone-100 px-5 py-4" aria-label="Pagination"><button v-for="link in links" :key="link.label" :disabled="!link.url" class="rounded-lg px-3 py-1.5 text-xs disabled:opacity-40" :class="link.active ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100'" @click="go(link.url)">{{ link.label.replace('&laquo; Previous', 'Sebelumnya').replace('Next &raquo;', 'Berikutnya') }}</button></nav></template>
