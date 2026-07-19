<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    stats: Object,
    routers: Array,
    pings: Object,
});

const stats = ref(props.stats);
const routers = ref(props.routers);
const pings = ref(props.pings);
const isRefreshing = ref(false);

const fetchLiveStats = async () => {
    isRefreshing.value = true;
    try {
        const response = await axios.get(route('noc.live'));
        stats.value = response.data.stats;
        routers.value = response.data.routers;
        pings.value = response.data.pings;
    } catch (e) {
        console.error('Failed to poll NOC stats:', e);
    } finally {
        isRefreshing.value = false;
    }
};

// OLT Mock Data (Bagian 1)
const olts = ref([
    {
        id: 'OLT-GPON-01',
        name: 'OLT SENTRAL PLAZA',
        type: 'HIOSO EPON',
        status: 'Aktif',
        onu_total: 128,
        onu_online: 124,
        onu_dying: 1,
        onu_loss: 3,
        onu_rx_bad: 2,
        online_rate: 96.88,
        rx_bad_rate: 1.56,
        last_update: 'Baru saja',
        coverage: 'Area Sentral & Pasar'
    },
    {
        id: 'OLT-GPON-02',
        name: 'OLT MANGIR TIMUR',
        type: 'HIOSO GPON',
        status: 'Aktif',
        onu_total: 96,
        onu_online: 90,
        onu_dying: 0,
        onu_loss: 6,
        onu_rx_bad: 4,
        online_rate: 93.75,
        rx_bad_rate: 4.16,
        last_update: '1 menit yang lalu',
        coverage: 'Dusun Mangir & Sekitarnya'
    }
]);

const animateMockData = () => {
    // Fluctuate OLT online rates slightly
    olts.value.forEach(olt => {
        const delta = Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
        let online = olt.onu_online + delta;
        if (online > olt.onu_total - olt.onu_loss) online = olt.onu_total - olt.onu_loss;
        if (online < olt.onu_total - 12) online = olt.onu_total - 12;
        olt.onu_online = online;
        olt.onu_loss = olt.onu_total - online - olt.onu_dying;
        olt.online_rate = parseFloat(((online / olt.onu_total) * 100).toFixed(2));
    });
};

// Realtime HTB Queue integration (Bagian 3)
const htbQueues = ref([]);
const isRefreshingQueues = ref(false);

const fetchLiveQueues = async () => {
    isRefreshingQueues.value = true;
    try {
        const response = await axios.get(route('noc.queues'));
        htbQueues.value = response.data;
    } catch (e) {
        console.error('Failed to poll HTB queues:', e);
    } finally {
        isRefreshingQueues.value = false;
    }
};

let intervalId = null;
let mockIntervalId = null;
let queueIntervalId = null;

onMounted(() => {
    fetchLiveQueues();
    intervalId = setInterval(fetchLiveStats, 30000);
    mockIntervalId = setInterval(animateMockData, 2000);
    queueIntervalId = setInterval(fetchLiveQueues, 8000); // refresh queue status every 8 seconds
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    if (mockIntervalId) clearInterval(mockIntervalId);
    if (queueIntervalId) clearInterval(queueIntervalId);
});
</script>

<template>
    <Head title="NOC Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Network Operations Center (NOC)
                </h2>
                <button
                    @click="fetchLiveStats"
                    :disabled="isRefreshing"
                    class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
                >
                    <svg :class="{ 'animate-spin': isRefreshing }" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                    </svg>
                    Refresh (Auto 30s)
                </button>
            </div>
        </template>

        <div class="py-12 bg-slate-950 min-h-screen text-slate-100">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- NOC Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-5 rounded-2xl shadow-lg shadow-indigo-500/5 transition hover:border-indigo-500/35 hover:-translate-y-0.5 duration-350 flex items-center gap-4">
                        <div class="p-3 bg-indigo-950/30 text-indigo-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-100 font-mono">{{ stats.totalRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Router</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-5 rounded-2xl shadow-lg shadow-indigo-500/5 transition hover:border-indigo-500/35 hover:-translate-y-0.5 duration-350 flex items-center gap-4">
                        <div class="p-3 bg-emerald-950/30 text-emerald-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-emerald-400 font-mono">{{ stats.onlineRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Router Online</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-5 rounded-2xl shadow-lg shadow-indigo-500/5 transition hover:border-indigo-500/35 hover:-translate-y-0.5 duration-350 flex items-center gap-4">
                        <div class="p-3 bg-rose-950/30 text-rose-450 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-rose-450 font-mono">{{ stats.offlineRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Router Offline</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-5 rounded-2xl shadow-lg shadow-indigo-500/5 transition hover:border-indigo-500/35 hover:-translate-y-0.5 duration-350 flex items-center gap-4">
                        <div class="p-3 bg-amber-950/30 text-amber-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-amber-405 font-mono">{{ stats.highCpu }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">CPU Tinggi</div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-5 rounded-2xl shadow-lg shadow-indigo-500/5 transition hover:border-indigo-500/35 hover:-translate-y-0.5 duration-350 flex items-center gap-4">
                        <div class="p-3 bg-blue-950/30 text-blue-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-blue-400 font-mono">{{ stats.highRam }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">RAM Tinggi</div>
                        </div>
                    </div>
                </div>

                <!-- OLT Monitoring Grid (Bagian 1) -->
                <div class="bg-slate-900/50 border border-slate-800/80 backdrop-blur-md rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            GPON / EPON OLT Monitoring
                        </h3>
                        <span class="text-[10px] bg-slate-850 border border-slate-800 text-emerald-400 font-bold px-2 py-0.5 rounded-md font-mono">
                            LIVE PREVIEW
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- OLT Card -->
                        <div v-for="olt in olts" :key="olt.id" class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5 hover:border-indigo-500/30 transition duration-300 relative overflow-hidden group">
                            <!-- Background Glow Decor -->
                            <div class="absolute -top-12 -right-12 h-24 w-24 rounded-full bg-indigo-500/5 blur-xl group-hover:bg-indigo-500/10 transition duration-300"></div>
                            
                            <!-- Header OLT -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-sm font-black text-slate-100 tracking-wide font-mono">{{ olt.name }}</h4>
                                    <span class="text-[10px] text-slate-500 font-mono tracking-wider">{{ olt.id }} &bull; {{ olt.type }}</span>
                                </div>
                                <span class="px-2.5 py-0.5 text-[9px] font-black uppercase rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 tracking-wider">
                                    {{ olt.status }}
                                </span>
                            </div>
                            
                            <!-- Grid Angka Ringkasan Baris 1 -->
                            <div class="grid grid-cols-3 gap-2 bg-slate-900/40 p-3 rounded-xl border border-slate-900/50 mb-3 text-center">
                                <div>
                                    <div class="text-slate-450 text-[9px] font-bold uppercase tracking-wider">Total ONU</div>
                                    <div class="text-base font-black text-slate-100 font-mono">{{ olt.onu_total }}</div>
                                </div>
                                <div>
                                    <div class="text-emerald-400 text-[9px] font-bold uppercase tracking-wider">Online</div>
                                    <div class="text-base font-black text-emerald-400 font-mono">{{ olt.onu_online }}</div>
                                </div>
                                <div>
                                    <div class="text-amber-400 text-[9px] font-bold uppercase tracking-wider">Dying/Gasp</div>
                                    <div class="text-base font-black text-amber-400 font-mono">{{ olt.onu_dying }}</div>
                                </div>
                            </div>

                            <!-- Grid Angka Ringkasan Baris 2 -->
                            <div class="grid grid-cols-3 gap-2 bg-slate-900/40 p-3 rounded-xl border border-slate-900/50 mb-4 text-center">
                                <div>
                                    <div class="text-rose-450 text-[9px] font-bold uppercase tracking-wider">Loss</div>
                                    <div class="text-base font-black text-rose-400 font-mono">{{ olt.onu_loss }}</div>
                                </div>
                                <div>
                                    <div class="text-pink-400 text-[9px] font-bold uppercase tracking-wider">RX Buruk</div>
                                    <div class="text-base font-black text-pink-400 font-mono">{{ olt.onu_rx_bad }}</div>
                                </div>
                                <div>
                                    <div class="text-sky-400 text-[9px] font-bold uppercase tracking-wider">Online Rate</div>
                                    <div class="text-base font-black text-sky-450 font-mono">{{ olt.online_rate }}%</div>
                                </div>
                            </div>

                            <!-- Progress Bars -->
                            <div class="space-y-3 pt-1 border-t border-slate-900/60">
                                <!-- Online % -->
                                <div>
                                    <div class="flex justify-between text-[9px] text-slate-400 mb-1 font-bold">
                                        <span>ONLINE RATE %</span>
                                        <span class="text-sky-400 font-mono">{{ olt.online_rate }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-900 h-2 rounded-full overflow-hidden border border-slate-800">
                                        <div class="bg-gradient-to-r from-sky-500 to-indigo-500 h-full rounded-full transition-all duration-500" :style="{ width: olt.online_rate + '%' }"></div>
                                    </div>
                                </div>
                                
                                <!-- RX Buruk % -->
                                <div>
                                    <div class="flex justify-between text-[9px] text-slate-400 mb-1 font-bold">
                                        <span>RX BURUK RATE %</span>
                                        <span class="text-pink-400 font-mono">{{ olt.rx_bad_rate }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-900 h-2 rounded-full overflow-hidden border border-slate-800">
                                        <div class="bg-pink-500 h-full rounded-full transition-all duration-500" :style="{ width: (olt.rx_bad_rate * 5) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer OLT -->
                            <div class="mt-4 pt-3 border-t border-slate-900/60 flex justify-between items-center text-[9px] text-slate-500 font-semibold font-mono uppercase tracking-wider">
                                <span class="text-emerald-500 flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> NORMAL
                                </span>
                                <span>Cakup: {{ olt.coverage }}</span>
                                <span>Up: {{ olt.last_update }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ISP Latency Status & Router Table Grid & HTB Queue Panel -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Router Table & HTB Panel (Left 2 Columns) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Router Table -->
                        <div class="bg-slate-900/50 border border-slate-800/80 backdrop-blur-md p-6 rounded-2xl shadow-xl overflow-hidden">
                            <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider mb-4">Live Router Status</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider">
                                            <th class="p-3">Nama Router</th>
                                            <th class="p-3">IP Address</th>
                                            <th class="p-3">Status</th>
                                            <th class="p-3">Uptime</th>
                                            <th class="p-3 text-right">CPU</th>
                                            <th class="p-3 text-right">RAM</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        <tr v-for="router in routers" :key="router.id" class="hover:bg-slate-800/30 transition duration-150">
                                            <td class="p-3 font-semibold text-slate-100">{{ router.name }}</td>
                                            <td class="p-3 font-mono text-slate-400">{{ router.host }}</td>
                                            <td class="p-3">
                                                <span
                                                    :class="router.status === 'online' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                                    class="px-2.5 py-0.5 rounded font-bold uppercase tracking-wider text-[9px]"
                                                >
                                                    {{ router.status }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-slate-400 font-mono">{{ router.uptime }}</td>
                                            <td class="p-3 text-right font-mono" :class="router.cpu_load > 80 ? 'text-rose-450 font-bold shadow-pulse' : 'text-slate-350'">
                                                {{ router.cpu_load }}%
                                            </td>
                                            <td class="p-3 text-right font-mono" :class="router.ram_usage > 80 ? 'text-rose-450 font-bold' : 'text-slate-350'">
                                                {{ router.ram_usage }}%
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- HTB Queue Panel (Bagian 3) -->
                        <div class="bg-slate-900/50 border border-slate-800/80 backdrop-blur-md rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    HTB Bandwidth Queue (Mikrotik)
                                </h3>
                                <span class="text-[10px] bg-slate-850 border border-slate-800 text-indigo-400 font-bold px-2 py-0.5 rounded-md font-mono">
                                    REALTIME INTERACTIVE
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[460px] overflow-y-auto pr-1">
                                <div v-if="htbQueues.length === 0" class="col-span-2 text-center text-slate-500 py-12">
                                    <div v-if="isRefreshingQueues" class="flex flex-col items-center justify-center gap-2.5">
                                        <svg class="animate-spin h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-xs font-bold font-mono text-slate-400 tracking-wider">MENGHUBUNGKAN KE MIKROTIK API & TUNNEL...</span>
                                    </div>
                                    <span v-else class="text-xs font-bold font-mono tracking-wider">TIDAK ADA DATA ANTREAN QUEUE. PASTIKAN KONEKSI ROUTER AKTIF.</span>
                                </div>
                                <div v-for="q in htbQueues" :key="q.name" class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl hover:border-indigo-500/20 transition duration-200">
                                    <!-- Queue Header -->
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <div class="text-xs font-black text-slate-200 font-mono tracking-wide">{{ q.name }}</div>
                                            <div class="text-[9px] text-slate-500 font-mono">Max limit: {{ q.max_limit_tx }}M / {{ q.max_limit_rx }}M | Burst: {{ q.burst_limit }}</div>
                                        </div>
                                        <div>
                                            <span :class="{
                                                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': q.status === 'aktif',
                                                'bg-amber-500/10 text-amber-400 border border-amber-500/20': q.status === 'idle',
                                                'bg-rose-500/10 text-rose-450 border border-rose-500/20 animate-pulse': q.status === 'terpotong'
                                            }" class="px-2 py-0.5 rounded text-[8px] font-black uppercase font-mono tracking-wider border">
                                                {{ q.status === 'terpotong' ? 'Throttled / Limit' : q.status }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Bandwidth Rate metrics -->
                                    <div class="flex justify-between items-center text-[10px] font-mono mb-2 text-slate-400">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[8px] text-slate-650 font-bold">DOWN</span>
                                            <span class="text-emerald-450 font-bold">↓ {{ q.current_tx }} Mbps</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-[8px] text-slate-650 font-bold">UP</span>
                                            <span class="text-indigo-400 font-bold">↑ {{ q.current_rx }} Mbps</span>
                                        </div>
                                        <div class="text-slate-550 text-[9px] font-bold">
                                            Utilisasi: {{ q.usage }}%
                                        </div>
                                    </div>

                                    <!-- Bandwidth progress bar with changing colors -->
                                    <div class="w-full bg-slate-900 h-1.5 rounded-full overflow-hidden border border-slate-800">
                                        <div :class="{
                                            'bg-emerald-500': q.usage <= 75,
                                            'bg-amber-500': q.usage > 75 && q.usage <= 90,
                                            'bg-rose-500 shadow-[0_0_8px_#ef4444]': q.usage > 90
                                        }" class="h-full rounded-full transition-all duration-300" :style="{ width: Math.min(q.usage, 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ISP Latency Status (Right 1 Column) -->
                    <div class="bg-slate-900/50 border border-slate-800/80 backdrop-blur-md p-6 rounded-2xl shadow-xl h-fit">
                        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider mb-4">Ping ISP Gateway & DNS</h3>
                        <div class="space-y-4">
                            <!-- Google -->
                            <div class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-slate-200">Google DNS</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Host: 8.8.8.8</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-200">{{ pings.google.latency !== null ? pings.google.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500 shadow-[0_0_8px_#10b981]': pings.google.status === 'green',
                                        'bg-amber-500 shadow-[0_0_8px_#f59e0b]': pings.google.status === 'yellow',
                                        'bg-rose-500 shadow-[0_0_8px_#ef4444]': pings.google.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Cloudflare -->
                            <div class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-slate-200">Cloudflare DNS</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Host: 1.1.1.1</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-200">{{ pings.cloudflare.latency !== null ? pings.cloudflare.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500 shadow-[0_0_8px_#10b981]': pings.cloudflare.status === 'green',
                                        'bg-amber-500 shadow-[0_0_8px_#f59e0b]': pings.cloudflare.status === 'yellow',
                                        'bg-rose-500 shadow-[0_0_8px_#ef4444]': pings.cloudflare.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- OpenDNS -->
                            <div class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-slate-200">OpenDNS</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Host: 208.67.222.222</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-200">{{ pings.opendns.latency !== null ? pings.opendns.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500 shadow-[0_0_8px_#10b981]': pings.opendns.status === 'green',
                                        'bg-amber-500 shadow-[0_0_8px_#f59e0b]': pings.opendns.status === 'yellow',
                                        'bg-rose-500 shadow-[0_0_8px_#ef4444]': pings.opendns.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Gateway ISP -->
                            <div class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-slate-200">ISP Gateway</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Target: Router WAN Gateway</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-200">{{ pings.gateway.latency }} ms</span>
                                    <div :class="{
                                        'bg-emerald-500 shadow-[0_0_8px_#10b981]': pings.gateway.status === 'green',
                                        'bg-amber-500 shadow-[0_0_8px_#f59e0b]': pings.gateway.status === 'yellow',
                                        'bg-rose-500 shadow-[0_0_8px_#ef4444]': pings.gateway.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
