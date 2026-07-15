<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object,
});

const formatValues = (values) => {
    if (!values) return 'N/A';
    try {
        if (typeof values === 'string') {
            return values;
        }
        return Object.entries(values)
            .map(([key, val]) => `${key}: ${typeof val === 'object' ? JSON.stringify(val) : val}`)
            .join(', ');
    } catch (e) {
        return 'Data error';
    }
};

const getActionColor = (action) => {
    switch (action) {
        case 'CREATE':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400';
        case 'UPDATE':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-400';
        case 'DELETE':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400';
        case 'DISCONNECT_PPP_SESSION':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400';
    }
};

const getModelName = (modelType) => {
    if (!modelType) return 'N/A';
    return modelType.split('\\').pop();
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Log Aktivitas Staff (Audit Logs)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Activity Logs Card -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 dark:bg-gray-900/40 dark:border-gray-700">
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Pengguna (Staff)</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Entitas</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">ID Objek</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Rincian Perubahan (New Values)</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada riwayat aktivitas log.
                                    </td>
                                </tr>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition text-sm text-gray-700 dark:text-gray-300"
                                >
                                    <!-- User -->
                                    <td class="p-4 font-medium text-gray-900 dark:text-white">
                                        {{ log.user?.name || 'System / Auto' }}
                                    </td>

                                    <!-- Action -->
                                    <td class="p-4">
                                        <span
                                            :class="getActionColor(log.action)"
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        >
                                            {{ log.action }}
                                        </span>
                                    </td>

                                    <!-- Model Type -->
                                    <td class="p-4 font-medium text-indigo-500 dark:text-indigo-400">
                                        {{ getModelName(log.model_type) }}
                                    </td>

                                    <!-- Model ID -->
                                    <td class="p-4 font-mono text-xs">
                                        {{ log.model_id }}
                                    </td>

                                    <!-- Values -->
                                    <td class="p-4 text-xs max-w-xs truncate" :title="formatValues(log.new_values || log.old_values)">
                                        {{ formatValues(log.new_values || log.old_values) }}
                                    </td>

                                    <!-- Created At -->
                                    <td class="p-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 sm:px-6" v-if="logs.links.length > 3">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Component
                                :is="logs.prev_page_url ? 'Link' : 'span'"
                                :href="logs.prev_page_url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750"
                                :class="!logs.prev_page_url ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                Previous
                            </Component>
                            <Component
                                :is="logs.next_page_url ? 'Link' : 'span'"
                                :href="logs.next_page_url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750"
                                :class="!logs.next_page_url ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                Next
                            </Component>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                    Showing <span class="font-medium">{{ logs.from || 0 }}</span> to <span class="font-medium">{{ logs.to || 0 }}</span> of <span class="font-medium">{{ logs.total }}</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <Component
                                        :is="link.url ? 'Link' : 'span'"
                                        v-for="(link, i) in logs.links"
                                        :key="i"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            link.active ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:outline-offset-0',
                                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                        ]"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
