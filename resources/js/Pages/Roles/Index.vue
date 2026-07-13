<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    allPermissions: {
        type: Object,
        required: true,
    },
});

// Modal States
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentRoleId = ref(null);

const form = useForm({
    name: '',
    permissions: [],
});

// Accordion Collapsed State
const collapsedSections = ref({
    routers: true,
    packages: true,
    customers: true,
    online_sessions: true,
    network_map: true,
    invoices: true,
    tickets: true,
    roles: true,
});

const toggleSection = (section) => {
    collapsedSections.value[section] = !collapsedSections.value[section];
};

const openAddModal = () => {
    isEditing.value = false;
    currentRoleId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = async (role) => {
    if (role.name === 'Super Admin') return;
    
    isEditing.value = true;
    currentRoleId.value = role.id;
    form.reset();
    form.name = role.name;
    
    try {
        const response = await axios.get(route('roles.permissions', role.id));
        form.permissions = response.data;
    } catch (e) {
        console.error('Gagal mengambil permission role:', e);
    }
    
    isModalOpen.value = true;
};

const togglePermission = (permissionName) => {
    const index = form.permissions.indexOf(permissionName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionName);
    }
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('roles.update', currentRoleId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteRole = (role) => {
    if (role.name === 'Super Admin') return;
    
    if (confirm(`Apakah Anda yakin ingin menghapus role "${role.name}"?`)) {
        router.delete(route('roles.destroy', role.id));
    }
};

// Pretty label mapping for modules
const moduleLabelMap = {
    routers: 'Manajemen Router MikroTik',
    packages: 'Paket & Bandwidth Profil',
    customers: 'Manajemen Data Pelanggan',
    online_sessions: 'Monitoring Sesi Online',
    network_map: 'Peta Infrastruktur & Kabel',
    invoices: 'Billing & Tagihan Keuangan',
    tickets: 'Ticketing Pengaduan Gangguan',
    roles: 'Otoritas & Hak Akses User',
};
</script>

<template>
    <Head title="Manajemen Role & Permissions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Role & Hak Akses
                </h2>
                <button
                    @click="openAddModal"
                    class="self-start sm:self-center px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition"
                >
                    + Tambah Role Baru
                </button>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Roles Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-150 dark:border-gray-700/50">
                                <th class="px-6 py-4">Nama Role</th>
                                <th class="px-6 py-4">Pengguna Aktif</th>
                                <th class="px-6 py-4">Hak Akses Aktif</th>
                                <th class="px-6 py-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-700/50 text-sm">
                            <tr
                                v-for="role in roles"
                                :key="role.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300 transition"
                            >
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    {{ role.name }}
                                    <span
                                        v-if="role.name === 'Super Admin'"
                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-400 uppercase tracking-wider"
                                    >
                                        Sistem Utama
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ role.users_count }}</span> User
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ role.permissions_count }}</span> Permission
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button
                                        v-if="role.name !== 'Super Admin'"
                                        @click="openEditModal(role)"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium transition"
                                    >
                                        Edit Hak Akses
                                    </button>
                                    <button
                                        v-if="role.name !== 'Super Admin'"
                                        @click="deleteRole(role)"
                                        class="inline-flex items-center px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-lg text-xs font-medium transition"
                                    >
                                        Hapus
                                    </button>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic pr-4">Akses Penuh Permanen</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="3xl">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                        {{ isEditing ? 'Edit Hak Akses Role' : 'Tambah Role & Hak Akses Baru' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nama Role" class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                :disabled="isEditing && form.name === 'Super Admin'"
                                placeholder="Misal: Teknisi Lapangan, Kasir Loket"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Accordion Permissions Section -->
                        <div>
                            <InputLabel value="Daftar Hak Akses (Berdasarkan Modul)" class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3" />
                            
                            <div class="space-y-3">
                                <div
                                    v-for="(perms, moduleName) in allPermissions"
                                    :key="moduleName"
                                    class="border border-gray-150 dark:border-gray-700/50 rounded-xl overflow-hidden"
                                >
                                    <!-- Accordion Header -->
                                    <div
                                        @click="toggleSection(moduleName)"
                                        class="bg-gray-50 dark:bg-gray-900/40 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900/60 transition"
                                    >
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ moduleLabelMap[moduleName] || moduleName.toUpperCase() }}
                                        </span>
                                        <svg
                                            :class="[collapsedSections[moduleName] ? '' : 'rotate-180']"
                                            class="h-4 w-4 text-gray-400 transition-transform"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Accordion Content -->
                                    <div
                                        v-show="!collapsedSections[moduleName]"
                                        class="p-4 bg-white dark:bg-gray-800 grid grid-cols-1 md:grid-cols-2 gap-3"
                                    >
                                        <label
                                            v-for="perm in perms"
                                            :key="perm.id"
                                            class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/20 cursor-pointer text-xs"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="form.permissions.includes(perm.name)"
                                                @change="togglePermission(perm.name)"
                                                class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 mt-0.5"
                                            />
                                            <div>
                                                <div class="font-semibold text-gray-800 dark:text-gray-200 font-mono">{{ perm.name }}</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-150 dark:border-gray-700/50">
                            <SecondaryButton @click="isModalOpen = false">Batal</SecondaryButton>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEditing ? 'Simpan Perubahan' : 'Buat Role Baru' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
