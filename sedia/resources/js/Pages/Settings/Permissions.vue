<script setup lang="ts">
import { reactive, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
const page = usePage<any>();
const role = ref("staff");
const permissions = reactive<any>({ ...page.props.permissions });
const loading = ref(false);
const saving = ref(false);
const loadError = ref("");
let requestId = 0;
async function changeRole() {
    const selectedRole = role.value;
    const id = ++requestId;
    loading.value = true;
    loadError.value = "";
    try {
        const response = await fetch(
            `/app/settings/permissions/${selectedRole}`,
            { headers: { Accept: "application/json" } },
        );
        if (!response.ok) throw new Error("Hak akses gagal dimuat.");
        const data = await response.json();
        if (id !== requestId || selectedRole !== role.value) return;
        Object.keys(permissions).forEach((key) => delete permissions[key]);
        Object.assign(permissions, data.permissions);
    } catch (error) {
        if (id === requestId)
            loadError.value =
                error instanceof Error
                    ? error.message
                    : "Hak akses gagal dimuat.";
    } finally {
        if (id === requestId) loading.value = false;
    }
}
function save() {
    if (loading.value || saving.value) return;
    const selectedRole = role.value;
    saving.value = true;
    router.post(
        "/app/settings/permissions",
        {
            role: selectedRole,
            permissions: JSON.parse(JSON.stringify(permissions)),
        },
        {
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>
<template>
    <Head title="Hak Akses" /><AppLayout
        ><template #title>Hak Akses</template>
        <div class="mx-auto max-w-5xl space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-bold uppercase tracking-wider text-cyan-700"
                    >
                        Konfigurasi
                    </p>
                    <h2 class="mt-1 text-2xl font-semibold">Permission role</h2>
                </div>
                <select
                    v-model="role"
                    aria-label="Pilih role"
                    :disabled="loading || saving"
                    class="rounded-xl border-stone-200 disabled:opacity-50"
                    @change="changeRole"
                >
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div
                v-if="loadError"
                role="alert"
                class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
            >
                {{ loadError }}
            </div>
            <section
                class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm"
            >
                <div
                    v-if="loading"
                    aria-live="polite"
                    class="p-10 text-center text-sm text-stone-500"
                >
                    Memuat hak akses...
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b bg-stone-50 text-xs uppercase text-stone-500"
                        >
                            <tr>
                                <th class="px-5 py-4">Resource</th>
                                <th
                                    v-for="action in [
                                        'view',
                                        'create',
                                        'edit',
                                        'delete',
                                    ]"
                                    :key="action"
                                    class="px-5 py-4"
                                >
                                    {{ action }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="(permission, key) in permissions"
                                :key="key"
                            >
                                <td class="px-5 py-3 font-medium">
                                    {{ permission.label }}
                                </td>
                                <td
                                    v-for="action in [
                                        'view',
                                        'create',
                                        'edit',
                                        'delete',
                                    ]"
                                    :key="action"
                                    class="px-5 py-3"
                                >
                                    <input
                                        v-model="permission[action]"
                                        type="checkbox"
                                        :aria-label="`${permission.label}: ${action}`"
                                        class="rounded border-stone-300 text-cyan-500"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <button
                :disabled="loading || saving"
                class="rounded-xl bg-stone-950 px-4 py-3 font-semibold text-white disabled:opacity-50"
                @click="save"
            >
                {{ saving ? "Menyimpan..." : "Simpan hak akses" }}
            </button>
        </div></AppLayout
    >
</template>
