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
    routers: Array,
    canManage: Boolean,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRouterId = ref(null);

const form = useForm({
    name: '',
    host: '',
    port: 8729,
    username: '',
    password: '',
    connection_type: 'api_ssl',
    is_active: true,
});

const openAddModal = () => {
    isEditing.value = false;
    editingRouterId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (routerItem) => {
    isEditing.value = true;
    editingRouterId.value = routerItem.id;
    form.clearErrors();
    form.name = routerItem.name;
    form.host = routerItem.host;
    form.port = routerItem.port;
    form.username = routerItem.username;
    form.password = ''; // empty by default
    form.connection_type = routerItem.connection_type;
    form.is_active = routerItem.is_active;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('routers.update', editingRouterId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('routers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteRouter = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus router ini?')) {
        router.delete(route('routers.destroy', id));
    }
};

// Connection Test states
const testResult = ref(null);
const testingRouterId = ref(null);
const isTesting = ref(false);

const runTestConnection = async (id) => {
    isTesting.value = true;
    testingRouterId.value = id;
    testResult.value = null;

    try {
        const response = await axios.post(route('routers.test-connection', id));
        testResult.value = {
            success: true,
            routerId: id,
            message: response.data.message,
            data: response.data.data
        };
        router.reload({ only: ['routers'] });
    } catch (error) {
        testResult.value = {
            success: false,
            routerId: id,
            message: error.response?.data?.message || 'Gagal terhubung ke router.'
        };
        router.reload({ only: ['routers'] });
    } finally {
        isTesting.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>

<template>
    <Head title="Manajemen Router" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Router
                </h2>
                <button
                    v-if="canManage"
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Router
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Test Result Notification Panel -->
                <div v-if="testResult" :class="[
                    'p-6 rounded-xl border shadow-sm transition-all duration-300',
                    testResult.success 
                        ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900/50' 
                        : 'bg-rose-50 border-rose-200 dark:bg-rose-950/20 dark:border-rose-900/50'
                ]">
                    <div class="flex justify-between items-start">
                        <div class="flex gap-3">
                            <div :class="[
                                'p-2 rounded-lg',
                                testResult.success ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400'
                            ]">
                                <svg v-if="testResult.success" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ testResult.success ? 'Koneksi Berhasil' : 'Koneksi Gagal' }}
                                </h3>
                                <p class="text-sm mt-0.5 text-gray-600 dark:text-gray-400">
                                    {{ testResult.message }}
                                </p>
                                
                                <!-- Resource metrics details -->
                                <div v-if="testResult.success && testResult.data" class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4 bg-white dark:bg-gray-900/60 p-4 rounded-lg border border-gray-100 dark:border-gray-800">
                                    <div>
                                        <span class="text-xs text-gray-400 block">Board Name</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ testResult.data.boardName }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Version</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">v{{ testResult.data.version }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">CPU Load</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ testResult.data.cpuLoad }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Uptime</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ testResult.data.uptime }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Free Memory</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ testResult.data.freeMemory }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Total Memory</span>
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ testResult.data.totalMemory }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="testResult = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Main Router List Card -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 dark:bg-gray-900/40 dark:border-gray-700">
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Router</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Host / IP Address</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Port</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe Koneksi</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Terakhir Dicek</th>
                                    <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aktif</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-right tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-if="routers.length === 0">
                                    <td colspan="8" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada data router. Klik "Tambah Router" untuk menambahkan.
                                    </td>
                                </tr>
                                <tr v-for="routerItem in routers" :key="routerItem.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ routerItem.name }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-600 dark:text-gray-350 font-mono">
                                        {{ routerItem.host }}
                                    </td>
                                    <td class="p-4 text-sm text-gray-600 dark:text-gray-350 font-mono">
                                        {{ routerItem.port }}
                                    </td>
                                    <td class="p-4 text-sm">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-350">
                                            {{ routerItem.connection_type === 'api_ssl' ? 'API SSL' : 'API' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <span v-if="routerItem.status === 'online'" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Online
                                        </span>
                                        <span v-else-if="routerItem.status === 'offline'" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Offline
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                            Unknown
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(routerItem.last_checked_at) }}
                                    </td>
                                    <td class="p-4 text-sm">
                                        <span v-if="routerItem.is_active" class="text-emerald-500">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span v-else class="text-gray-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-right space-x-2">
                                        <button
                                            @click="runTestConnection(routerItem.id)"
                                            :disabled="isTesting"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-medium transition disabled:opacity-50"
                                        >
                                            <svg v-if="isTesting && testingRouterId === routerItem.id" class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Test
                                        </button>
                                        
                                        <button
                                            v-if="canManage"
                                            @click="openEditModal(routerItem)"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium transition"
                                        >
                                            Edit
                                        </button>
                                        
                                        <button
                                            v-if="canManage"
                                            @click="deleteRouter(routerItem.id)"
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
                    {{ isEditing ? 'Edit Router' : 'Tambah Router Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Router" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Contoh: Router Utama"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="host" value="Host / IP Address" />
                            <TextInput
                                id="host"
                                v-model="form.host"
                                type="text"
                                class="mt-1 block w-full font-mono"
                                placeholder="Contoh: 192.168.1.1 atau id.tunnel.net"
                                required
                            />
                            <InputError :message="form.errors.host" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="port" value="Port API" />
                            <TextInput
                                id="port"
                                v-model="form.port"
                                type="number"
                                class="mt-1 block w-full font-mono"
                                placeholder="Contoh: 8729 atau 8728"
                                required
                            />
                            <InputError :message="form.errors.port" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="username" value="Username Router" />
                            <TextInput
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="admin"
                                required
                            />
                            <InputError :message="form.errors.username" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password" :value="isEditing ? 'Password (Kosongkan jika tidak diubah)' : 'Password Router'" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                placeholder="••••••••"
                                :required="!isEditing"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="connection_type" value="Tipe Koneksi" />
                        <select
                            id="connection_type"
                            v-model="form.connection_type"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="api">API biasa (Port 8728)</option>
                            <option value="api_ssl">API SSL (Port 8729 - Rekomendasi)</option>
                        </select>
                        <InputError :message="form.errors.connection_type" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Aktifkan router ini
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" :disabled="form.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Router' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
