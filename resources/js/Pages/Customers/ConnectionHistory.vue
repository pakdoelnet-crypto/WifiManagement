<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};
const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    routers: {
        type: Array,
        required: true,
    },
    customers: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const routerId = ref(props.filters.router_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const customerId = ref(props.filters.customer_id || '');

const formatDateTime = (value) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(value));
};

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) return 'Masih terhubung';
    
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    
    if (hrs > 0) {
        return `${hrs} jam ${mins} menit`;
    }
    return `${mins} menit`;
};

const applyFilters = debounce(() => {
    router.get(
        route('connection-history.index'),
        {
            search: search.value,
            router_id: routerId.value,
            start_date: startDate.value,
            end_date: endDate.value,
            customer_id: customerId.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}, 300);

watch([search, routerId, startDate, endDate, customerId], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    routerId.value = '';
    startDate.value = '';
    endDate.value = '';
    customerId.value = '';
};
</script>

<template>
    <Head title="Riwayat Koneksi Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Riwayat Koneksi Pelanggan (PPPoE)
                </h2>
                <button
                    @click="clearFilters"
                    class="self-start sm:self-center px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-xl shadow-sm transition"
                >
                    Reset Filter
                </button>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Filters Panel -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/50 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search input -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Cari Pelanggan/Username</label>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Cari..."
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    />
                </div>

                <!-- Customer selection -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Filter Pelanggan</label>
                    <select
                        v-model="customerId"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    >
                        <option value="">Semua Pelanggan</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">
                            {{ c.name }} ({{ c.pppoe_username }})
                        </option>
                    </select>
                </div>

                <!-- Router select -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Filter Router</label>
                    <select
                        v-model="routerId"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    >
                        <option value="">Semua Router</option>
                        <option v-for="r in routers" :key="r.id" :value="r.id">
                            {{ r.name }}
                        </option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Mulai</label>
                    <input
                        type="date"
                        v-model="startDate"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    />
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Selesai</label>
                    <input
                        type="date"
                        v-model="endDate"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    />
                </div>
            </div>

            <!-- Connection Logs Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-150 dark:border-gray-700/50">
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">PPPoE Username</th>
                                <th class="px-6 py-4">IP Address</th>
                                <th class="px-6 py-4">Router</th>
                                <th class="px-6 py-4">Waktu Terkoneksi</th>
                                <th class="px-6 py-4">Waktu Terputus</th>
                                <th class="px-6 py-4 text-right">Durasi Sesi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-700/50 text-sm">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300 transition"
                            >
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ log.customer ? log.customer.name : 'Pelanggan Tidak Terdaftar' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ log.pppoe_username }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ log.ip_address || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ log.router ? log.router.name : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDateTime(log.session_started_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="log.session_ended_at">
                                        {{ formatDateTime(log.session_ended_at) }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 uppercase tracking-wider"
                                    >
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium">
                                    <span
                                        :class="[
                                            log.session_ended_at
                                                ? 'text-gray-500 dark:text-gray-400'
                                                : 'text-emerald-600 dark:text-emerald-400 font-bold'
                                        ]"
                                    >
                                        {{ formatDuration(log.duration_seconds) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="text-center py-8 text-gray-400 dark:text-gray-500 font-medium">
                                    Tidak ada data riwayat koneksi ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="logs.links.length > 3" class="px-6 py-4 bg-gray-50 dark:bg-gray-900/10 border-t border-gray-150 dark:border-gray-700/50 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ logs.from || 0 }} sampai {{ logs.to || 0 }} dari {{ logs.total }} baris
                    </div>
                    <div class="flex space-x-1">
                        <Component
                            :is="link.url ? 'Link' : 'span'"
                            v-for="(link, i) in logs.links"
                            :key="i"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            class="px-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
