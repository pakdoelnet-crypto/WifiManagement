<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    routers: Array,
});

const stats = ref({
    download_mbps: 0.0,
    upload_mbps: 0.0,
    top_routers: [],
    top_users: [],
    bandwidth_today_gb: 0,
});

const history = ref({
    download: Array(20).fill(0),
    upload: Array(20).fill(0),
});

const isRefreshing = ref(false);

const fetchLiveTraffic = async () => {
    isRefreshing.value = true;
    try {
        const response = await axios.get(route('traffic.stats'));
        stats.value = response.data;

        // Push new value to history array for SVG live sparkline
        history.value.download.push(response.data.download_mbps);
        history.value.download.shift();
        history.value.upload.push(response.data.upload_mbps);
        history.value.upload.shift();
    } catch (e) {
        console.error('Failed to poll traffic stats:', e);
    } finally {
        isRefreshing.value = false;
    }
};

let intervalId = null;
onMounted(() => {
    fetchLiveTraffic();
    intervalId = setInterval(fetchLiveTraffic, 5000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

// Helper to generate SVG polyline path coordinates
const getSparklinePoints = (arr, max = 50) => {
    const width = 300;
    const height = 80;
    const step = width / (arr.length - 1);
    
    // Find dynamic maximum in the history array
    const limit = Math.max(...arr, 10);
    
    const points = arr.map((val, idx) => {
        const x = idx * step;
        const y = height - (val / limit * (height - 10)) - 5;
        return `${x},${y}`;
    });
    return points.join(' ');
};
</script>

<template>
    <Head title="Monitoring Trafik" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Realtime Traffic Monitoring
                </h2>
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Live Polling (5s)</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Speedometers & Live Sparklines Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Download Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Total Download Rate</div>
                            <div class="flex items-baseline gap-1 text-emerald-600 dark:text-emerald-400">
                                <span class="text-4xl font-black font-mono">{{ stats.download_mbps }}</span>
                                <span class="text-sm font-bold">Mbps</span>
                            </div>
                        </div>

                        <!-- Custom SVG Sparkline -->
                        <div class="mt-6 h-20 w-full">
                            <svg class="h-full w-full" viewBox="0 0 300 80">
                                <polyline
                                    fill="none"
                                    stroke="#10b981"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :points="getSparklinePoints(history.download)"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Upload Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Total Upload Rate</div>
                            <div class="flex items-baseline gap-1 text-indigo-600 dark:text-indigo-400">
                                <span class="text-4xl font-black font-mono">{{ stats.upload_mbps }}</span>
                                <span class="text-sm font-bold">Mbps</span>
                            </div>
                        </div>

                        <!-- Custom SVG Sparkline -->
                        <div class="mt-6 h-20 w-full">
                            <svg class="h-full w-full" viewBox="0 0 300 80">
                                <polyline
                                    fill="none"
                                    stroke="#6366f1"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :points="getSparklinePoints(history.upload)"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Bandwidth Today -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Kuota Bandwidth Terpakai Hari Ini</div>
                            <div class="flex items-baseline gap-1 text-gray-900 dark:text-gray-150">
                                <span class="text-4xl font-black font-mono">{{ stats.bandwidth_today_gb }}</span>
                                <span class="text-sm font-bold">GB</span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rata-rata Utilisasi Jaringan</div>
                            <div class="w-full bg-gray-100 dark:bg-gray-900 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 h-full rounded-full" style="width: 65%;"></div>
                            </div>
                            <div class="flex justify-between text-[9px] text-gray-400 mt-1 font-bold">
                                <span>KAPASITAS: 1 Gbps</span>
                                <span>65% TERPAKAI</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Routers & Top Users Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Top Routers -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Top 5 Router (Bandwidth Tinggi)</h3>
                        <div class="space-y-3">
                            <div v-for="(router, idx) in stats.top_routers" :key="idx" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-black text-indigo-500 font-mono">#{{ idx + 1 }}</span>
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ router.name }}</span>
                                </div>
                                <div class="flex gap-4 text-xs font-mono">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">↓ {{ router.rx }} Mbps</span>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">↑ {{ router.tx }} Mbps</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Users -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Pelanggan Aktif Online (Realtime)</h3>
                        <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                            <div v-for="(user, idx) in stats.top_users" :key="idx" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                <div>
                                    <div class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ user.username }}</div>
                                    <div class="text-[9px] text-gray-400 font-mono">IP: {{ user.ip }} | Uptime: {{ user.uptime }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-black text-indigo-500 font-mono">{{ user.usage_mbps }} Mbps</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
