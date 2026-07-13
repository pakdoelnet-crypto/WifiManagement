<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    sessions: Array,
    canManage: Boolean,
});

const activeSessions = ref(props.sessions);
const isDisconnectModalOpen = ref(false);
const selectedSession = ref(null);
const isProcessing = ref(false);

onMounted(() => {
    // Connect to Reverb public channel
    window.Echo.channel('ppp-sessions')
        .listen('.PppActiveSessionsUpdated', (e) => {
            activeSessions.value = e.sessions;
        });
});

onUnmounted(() => {
    // Disconnect from channel
    window.Echo.leaveChannel('ppp-sessions');
});

const confirmDisconnect = (session) => {
    selectedSession.value = session;
    isDisconnectModalOpen.value = true;
};

const disconnectSession = () => {
    if (!selectedSession.value) return;
    isProcessing.value = true;
    
    router.post(route('online-customers.disconnect'), {
        id: selectedSession.value.id
    }, {
        onFinish: () => {
            isProcessing.value = false;
            isDisconnectModalOpen.value = false;
            selectedSession.value = null;
        }
    });
};
</script>

<template>
    <Head title="Pelanggan Online" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Pelanggan Online (PPPoE)
                </h2>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400">
                        <span class="mr-1.5 h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Real-time Monitoring
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Overview -->
                <div class="mb-6 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-400 font-medium">Pelanggan Aktif Online</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ activeSessions.length }} <span class="text-sm font-normal text-gray-400">pelanggan online saat ini</span>
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Online Sessions Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 dark:bg-gray-900/40 dark:border-gray-700">
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nama Pelanggan</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">PPPoE Username</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">IP Address</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">MAC / Caller ID</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Uptime</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Router</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-right text-gray-500 dark:text-gray-400" v-if="canManage">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-if="activeSessions.length === 0">
                                    <td colspan="7" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada pelanggan online saat ini.
                                    </td>
                                </tr>
                                <tr
                                    v-for="session in activeSessions"
                                    :key="session.id"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                                >
                                    <!-- Pelanggan Details -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-slate-150 dark:bg-slate-700 flex items-center justify-center overflow-hidden">
                                                <img
                                                    v-if="session.customer?.photo_path"
                                                    :src="route('customers.media', { type: 'photo', filename: session.customer.photo_path.split('/').pop() })"
                                                    class="h-full w-full object-cover"
                                                />
                                                <svg v-else class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ session.customer?.name || 'User MikroTik' }}
                                                </div>
                                                <div class="text-[10px] text-indigo-500 font-semibold" v-if="session.customer?.package">
                                                    {{ session.customer.package.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- PPPoE Username -->
                                    <td class="p-4 text-sm font-semibold font-mono text-gray-800 dark:text-gray-300">
                                        {{ session.pppoe_username }}
                                    </td>

                                    <!-- IP Address -->
                                    <td class="p-4 text-sm font-mono text-gray-700 dark:text-gray-350">
                                        {{ session.ip_address }}
                                    </td>

                                    <!-- MAC / Caller ID -->
                                    <td class="p-4 text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ session.caller_id || 'N/A' }}
                                    </td>

                                    <!-- Uptime -->
                                    <td class="p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 font-mono">
                                        {{ session.uptime }}
                                    </td>

                                    <!-- Router Name -->
                                    <td class="p-4 text-sm text-gray-700 dark:text-gray-350 font-medium">
                                        {{ session.router?.name || 'N/A' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-sm text-right" v-if="canManage">
                                        <button
                                            @click="confirmDisconnect(session)"
                                            class="inline-flex items-center px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-lg text-xs font-semibold transition"
                                        >
                                            Putuskan
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disconnect Session Confirmation Modal -->
        <Modal :show="isDisconnectModalOpen" @close="isDisconnectModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Putuskan Sesi Aktif
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Apakah Anda yakin ingin memutuskan sesi PPPoE aktif untuk pelanggan <span class="font-bold text-gray-800 dark:text-gray-200">{{ selectedSession?.pppoe_username }}</span>? Pelanggan akan mengalami disonect sementara dan akan dial up ulang.
                </p>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="isDisconnectModalOpen = false" :disabled="isProcessing">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="disconnectSession" :disabled="isProcessing">
                        Ya, Putuskan Koneksi
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
