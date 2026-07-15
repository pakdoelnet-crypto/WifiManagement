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

let intervalId = null;
onMounted(() => {
    intervalId = setInterval(fetchLiveStats, 30000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
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

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- NOC Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ stats.totalRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Router</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.onlineRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Router Online</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ stats.offlineRouter }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Router Offline</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ stats.highCpu }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">CPU Tinggi</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ stats.highRam }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">RAM Tinggi</div>
                        </div>
                    </div>
                </div>

                <!-- ISP Latency Status & Router Table Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Router Table (Left) -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Live Router Status</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                        <th class="p-3">Nama Router</th>
                                        <th class="p-3">IP Address</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3">Uptime</th>
                                        <th class="p-3 text-right">CPU</th>
                                        <th class="p-3 text-right">RAM</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                    <tr v-for="router in routers" :key="router.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750/30">
                                        <td class="p-3 font-semibold text-gray-900 dark:text-gray-100">{{ router.name }}</td>
                                        <td class="p-3 font-mono text-gray-500">{{ router.host }}</td>
                                        <td class="p-3">
                                            <span
                                                :class="router.status === 'online' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400'"
                                                class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px]"
                                            >
                                                {{ router.status }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-gray-500 font-mono">{{ router.uptime }}</td>
                                        <td class="p-3 text-right font-mono" :class="{ 'text-rose-500 font-bold': router.cpu_load > 80 }">
                                            {{ router.cpu_load }}%
                                        </td>
                                        <td class="p-3 text-right font-mono" :class="{ 'text-rose-500 font-bold': router.ram_usage > 80 }">
                                            {{ router.ram_usage }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ISP Latency Status (Right) -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Ping ISP Gateway & DNS</h3>
                        <div class="space-y-4">
                            <!-- Google -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">Google DNS</div>
                                    <div class="text-[10px] text-gray-400 font-mono">Host: 8.8.8.8</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold">{{ pings.google.latency !== null ? pings.google.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500': pings.google.status === 'green',
                                        'bg-amber-500': pings.google.status === 'yellow',
                                        'bg-rose-500': pings.google.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Cloudflare -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">Cloudflare DNS</div>
                                    <div class="text-[10px] text-gray-400 font-mono">Host: 1.1.1.1</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold">{{ pings.cloudflare.latency !== null ? pings.cloudflare.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500': pings.cloudflare.status === 'green',
                                        'bg-amber-500': pings.cloudflare.status === 'yellow',
                                        'bg-rose-500': pings.cloudflare.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- OpenDNS -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">OpenDNS</div>
                                    <div class="text-[10px] text-gray-400 font-mono">Host: 208.67.222.222</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold">{{ pings.opendns.latency !== null ? pings.opendns.latency + ' ms' : 'Timeout' }}</span>
                                    <div :class="{
                                        'bg-emerald-500': pings.opendns.status === 'green',
                                        'bg-amber-500': pings.opendns.status === 'yellow',
                                        'bg-rose-500': pings.opendns.status === 'red',
                                    }" class="h-2.5 w-2.5 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Gateway ISP -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm text-gray-900 dark:text-gray-100">ISP Gateway</div>
                                    <div class="text-[10px] text-gray-400 font-mono">Target: Router WAN Gateway</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold">{{ pings.gateway.latency }} ms</span>
                                    <div :class="{
                                        'bg-emerald-500': pings.gateway.status === 'green',
                                        'bg-amber-500': pings.gateway.status === 'yellow',
                                        'bg-rose-500': pings.gateway.status === 'red',
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
