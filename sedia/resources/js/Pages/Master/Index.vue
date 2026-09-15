<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage<any>();
const resource = computed(() => page.props.resource);
const rows = computed(() => page.props.rows ?? []);
const editing = ref<any>(null);
const form = reactive<Record<string, any>>({});
const search = reactive({ value: page.props.search ?? '' });
const processing = ref(false);
const columns = computed(() => resource.value.fields.filter((field: string) => field !== 'password'));
const resourceKeys: Record<string, string> = { outlets: 'OutletResource', menus: 'MenuItemResource', ingredients: 'IngredientResource', employees: 'EmployeeResource', expenses: 'ExpenseResource', users: 'UserResource' };
const permission = computed(() => page.props.auth?.permissions?.[resourceKeys[resource.value.slug]] ?? {});
const can = (action: 'create' | 'edit' | 'delete') => Boolean(permission.value[`can_${action}`]);

function label(key: string) { return key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()); }
function clearForm() { editing.value = null; Object.keys(form).forEach((key) => delete form[key]); }
function openCreate() { clearForm(); resource.value.fields.forEach((field: string) => { form[field] = field === 'is_active' ? true : field === 'role' ? 'staff' : field === 'status' ? 'active' : ''; }); }
function edit(row: any) { clearForm(); editing.value = row; resource.value.fields.forEach((field: string) => { form[field] = row[field] ?? ''; }); }
function submit() { if (processing.value) return; processing.value = true; const url = editing.value ? `/app/master/${resource.value.slug}/${editing.value.id}` : `/app/master/${resource.value.slug}`; router[editing.value ? 'put' : 'post'](url, form, { onSuccess: clearForm, onFinish: () => { processing.value = false; } }); }
function find() { router.get(`/app/master/${resource.value.slug}`, { q: search.value }, { preserveState: true, replace: true }); }
function remove(row: any) { if (processing.value || !confirm(`Hapus ${row.name ?? 'data ini'}?`)) return; processing.value = true; router.delete(`/app/master/${resource.value.slug}/${row.id}`, { onFinish: () => { processing.value = false; } }); }
function resetPassword(row: any) { if (processing.value) return; const password = prompt(`Password baru untuk ${row.name} (minimal 8 karakter):`); if (!password) return; const confirmation = prompt('Ulangi password baru:'); if (password !== confirmation) { window.alert('Konfirmasi password tidak sama.'); return; } processing.value = true; router.post(`/app/master/users/${row.id}/reset-password`, { password, password_confirmation: confirmation }, { onFinish: () => { processing.value = false; } }); }
</script>

<template>
    <Head :title="resource.title" />
    <AppLayout>
        <template #title>{{ resource.title }}</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Master data</p><h2 class="mt-1 text-2xl font-semibold text-stone-950">{{ resource.title }}</h2></div><div class="flex flex-col gap-2 min-[420px]:flex-row"><input v-model="search.value" aria-label="Cari master data" class="min-w-0 rounded-xl border-stone-200 bg-white text-sm" placeholder="Cari..." @keyup.enter="find"><button type="button" class="rounded-xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white" @click="find">Cari</button><button v-if="can('create')" type="button" class="rounded-xl bg-lime-300 px-4 py-2.5 text-sm font-semibold text-stone-900" @click="openCreate">Tambah</button></div></div>
            <section v-if="editing || Object.keys(form).length" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"><form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                <label v-for="field in resource.fields" :key="field" class="text-sm text-stone-600" :class="{ 'sm:col-span-2': ['address', 'receipt_header', 'receipt_footer', 'description', 'note'].includes(field) }">{{ label(field) }}
                    <select v-if="field === 'outlet_id'" v-model="form[field]" class="mt-1 w-full rounded-xl border-stone-200"><option value="">Pilih outlet</option><option v-for="(name, id) in page.props.outlets" :key="id" :value="id">{{ name }}</option></select>
                    <select v-else-if="field === 'user_id'" v-model="form[field]" class="mt-1 w-full rounded-xl border-stone-200"><option value="">Tanpa akun</option><option v-for="userOption in page.props.users" :key="userOption.id" :value="userOption.id">{{ userOption.name }} · {{ userOption.email }}</option></select>
                    <select v-else-if="field === 'role'" v-model="form[field]" class="mt-1 w-full rounded-xl border-stone-200"><option value="admin">Admin</option><option value="staff">Staff</option></select>
                    <select v-else-if="field === 'status'" v-model="form[field]" class="mt-1 w-full rounded-xl border-stone-200"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    <input v-else-if="field === 'is_active'" v-model="form[field]" type="checkbox" class="ml-3 rounded border-stone-300 text-amber-500">
                    <textarea v-else-if="['address', 'receipt_header', 'receipt_footer', 'description', 'note'].includes(field)" v-model="form[field]" class="mt-1 w-full rounded-xl border-stone-200" rows="2"></textarea>
                    <input v-else v-model="form[field]" :type="field.includes('date') ? 'date' : field.includes('password') ? 'password' : ['price', 'amount', 'salary', 'stock'].some((word) => field.includes(word)) ? 'number' : field === 'email' ? 'email' : 'text'" class="mt-1 w-full rounded-xl border-stone-200">
                </label>
                <div class="flex gap-2 sm:col-span-2"><button :disabled="processing" class="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-stone-950 disabled:opacity-50">{{ processing ? 'Menyimpan...' : 'Simpan' }}</button><button type="button" :disabled="processing" class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm disabled:opacity-50" @click="clearForm">Batal</button></div>
            </form></section>
                <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th v-for="column in columns" :key="column" class="px-5 py-4">{{ label(column) }}</th><th v-if="can('edit') || can('delete')" class="px-5 py-4">Aksi</th></tr></thead><tbody class="divide-y divide-stone-100"><tr v-for="row in rows" :key="row.id"><td v-for="column in columns" :key="column" class="px-5 py-4 text-stone-700">{{ row[column] ?? row[column.replace('_id', '_name')] ?? '-' }}</td><td v-if="can('edit') || can('delete')" class="space-x-3 whitespace-nowrap px-5 py-4"><button v-if="can('edit')" :disabled="processing" class="text-amber-700 disabled:opacity-40" @click="edit(row)">Edit</button><a v-if="resource.slug === 'menus' && can('edit')" class="text-cyan-700" :href="`/app/master/menus/${row.id}/recipes`">Resep</a><button v-if="resource.slug === 'users' && can('edit')" :disabled="processing" class="text-cyan-700 disabled:opacity-40" @click="resetPassword(row)">Reset password</button><button v-if="can('delete')" :disabled="processing" class="text-red-600 disabled:opacity-40" @click="remove(row)">Hapus</button></td></tr><tr v-if="!rows.length"><td :colspan="columns.length + (can('edit') || can('delete') ? 1 : 0)" class="p-10 text-center text-stone-500">Belum ada data.</td></tr></tbody></table></div></section>
        </div>
    </AppLayout>
</template>
