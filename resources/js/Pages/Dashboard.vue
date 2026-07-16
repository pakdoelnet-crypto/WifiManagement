<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import apexchart from 'vue3-apexcharts';

const page = usePage();

const showFinancials = computed(() => {
    const roles = page.props.auth.user.roles || [];
    const permissions = page.props.auth.user.permissions || [];
    return permissions.includes('invoices.view') || roles.includes('Super Admin') || roles.includes('Owner');
});

const isSuperAdmin = computed(() => {
    const roles = page.props.auth.user.roles || [];
    return roles.includes('Super Admin');
});

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    charts: {
        type: Object,
        required: true,
    },
    recentLogs: {
        type: Array,
        required: true,
    }
});

// Live active sessions
const liveOnlineCount = ref(props.stats.onlineCustomers);

// Router dynamic details
const activeRouters = ref(props.stats.routers.map(r => ({
    ...r,
    uptime: 'Menuat...',
    board_name: 'RB3011UiAS (Demo)',
    version: 'RouterOS v7.12',
    free_memory: '128 MB',
    total_memory: '256 MB',
})));

const primaryRouterIndex = computed(() => {
    if (activeRouters.value.length > 0) {
        const index = activeRouters.value.findIndex(r => r.status === 'online');
        return index !== -1 ? index : 0;
    }
    return -1;
});

const primaryRouter = computed(() => {
    const idx = primaryRouterIndex.value;
    if (idx !== -1 && activeRouters.value[idx]) {
        return activeRouters.value[idx];
    }
    return { id: 0, name: 'Offline Router', status: 'offline', cpu_load: 0, uptime: '-', board_name: '-', version: '-', free_memory: '-', total_memory: '-' };
});

// Live Interface list
const interfaces = ref([]);
const selectedInterface = ref('');

// Live Traffic Chart Data
const trafficCategories = ref([]);
const rxSeriesData = ref([]);
const txSeriesData = ref([]);

const trafficSeries = computed(() => [
    {
        name: 'Download (RX)',
        data: rxSeriesData.value,
    },
    {
        name: 'Upload (TX)',
        data: txSeriesData.value,
    }
]);

// Initialize mock traffic data for scrolling graph
const initMockTrafficData = () => {
    const now = new Date();
    for (let i = 19; i >= 0; i--) {
        const time = new Date(now.getTime() - i * 1000);
        const timeStr = time.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        trafficCategories.value.push(timeStr);
        rxSeriesData.value.push(parseFloat((Math.random() * 20 + 35).toFixed(2))); // 35 - 55 Mbps
        txSeriesData.value.push(parseFloat((Math.random() * 3.5 + 2.0).toFixed(2))); // 2.0 - 5.5 Mbps
    }
};

const trafficChartOptions = ref({
    chart: {
        id: 'realtime-traffic',
        type: 'area',
        animations: {
            enabled: true,
            easing: 'linear',
            dynamicAnimation: { speed: 800 }
        },
        toolbar: { show: false },
        fontFamily: 'Figtree, sans-serif',
        background: 'transparent',
    },
    colors: ['#10b981', '#6366f1'], // Green for RX (Download), Indigo for TX (Upload)
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.02,
        }
    },
    xaxis: {
        categories: trafficCategories.value,
        tickAmount: 5,
        labels: {
            rotate: 0,
            hideOverlappingLabels: true,
            style: { colors: '#94a3b8' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8' },
            formatter: (val) => val + ' Mbps',
        },
    },
    grid: {
        borderColor: '#1e293b',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: 'dark',
        x: { show: true },
        y: {
            formatter: (val) => val + ' Mbps',
        },
    },
});

// Update traffic values on event
const updateTrafficData = (rxBits, txBits) => {
    const rxMbps = parseFloat((rxBits / 1000000).toFixed(2));
    const txMbps = parseFloat((txBits / 1000000).toFixed(2));

    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    rxSeriesData.value.push(rxMbps);
    txSeriesData.value.push(txMbps);
    trafficCategories.value.push(timeStr);

    if (rxSeriesData.value.length > 20) {
        rxSeriesData.value.shift();
        txSeriesData.value.shift();
        trafficCategories.value.shift();
    }

    trafficChartOptions.value = {
        ...trafficChartOptions.value,
        xaxis: {
            ...trafficChartOptions.value.xaxis,
            categories: [...trafficCategories.value]
        }
    };
};

// Interval for fetching interfaces & active traffic
let trafficInterval = null;
let resourcesInterval = null;

const fetchInterfaces = async () => {
    const routerItem = primaryRouter.value;
    if (!routerItem || routerItem.id === 0) return;

    try {
        const response = await fetch(route('routers.interfaces', routerItem.id));
        if (response.ok) {
            const data = await response.json();
            interfaces.value = data.interfaces || [];
            if (interfaces.value.length > 0 && !selectedInterface.value) {
                // Find ether1 or first running
                const ether1 = interfaces.value.find(i => i.name.toLowerCase().includes('ether1'));
                selectedInterface.value = ether1 ? ether1.name : interfaces.value[0].name;
            }
        }
    } catch (e) {
        console.error('Failed to load interfaces', e);
    }
};

const pollActiveTraffic = async () => {
    const routerItem = primaryRouter.value;
    if (!routerItem || routerItem.id === 0 || !selectedInterface.value) return;

    try {
        const response = await fetch(route('routers.active-traffic', {
            id: routerItem.id,
            interface: selectedInterface.value
        }));
        if (response.ok) {
            const data = await response.json();
            // Directly push data point for fast responsiveness
            updateTrafficData(data.rx || 0, data.tx || 0);
        }
    } catch (e) {
        console.error('Traffic poll error', e);
    }
};

const fetchRouterResources = async () => {
    const routerItem = primaryRouter.value;
    if (!routerItem || routerItem.id === 0) return;

    try {
        const response = await fetch(route('routers.live-resources', routerItem.id));
        if (response.ok) {
            const data = await response.json();
            routerItem.cpu_load = data.cpu_load;
            routerItem.uptime = data.uptime;
            routerItem.board_name = data.board_name;
            routerItem.version = data.version;
            routerItem.free_memory = data.free_memory || '128 MB';
            routerItem.total_memory = data.total_memory || '256 MB';
        }
    } catch (e) {
        // Fallback simulate CPU fluctuation
        if (routerItem.status === 'online') {
            const fluctuation = Math.floor(Math.random() * 5) - 2; // -2 to +2
            routerItem.cpu_load = Math.max(5, Math.min(85, (routerItem.cpu_load || 15) + fluctuation));
            routerItem.uptime = '2d 14h 32m';
            routerItem.board_name = 'RB3011UiAS (Demo)';
            routerItem.version = 'RouterOS v7.12';
            routerItem.free_memory = '142 MB';
            routerItem.total_memory = '256 MB';
        }
    }
};

onMounted(() => {
    initMockTrafficData();

    // 1. Listen to Echo channels
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

        window.Echo.channel('interface-traffic')
            .listen('.InterfaceTrafficUpdated', (e) => {
                if (e.interfaceName === selectedInterface.value) {
                    updateTrafficData(e.rx, e.tx);
                }
            });
    }

    // 2. Fetch data
    fetchInterfaces().then(() => {
        pollActiveTraffic();
    });
    fetchRouterResources();

    // 3. Set polling intervals
    trafficInterval = setInterval(pollActiveTraffic, 1000); // 1 second live poll
    resourcesInterval = setInterval(fetchRouterResources, 5000); // 5 seconds resource updates
});

onUnmounted(() => {
    if (trafficInterval) clearInterval(trafficInterval);
    if (resourcesInterval) clearInterval(resourcesInterval);
});

// Test connection handler in table
const isTesting = ref(null);
const testConnection = (id) => {
    isTesting.value = id;
    router.post(route('routers.test-connection', id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isTesting.value = null;
            fetchRouterResources();
        }
    });
};

// Format Rupiah Currency
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

// Revenue Chart Configuration (Dark theme colors)
const revenueChartOptions = computed(() => ({
    chart: {
        id: 'revenue-chart',
        toolbar: { show: false },
        fontFamily: 'Figtree, sans-serif',
        background: 'transparent',
    },
    colors: ['#6366f1'], // Indigo-500
    stroke: {
        curve: 'smooth',
        width: 3.5,
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.01,
        }
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
        borderColor: '#1e293b',
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
        borderColor: '#1e293b',
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

        <!-- Container Wrapped with Dark NMS Theme Styles -->
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6 text-slate-100">
            
            <!-- TUGAS 1.1: GRAFIK TRAFIK LANGSUNG (Topmost Card) -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black tracking-wide text-white flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Grafik Trafik Langsung (Live)
                        </h3>
                        <p class="text-xs text-slate-400">Monitoring real-time throughput data jaringan</p>
                    </div>
                    
                    <div class="flex items-center gap-3 shrink-0">
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Interface:</label>
                        <select
                            v-model="selectedInterface"
                            class="bg-slate-950 border border-slate-800 text-slate-300 text-xs font-bold rounded-xl px-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="int in interfaces" :key="int.name" :value="int.name">
                                {{ int.name }} {{ int.running ? '•' : '(Down)' }}
                            </option>
                            <option v-if="interfaces.length === 0" value="ether1">ether1-ISP (Demo)</option>
                        </select>
                    </div>
                </div>

                <!-- Live Traffic Area Chart -->
                <div class="h-80 w-full">
                    <apexchart
                        type="area"
                        height="100%"
                        width="100%"
                        :options="trafficChartOptions"
                        :series="trafficSeries"
                    />
                </div>
            </div>

            <!-- TUGAS 1.2: STAT CARDS DENGAN STYLE MOMON NMS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5">
                
                <!-- Card 1: Hotspot Aktif -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-emerald-500/30">
                    <div class="p-4 bg-emerald-500/10 text-emerald-500 rounded-2xl shrink-0">
                        <!-- Icon User Group / Hotspot -->
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hotspot Aktif</span>
                        <div class="text-3xl font-black text-emerald-500 leading-none">0</div>
                        <span class="text-[10px] text-slate-400 block truncate">Sesi aktif hotspot</span>
                    </div>
                </div>

                <!-- Card 2: PPP Aktif -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-orange-500/30">
                    <div class="p-4 bg-orange-500/10 text-orange-500 rounded-2xl shrink-0">
                        <!-- Icon Connection / Plug -->
                        <svg class="h-7 w-7 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1.5">
                            PPP Aktif
                            <span class="h-2 w-2 rounded-full bg-orange-500 animate-ping" v-if="liveOnlineCount > 0"></span>
                        </span>
                        <div class="text-3xl font-black text-orange-500 leading-none">{{ liveOnlineCount }}</div>
                        <span class="text-[10px] text-slate-400 block">Sesi PPPoE aktif</span>
                    </div>
                </div>

                <!-- Card 3: Hari Ini -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-cyan-500/30">
                    <div class="p-4 bg-cyan-500/10 text-cyan-500 rounded-2xl shrink-0">
                        <!-- Icon Wallet / Cash -->
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hari Ini</span>
                        <div class="text-2xl font-black text-cyan-500 leading-none">{{ stats.paidInvoicesToday ? formatRupiah(stats.paidInvoicesToday) : 'Rp 0' }}</div>
                        <span class="text-[10px] text-slate-400 block">Pendapatan hari ini</span>
                    </div>
                </div>

                <!-- Card 4: Bulan Ini -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-yellow-500/30">
                    <div class="p-4 bg-yellow-500/10 text-yellow-500 rounded-2xl shrink-0">
                        <!-- Icon Trend Up / Income -->
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Bulan Ini</span>
                        <div class="text-2xl font-black text-yellow-500 leading-none">{{ formatRupiah(stats.paidInvoicesThisMonth) }}</div>
                        <span class="text-[10px] text-slate-400 block">Pendapatan bulan ini</span>
                    </div>
                </div>

                <!-- Card 5: Model Perangkat -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-indigo-500/30">
                    <div class="p-4 bg-indigo-500/10 text-indigo-400 rounded-2xl shrink-0">
                        <!-- Icon Router / Board -->
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0 flex-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Model Perangkat</span>
                        <div class="text-xl font-black text-white leading-none truncate">{{ primaryRouter.board_name || 'RB450Gx4 (Demo)' }}</div>
                        <span class="text-[10px] text-slate-400 block uppercase font-bold" :class="primaryRouter.status === 'online' ? 'text-emerald-400' : 'text-rose-400'">
                            {{ primaryRouter.status === 'online' ? primaryRouter.version : 'OFFLINE' }}
                        </span>
                    </div>
                </div>

                <!-- Card 6: Uptime -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex items-center gap-4 transition duration-300 hover:border-purple-500/30">
                    <div class="p-4 bg-purple-500/10 text-purple-400 rounded-2xl shrink-0">
                        <!-- Icon Time / Uptime -->
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Uptime</span>
                        <div class="text-sm font-black text-white leading-tight uppercase truncate">{{ primaryRouter.uptime || '-' }}</div>
                        <span class="text-[10px] text-slate-400 block">Waktu aktif router</span>
                    </div>
                </div>

            </div>

            <!-- TUGAS 1.3: CARD BARU "INFORMASI SISTEM" -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Informasi Sistem & Resources Router
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">
                    <!-- Left Column: Specs -->
                    <div class="grid grid-cols-2 gap-y-4 border-r border-slate-800/80 pr-0 md:pr-8">
                        <div>
                            <span class="text-slate-400 block">Identitas Router</span>
                            <span class="font-bold text-white text-sm">{{ primaryRouter.name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Model Perangkat</span>
                            <span class="font-bold text-white text-sm">{{ primaryRouter.board_name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Versi RouterOS</span>
                            <span class="font-bold text-white text-sm">{{ primaryRouter.version }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Uptime</span>
                            <span class="font-bold text-white text-sm">{{ primaryRouter.uptime }}</span>
                        </div>
                    </div>

                    <!-- Right Column: CPU & Memory Usage bars -->
                    <div class="space-y-4 flex flex-col justify-center">
                        <!-- CPU Usage -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between font-semibold">
                                <span class="text-slate-400">Beban CPU</span>
                                <span class="text-white">{{ primaryRouter.cpu_load }}%</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                                <div
                                    :style="{ width: primaryRouter.cpu_load + '%' }"
                                    :class="[
                                        primaryRouter.cpu_load > 80 ? 'bg-rose-500' : (primaryRouter.cpu_load > 50 ? 'bg-amber-500' : 'bg-emerald-500')
                                    ]"
                                    class="h-full rounded-full transition-all duration-500"
                                ></div>
                            </div>
                        </div>

                        <!-- Memory Usage -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between font-semibold">
                                <span class="text-slate-400">Penggunaan Memori (RAM)</span>
                                <span class="text-white">{{ primaryRouter.free_memory }} Bebas dari {{ primaryRouter.total_memory }}</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                                <div
                                    style="width: 45%"
                                    class="h-full rounded-full bg-indigo-500"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAFIK / CHARTS GRID -->
            <div class="grid grid-cols-1 gap-6" :class="showFinancials ? 'lg:grid-cols-2' : 'lg:grid-cols-1'">
                
                <!-- Chart 1: Pendapatan -->
                <div v-if="showFinancials" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-md font-bold text-white">Pendapatan 6 Bulan Terakhir</h3>
                            <p class="text-xs text-slate-400">Total tagihan terbayar per bulan (Rupiah)</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <apexchart
                            type="area"
                            height="100%"
                            :options="revenueChartOptions"
                            :series="revenueChartSeries"
                        />
                    </div>
                </div>

                <!-- Chart 2: Registrasi Baru -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-md font-bold text-white">Pelanggan Baru per Bulan</h3>
                            <p class="text-xs text-slate-400">Jumlah pendaftaran pelanggan 6 bulan terakhir</p>
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

            <!-- BOTTOM PANEL: ROUTER STATUS LIST & RECENT ACTIVITY LOGS -->
            <div v-if="isSuperAdmin" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Section (2/3 width): Status Router Jaringan -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md lg:col-span-2 space-y-4">
                    <div>
                        <h3 class="text-md font-bold text-white">Status Router Jaringan</h3>
                        <p class="text-xs text-slate-400">Daftar semua mesin router aktif dan kinerjanya</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-950/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                    <th class="px-4 py-3">Nama Router</th>
                                    <th class="px-4 py-3">IP Address</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Beban CPU</th>
                                    <th class="px-4 py-3">Terakhir Dicek</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                <tr v-for="routerItem in activeRouters" :key="routerItem.id" class="hover:bg-slate-950/20 transition">
                                    <td class="px-4 py-3 font-semibold text-white">
                                        {{ routerItem.name }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-400">
                                        {{ routerItem.host }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                routerItem.status === 'online'
                                                    ? 'bg-emerald-950/40 text-emerald-400'
                                                    : 'bg-rose-950/40 text-rose-400'
                                            ]"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide"
                                        >
                                            {{ routerItem.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">
                                        <div class="flex items-center gap-2">
                                            <div class="w-12 bg-slate-950 h-1.5 rounded-full overflow-hidden border border-slate-800 shrink-0">
                                                <div
                                                    :style="{ width: routerItem.cpu_load + '%' }"
                                                    :class="[
                                                        routerItem.cpu_load > 80 ? 'bg-rose-500' : (routerItem.cpu_load > 50 ? 'bg-amber-500' : 'bg-emerald-500')
                                                    ]"
                                                    class="h-full rounded-full"
                                                ></div>
                                            </div>
                                            <span class="text-xs">{{ routerItem.cpu_load }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-400">
                                        {{ routerItem.last_checked_at }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            @click="testConnection(routerItem.id)"
                                            :disabled="isTesting === routerItem.id"
                                            class="inline-flex items-center px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-lg text-xs font-bold transition disabled:opacity-50"
                                        >
                                            <svg v-if="isTesting === routerItem.id" class="animate-spin h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ isTesting === routerItem.id ? 'Menguji...' : 'Uji Koneksi' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Section (1/3 width): Log Aktivitas Terbaru -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-md font-bold text-white">Log Aktivitas Terbaru</h3>
                            <p class="text-xs text-slate-400">Aktivitas admin & staf operator</p>
                        </div>
                        <Link
                            :href="route('audit-logs.index')"
                            class="text-xs font-bold text-indigo-400 hover:text-indigo-300"
                        >
                            Lihat Semua
                        </Link>
                    </div>
                    
                    <div class="space-y-4">
                        <div
                            v-for="log in recentLogs"
                            :key="log.id"
                            class="flex items-start gap-3 p-3 bg-slate-950/40 border border-slate-800/80 rounded-xl"
                        >
                            <div class="h-8 w-8 rounded-full bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-black text-xs shrink-0 uppercase">
                                {{ log.user_name.substring(0, 2) }}
                            </div>
                            <div class="space-y-0.5 min-w-0 flex-1">
                                <p class="text-xs font-bold text-white">
                                    {{ log.user_name }}
                                </p>
                                <p class="text-xs text-slate-400 truncate">
                                    {{ log.action }} {{ log.model_type }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    {{ log.created_at }}
                                </p>
                            </div>
                        </div>
                        
                        <div v-if="recentLogs.length === 0" class="text-center py-6 text-xs text-slate-400">
                            Belum ada log aktivitas tercatat.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
