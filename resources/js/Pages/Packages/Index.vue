<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import axios from 'axios';

const props = defineProps({
    packages: Array,
    routers: Array,
    canManage: Boolean,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingPackageId = ref(null);

const form = useForm({
    name: '',
    upload_limit: '', // in Mbps in UI
    download_limit: '', // in Mbps in UI
    price: '',
    mikrotik_profile: '',
    is_active: true,
});

// For Mikrotik Syncing
const selectedRouterId = ref('');
const syncedProfiles = ref([]);
const isSyncing = ref(false);
const syncError = ref('');

const fetchMikrotikProfiles = async () => {
    if (!selectedRouterId.value) return;
    isSyncing.value = true;
    syncError.value = '';
    syncedProfiles.value = [];

    try {
        const response = await axios.get(route('packages.sync-profiles', selectedRouterId.value));
        syncedProfiles.value = response.data.profiles;
    } catch (error) {
        syncError.value = error.response?.data?.message || 'Gagal mengambil profil dari router.';
    } finally {
        isSyncing.value = false;
    }
};

const onProfileSelect = (event) => {
    const profileName = event.target.value;
    form.mikrotik_profile = profileName;
    
    // Autofill upload/download speed based on profile rate-limit
    const selectedProfile = syncedProfiles.value.find(p => p.name === profileName);
    if (selectedProfile && selectedProfile.rate_limit && selectedProfile.rate_limit !== 'No Limit') {
        const limits = selectedProfile.rate_limit.split('/');
        if (limits.length === 2) {
            const up = limits[0].toLowerCase();
            const down = limits[1].toLowerCase();
            
            if (up.includes('m')) form.upload_limit = parseInt(up);
            else if (up.includes('k')) form.upload_limit = parseFloat((parseInt(up) / 1024).toFixed(1));
            
            if (down.includes('m')) form.download_limit = parseInt(down);
            else if (down.includes('k')) form.download_limit = parseFloat((parseInt(down) / 1024).toFixed(1));
        }
    }
};

const openAddModal = () => {
    isEditing.value = false;
    editingPackageId.value = null;
    form.reset();
    form.clearErrors();
    syncedProfiles.value = [];
    syncError.value = '';
    
    if (props.routers.length > 0) {
        selectedRouterId.value = props.routers[0].id;
        fetchMikrotikProfiles();
    } else {
        selectedRouterId.value = '';
    }
    isModalOpen.value = true;
};

const openEditModal = (packageItem) => {
    isEditing.value = true;
    editingPackageId.value = packageItem.id;
    form.clearErrors();
    form.name = packageItem.name;
    form.upload_limit = packageItem.upload_limit / 1024;
    form.download_limit = packageItem.download_limit / 1024;
    form.price = packageItem.price;
    form.mikrotik_profile = packageItem.mikrotik_profile;
    form.is_active = packageItem.is_active;
    
    syncedProfiles.value = [];
    syncError.value = '';
    
    if (props.routers.length > 0) {
        selectedRouterId.value = props.routers[0].id;
        fetchMikrotikProfiles().then(() => {
            // Retain old profile selection
            form.mikrotik_profile = packageItem.mikrotik_profile;
        });
    } else {
        selectedRouterId.value = '';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    form.transform((data) => ({
        ...data,
        upload_limit: Math.round(data.upload_limit * 1024),
        download_limit: Math.round(data.download_limit * 1024),
    }));

    if (isEditing.value) {
        form.put(route('packages.update', editingPackageId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('packages.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePackage = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket ini?')) {
        router.delete(route('packages.destroy', id));
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};
</script>

<template>
    <Head title="Manajemen Paket Bandwidth" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Paket Bandwidth
                </h2>
                <button
                    v-if="canManage"
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Paket
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Packages List Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 dark:bg-gray-900/40 dark:border-gray-700">
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Paket</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Upload Limit</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Download Limit</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Profil MikroTik</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Harga</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-right tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-if="packages.length === 0">
                                    <td colspan="7" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada data paket internet. Klik "Tambah Paket" untuk menambahkan.
                                    </td>
                                </tr>
                                <tr v-for="packageItem in packages" :key="packageItem.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ packageItem.name }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-600 dark:text-gray-350">
                                        {{ packageItem.upload_limit >= 1024 ? (packageItem.upload_limit / 1024) + ' Mbps' : packageItem.upload_limit + ' Kbps' }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-600 dark:text-gray-350">
                                        {{ packageItem.download_limit >= 1024 ? (packageItem.download_limit / 1024) + ' Mbps' : packageItem.download_limit + ' Kbps' }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-600 dark:text-gray-350 font-mono">
                                        {{ packageItem.mikrotik_profile }}
                                    </td>
                                    <td class="p-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatPrice(packageItem.price) }}
                                    </td>
                                    <td class="p-4 text-sm">
                                        <span v-if="packageItem.is_active" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400">
                                            Aktif
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                            Nonaktif
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-right space-x-2">
                                        <button
                                            v-if="canManage"
                                            @click="openEditModal(packageItem)"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium transition"
                                        >
                                            Edit
                                        </button>
                                        
                                        <button
                                            v-if="canManage"
                                            @click="deletePackage(packageItem.id)"
                                            class="inline-flex items-center px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-lg text-xs font-medium transition"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    {{ isEditing ? 'Edit Paket Bandwidth' : 'Tambah Paket Bandwidth Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Router Selection -->
                    <div>
                        <InputLabel for="router_id" value="Pilih Router MikroTik (Untuk Mengambil Profil)" />
                        <select
                            id="router_id"
                            v-model="selectedRouterId"
                            @change="fetchMikrotikProfiles"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="">-- Pilih Router --</option>
                            <option v-for="routerItem in routers" :key="routerItem.id" :value="routerItem.id">
                                {{ routerItem.name }} ({{ routerItem.host }})
                            </option>
                        </select>
                    </div>

                    <!-- Profil select dropdown -->
                    <div>
                        <InputLabel for="mikrotik_profile" value="Profil MikroTik" />
                        <div class="flex gap-2">
                            <select
                                id="mikrotik_profile"
                                v-model="form.mikrotik_profile"
                                @change="onProfileSelect"
                                :disabled="isSyncing || syncedProfiles.length === 0"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="">-- Pilih Profil --</option>
                                <option v-for="profile in syncedProfiles" :key="profile.name" :value="profile.name">
                                    {{ profile.name }} (Rate-Limit: {{ profile.rate_limit }})
                                </option>
                            </select>
                            <button
                                type="button"
                                @click="fetchMikrotikProfiles"
                                :disabled="isSyncing || !selectedRouterId"
                                class="mt-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-755 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md text-sm font-medium transition disabled:opacity-50"
                            >
                                Sync Ulang
                            </button>
                        </div>
                        <p v-if="isSyncing" class="text-xs text-indigo-500 mt-1">Mengambil profil dari router...</p>
                        <p v-if="syncError" class="text-xs text-rose-500 mt-1">
                            Gagal mengambil profil dari router.
                            <button type="button" @click="fetchMikrotikProfiles" class="underline text-indigo-500 ml-1 font-semibold">Coba Lagi</button>
                        </p>
                    </div>

                    <!-- Custom Name -->
                    <div>
                        <InputLabel for="name" value="Nama Paket Internet" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Contoh: Paket Family 20 Mbps"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="upload_limit" value="Upload Speed (Mbps)" />
                            <TextInput
                                id="upload_limit"
                                v-model="form.upload_limit"
                                type="number"
                                step="0.1"
                                class="mt-1 block w-full font-mono"
                                placeholder="Contoh: 10"
                                required
                            />
                            <InputError :message="form.errors.upload_limit" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="download_limit" value="Download Speed (Mbps)" />
                            <TextInput
                                id="download_limit"
                                v-model="form.download_limit"
                                type="number"
                                step="0.1"
                                class="mt-1 block w-full font-mono"
                                placeholder="Contoh: 20"
                                required
                            />
                            <InputError :message="form.errors.download_limit" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="price" value="Harga Paket Bulanan (Rp)" />
                        <TextInput
                            id="price"
                            v-model="form.price"
                            type="number"
                            class="mt-1 block w-full font-mono"
                            placeholder="Contoh: 200000"
                            required
                        />
                        <InputError :message="form.errors.price" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Aktifkan paket ini
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" :disabled="form.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Paket' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
