<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    sla: Object,
    chartData: Object,
});

// Helper to generate SVG polyline path coordinates
const getSparklinePoints = (arr, max = 100) => {
    const width = 600;
    const height = 120;
    const step = width / (arr.length - 1);
    
    // Find min and max for scaling
    const minVal = Math.min(...arr);
    const maxVal = Math.max(...arr);
    const range = (maxVal - minVal) || 1;
    
    const points = arr.map((val, idx) => {
        const x = idx * step;
        const y = height - ((val - minVal) / range * (height - 20)) - 10;
        return `${x},${y}`;
    });
    return points.join(' ');
};
</script>

<template>
    <Head title="SLA Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard SLA & Kualitas Layanan (SLA Dashboard)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- SLA Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Uptime SLA -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rata-rata Uptime (SLA)</div>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ sla.uptime }}%</div>
                    </div>

                    <!-- Latency -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rata-rata Latensi (ISP)</div>
                        <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ sla.latency }} ms</div>
                    </div>

                    <!-- Response Time -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rata-rata Response Teknisi</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ sla.avgResponseText }}</div>
                    </div>

                    <!-- Ping -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Ping Google DNS</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100 font-mono">{{ sla.ping }} ms</div>
                    </div>

                    <!-- Packet Loss -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Packet Loss Jaringan</div>
                        <div class="text-xl font-bold font-mono" :class="sla.packetLoss > 1 ? 'text-rose-500' : 'text-emerald-500'">
                            {{ sla.packetLoss }}%
                        </div>
                    </div>

                    <!-- Gangguan Hari Ini -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Gangguan Hari Ini</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ sla.gangguanToday }} Laporan</div>
                    </div>
                </div>

                <!-- SLA History Graph -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-6">Uptime SLA History (7 Hari Terakhir)</h3>
                    
                    <div class="h-44 w-full relative flex items-end justify-between border-b border-gray-150 dark:border-gray-700/60 pb-2">
                        <svg class="h-full w-full absolute inset-0 z-10" viewBox="0 0 600 120" preserveAspectRatio="none">
                            <polyline
                                fill="none"
                                stroke="#10b981"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :points="getSparklinePoints(chartData.uptime)"
                            />
                        </svg>
                        
                        <div v-for="(label, idx) in chartData.labels" :key="idx" class="flex-1 flex flex-col items-center justify-end h-full z-20">
                            <!-- Tooltip/Value representation -->
                            <span class="text-[9px] font-mono text-emerald-500 font-bold bg-white dark:bg-gray-800 px-1 py-0.5 rounded border border-gray-200 dark:border-gray-700 shadow-sm mb-1">
                                {{ chartData.uptime[idx].toFixed(2) }}%
                            </span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mt-2">
                                {{ label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
