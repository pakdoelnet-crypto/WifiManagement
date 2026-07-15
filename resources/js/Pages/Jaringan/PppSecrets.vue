<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    routers: {
        type: Array,
        required: true,
    },
    selectedRouterId: {
        type: Number,
        required: true,
    },
    secrets: {
        type: Array,
        default: () => [],
    },
    profiles: {
        type: Array,
        default: () => [],
    },
    routerStatus: {
        type: String,
        default: 'offline',
    },
    canManage: {
        type: Boolean,
        default: false,
    }
});

const currentRouterId = ref(props.selectedRouterId);
const searchQuery = ref('');
const showPasswordMap = ref({});

// Change router handler
const onRouterChange = () => {
    router.get(route('ppp-secrets.index'), { router_id: currentRouterId.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Filtered Secrets list
const filteredSecrets = computed(() => {
    if (!searchQuery.value) return props.secrets;
    const query = searchQuery.value.toLowerCase();
    return props.secrets.filter(secret => 
        (secret.name && secret.name.toLowerCase().includes(query)) ||
        (secret.profile && secret.profile.toLowerCase().includes(query)) ||
        (secret.comment && secret.comment.toLowerCase().includes(query))
    );
});

// Password visibility toggle helper
const togglePasswordVisibility = (secretName) => {
    showPasswordMap.value[secretName] = !showPasswordMap.value[secretName];
};

// Modal: Create PPPoE Secret
const showCreateModal = ref(false);
const createForm = useForm({
    router_id: props.selectedRouterId,
    name: '',
    password: '',
    profile: '',
    comment: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.router_id = currentRouterId.value;
    // Default to the first available profile if any
    if (props.profiles.length > 0) {
        createForm.profile = props.profiles[0].name;
    } else {
        createForm.profile = 'default';
    }
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitCreateForm = () => {
    createForm.post(route('ppp-secrets.store'), {
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

// Toggle Secret Active State (Enable/Disable)
const isToggling = ref(null);
const toggleSecretStatus = (secret) => {
    if (!props.canManage) return;
    
    isToggling.value = secret.id;
    router.post(route('ppp-secrets.toggle'), {
        router_id: currentRouterId.value,
        secret_id: secret.id,
        name: secret.name,
        disabled: !secret.disabled, // if currently disabled, new disabled value is true (so disabled: true to disable, false to enable)
    }, {
        preserveScroll: true,
        onFinish: () => {
            isToggling.value = null;
        }
    });
};

// Modal: Delete PPPoE Secret
const showDeleteModal = ref(false);
const selectedSecretToDelete = ref(null);

const confirmDeleteSecret = (secret) => {
    selectedSecretToDelete.value = secret;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedSecretToDelete.value = null;
};

const deleteForm = useForm({});
const executeDeleteSecret = () => {
    if (!selectedSecretToDelete.value) return;

    router.delete(route('ppp-secrets.destroy'), {
        data: {
            router_id: currentRouterId.value,
            secret_id: selectedSecretToDelete.value.id,
            name: selectedSecretToDelete.value.name,
        },
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        }
    });
};
</script>

<template>
    <Head title="Secret PPPoE" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                Manajemen Secret PPPoE (MikroTik)
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Filter & Action Controls card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-150 dark:border-gray-700/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                
                <!-- Router Selector Dropdown -->
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Pilih Router:</label>
                    <select
                        v-model="currentRouterId"
                        @change="onRouterChange"
                        class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-xl px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                    >
                        <option v-for="r in routers" :key="r.id" :value="r.id">
                            {{ r.name }} ({{ r.host }})
                        </option>
                        <option v-if="routers.length === 0" disabled>Belum ada router aktif</option>
                    </select>

                    <!-- Status indicator -->
                    <span
                        :class="[
                            routerStatus === 'online'
                                ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50'
                                : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200/50'
                        ]"
                        class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5"
                    >
                        <span v-if="routerStatus === 'online'" class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                        Router {{ routerStatus }}
                    </span>
                </div>

                <!-- Right search and add trigger -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search secret -->
                    <div class="relative min-w-[200px]">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari Username/Profil..."
                            class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3.5 py-2 pl-9 text-sm text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Add button (Super Admin, Owner, Admin, Teknisi only) -->
                    <button
                        v-if="canManage && routerStatus === 'online'"
                        @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md shadow-indigo-600/10 transition"
                    >
                        <svg class="h-4.5 w-4.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Secret PPPoE
                    </button>
                </div>
            </div>

            <!-- Secrets Table Grid card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-150 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-150 dark:border-gray-700/50">
                                <th class="px-6 py-4">Username (Name)</th>
                                <th class="px-6 py-4">Password</th>
                                <th class="px-6 py-4">Profile Bandwidth</th>
                                <th class="px-6 py-4">Service</th>
                                <th class="px-6 py-4">Comment / Keterangan</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-750 text-gray-700 dark:text-gray-300">
                            <tr v-for="secret in filteredSecrets" :key="secret.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/10 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white font-mono text-xs">
                                    {{ secret.name }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    <div class="flex items-center gap-2">
                                        <span>
                                            {{ showPasswordMap[secret.name] ? secret.password : '••••••••' }}
                                        </span>
                                        <button
                                            @click="togglePasswordVisibility(secret.name)"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition focus:outline-none"
                                            title="Tampilkan / Sembunyikan Password"
                                        >
                                            <svg v-if="showPasswordMap[secret.name]" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-xs font-bold">
                                        {{ secret.profile }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs uppercase text-gray-500">
                                    {{ secret.service || 'pppoe' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ secret.comment || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <!-- Enable/Disable Switch -->
                                    <button
                                        @click="toggleSecretStatus(secret)"
                                        :disabled="!canManage || isToggling === secret.id"
                                        class="focus:outline-none disabled:opacity-50 transition"
                                    >
                                        <span
                                            :class="[
                                                !secret.disabled
                                                    ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border-emerald-200'
                                                    : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-400 border-rose-200'
                                            ]"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border inline-flex items-center gap-1"
                                        >
                                            <span v-if="!secret.disabled" class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                            {{ !secret.disabled ? 'Active' : 'Disabled' }}
                                        </span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <!-- Delete action -->
                                    <button
                                        v-if="canManage"
                                        @click="confirmDeleteSecret(secret)"
                                        class="p-1.5 bg-rose-50 dark:bg-rose-950/20 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-950/40 rounded-xl transition"
                                        title="Hapus Secret PPPoE"
                                    >
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredSecrets.length === 0">
                                <td colspan="7" class="text-center py-12 text-sm text-gray-400 dark:text-gray-500">
                                    {{ routerStatus === 'online' ? 'Tidak ada secret PPPoE yang ditemukan.' : 'Router sedang offline. Tidak dapat membaca secret.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- MODAL: ADD NEW PPPOE SECRET -->
        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="md">
            <div class="p-6 text-gray-800 dark:text-gray-200">
                <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                    Tambah Secret PPPoE Baru
                </h3>
                
                <form @submit.prevent="submitCreateForm" class="space-y-4">
                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Username (Name)</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="Contoh: pppoe-budi"
                            class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="createForm.errors.name" class="text-xs text-rose-500 block mt-1">{{ createForm.errors.name }}</span>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
                        <input
                            v-model="createForm.password"
                            type="text"
                            required
                            placeholder="Masukkan password rahasia..."
                            class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="createForm.errors.password" class="text-xs text-rose-500 block mt-1">{{ createForm.errors.password }}</span>
                    </div>

                    <!-- Profile selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Profile Bandwidth</label>
                        <select
                            v-model="createForm.profile"
                            required
                            class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                        >
                            <option v-for="prof in profiles" :key="prof.name" :value="prof.name">
                                {{ prof.name }} ({{ prof.rate_limit || 'No Limit' }})
                            </option>
                            <option v-if="profiles.length === 0" value="default">default (No Limit)</option>
                        </select>
                        <span v-if="createForm.errors.profile" class="text-xs text-rose-500 block mt-1">{{ createForm.errors.profile }}</span>
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Keterangan / Comment (Opsional)</label>
                        <input
                            v-model="createForm.comment"
                            type="text"
                            placeholder="Contoh: Budi - RT 02 RW 03"
                            class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="createForm.errors.comment" class="text-xs text-rose-500 block mt-1">{{ createForm.errors.comment }}</span>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-bold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md shadow-indigo-600/10 transition disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Secret' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: CONFIRM DELETE -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="sm">
            <div class="p-6 text-gray-800 dark:text-gray-200">
                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/20 text-rose-600 mb-4 mx-auto">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <h3 class="text-md font-bold text-center mb-2">Hapus Secret PPPoE?</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-6">
                    Apakah Anda yakin ingin menghapus secret <span class="font-bold text-gray-700 dark:text-gray-200 font-mono">{{ selectedSecretToDelete?.name }}</span> dari MikroTik? Akun pelanggan ini tidak akan dapat terhubung lagi. Action ini tidak dapat dibatalkan.
                </p>
                
                <div class="flex items-center justify-center gap-3">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteSecret"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/10 transition"
                    >
                        Ya, Hapus Permanen
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
