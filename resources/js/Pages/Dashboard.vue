<script setup>
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import apexchart from 'vue3-apexcharts';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    charts: {
        type: Object,
        required: true,
    },
});

// Live online count updated via Laravel Echo
const liveOnlineCount = ref(props.stats.onlineCustomers);

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('ppp-active-sessions')
            .listen('.PppActiveSessionsUpdated', (e) => {
                if (Array.isArray(e)) {
                    liveOnlineCount.value = e.length;
                } else if (e && e.sessions && Array.isArray(e.sessions)) {
                    liveOnlineCount.value = e.sessions.length;
                } else if (e && Array.isArray(e.activeSessions)) {
                    liveOnlineCount.value = e.activeSessions.length;
                }
            });
    }
});

// Primary router (first router or placeholder)
const primaryRouter = computed(() => {
    if (props.stats.routers && props.stats.routers.length > 0) {
        // Find offline or high CPU one, otherwise first
        const needy = props.stats.routers.find(r => r.status !== 'online');
        return needy || props.stats.routers[0];
    }
    return { name: 'No Router Connected', status: 'offline', cpu_load: 0 };
});

// Format Rupiah Currency
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

// ApexCharts configuration
const revenueChartOptions = computed(() => ({
    chart: {
        id: 'revenue-chart',
        toolbar: { show: false },
        fontFamily: 'Figtree, sans-serif',
        background: 'transparent',
    },
    colors: ['#4f46e5'], // Indigo-600
    stroke: {
        curve: 'smooth',
        width: 3.5,
    },
    markers: {
        size: 5,
        strokeWidth: 2,
        hover: { size: 7 },
    },
    xaxis: {
        categories: props.charts.revenue.labels,
        labels: {
            style: { colors: '#94a3b8' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8' },
            formatter: (val) => formatRupiah(val),
        },
    },
    grid: {
        borderColor: '#e2e8f0',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val) => formatRupiah(val),
        },
    },
}));

const revenueChartSeries = computed(() => [
    {
        name: 'Pendapatan',
        data: props.charts.revenue.data,
    },
]);

const customerChartOptions = computed(() => ({
    chart: {
        id: 'customer-chart',
        toolbar: { show: false },
        fontFamily: 'Figtree, sans-serif',
        background: 'transparent',
    },
    colors: ['#10b981'], // Emerald-500
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '55%',
            distributed: false,
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.newCustomers.labels,
        labels: {
            style: { colors: '#94a3b8' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8' },
            formatter: (val) => Math.round(val) + ' User',
        },
    },
    grid: {
        borderColor: '#e2e8f0',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val) => val + ' Pelanggan Baru',
        },
    },
}));

const customerChartSeries = computed(() => [
    {
        name: 'Registrasi Baru',
        data: props.charts.newCustomers.data,
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard Portal Utama
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- STAT CARDS BARIS ATAS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Card 1: Total Customers -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 flex items-center justify-between transition duration-300">
                    <div class="space-y-1.5">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Pelanggan</span>
                        <div class="text-3xl font-extrabold text-gray-800 dark:text-white">
                            {{ stats.totalCustomers }}
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Terdaftar aktif/isolir</span>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Online Customers -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 flex items-center justify-between transition duration-300">
                    <div class="space-y-1.5">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            Sesi Aktif (Live)
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        </span>
                        <div class="text-3xl font-extrabold text-gray-800 dark:text-white">
                            {{ liveOnlineCount }}
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Pelanggan tersambung</span>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Router status -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 flex items-center justify-between transition duration-300">
                    <div class="space-y-2 flex-1 mr-3">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Router: {{ primaryRouter.name }}</span>
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    primaryRouter.status === 'online'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400'
                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400'
                                    ]"
                                class="px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide"
                            >
                                {{ primaryRouter.status }}
                            </span>
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300">CPU: {{ primaryRouter.cpu_load }}%</span>
                        </div>
                        <!-- Progress load bar -->
                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                            <div
                                :style="{ width: primaryRouter.cpu_load + '%' }"
                                :class="[
                                    primaryRouter.cpu_load > 80
                                        ? 'bg-rose-500'
                                        : (primaryRouter.cpu_load > 50 ? 'bg-amber-500' : 'bg-indigo-600')
                                ]"
                                class="h-full rounded-full transition-all duration-500"
                            ></div>
                        </div>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-2xl shrink-0">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Invoices due this month -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 flex items-center justify-between transition duration-300">
                    <div class="space-y-1.5">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Jatuh Tempo Bulan Ini</span>
                        <div class="text-xl font-extrabold text-rose-600 dark:text-rose-400 truncate">
                            {{ formatRupiah(stats.invoicesDueThisMonth) }}
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Total invoice belum lunas</span>
                    </div>
                    <div class="p-3 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 rounded-2xl">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- GRAFIK / CHARTS GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Chart 1: Pendapatan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-md font-bold text-gray-800 dark:text-white">Pendapatan 6 Bulan Terakhir</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Total tagihan terbayar per bulan (Rupiah)</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <apexchart
                            type="line"
                            height="100%"
                            :options="revenueChartOptions"
                            :series="revenueChartSeries"
                        />
                    </div>
                </div>

                <!-- Chart 2: Registrasi Baru -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-md font-bold text-gray-800 dark:text-white">Pelanggan Baru per Bulan</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Jumlah pendaftaran pelanggan 6 bulan terakhir</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <apexchart
                            type="bar"
                            height="100%"
                            :options="customerChartOptions"
                            :series="customerChartSeries"
                        />
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
