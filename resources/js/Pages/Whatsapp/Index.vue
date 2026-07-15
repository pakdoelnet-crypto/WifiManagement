<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    settings: Object,
    logs: Array,
    customersCount: Number,
});

const activeTab = ref('settings');

const settingsForm = useForm({
    reminder_h_minus_3_active: props.settings.reminder_h_minus_3_active,
    reminder_h_plus_1_active: props.settings.reminder_h_plus_1_active,
    reminder_template: props.settings.reminder_template,
    broadcast_template: props.settings.broadcast_template,
});

const testForm = useForm({
    phone: '',
    message: 'Halo, ini adalah pesan uji coba dari sistem Whatsapp Center PAK DOEL NET.',
});

const broadcastForm = useForm({
    message: '',
});

const saveSettings = () => {
    settingsForm.post(route('whatsapp.settings.update'));
};

const sendTest = () => {
    testForm.post(route('whatsapp.send-manual'), {
        onSuccess: () => testForm.reset('phone')
    });
};

const sendBroadcast = () => {
    if (confirm(`Apakah Anda yakin ingin mengirim pesan broadcast ke seluruh ${props.customersCount} pelanggan aktif?`)) {
        broadcastForm.post(route('whatsapp.broadcast'), {
            onSuccess: () => broadcastForm.reset()
        });
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateStr));
};
</script>

<template>
    <Head title="WhatsApp Center" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                WhatsApp Center Gateway
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Navigation Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700/60 flex gap-4">
                    <button
                        @click="activeTab = 'settings'"
                        :class="activeTab === 'settings' ? 'border-indigo-600 text-indigo-500 font-bold' : 'border-transparent text-gray-400 font-semibold'"
                        class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition"
                    >
                        Setelan Gateway & Template
                    </button>
                    <button
                        @click="activeTab = 'broadcast'"
                        :class="activeTab === 'broadcast' ? 'border-indigo-600 text-indigo-500 font-bold' : 'border-transparent text-gray-400 font-semibold'"
                        class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition"
                    >
                        Kirim Broadcast
                    </button>
                    <button
                        @click="activeTab = 'logs'"
                        :class="activeTab === 'logs' ? 'border-indigo-600 text-indigo-500 font-bold' : 'border-transparent text-gray-400 font-semibold'"
                        class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition"
                    >
                        Log Pengiriman ({{ logs.length }})
                    </button>
                </div>

                <!-- Tab: Settings -->
                <div v-if="activeTab === 'settings'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Config Form -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-6">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Setelan Auto Reminder & Template</h3>
                        <form @submit.prevent="saveSettings" class="space-y-4">
                            <!-- Toggle H-3 & H+1 -->
                            <div class="grid grid-cols-2 gap-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/40">
                                <div>
                                    <InputLabel for="rem_h3" value="Auto Reminder Jatuh Tempo (H-3)" />
                                    <select
                                        id="rem_h3"
                                        v-model="settingsForm.reminder_h_minus_3_active"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    >
                                        <option value="1">Aktif (Kirim H-3)</option>
                                        <option value="0">Non-Aktif</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="rem_h1" value="Auto Reminder Terlambat (H+1)" />
                                    <select
                                        id="rem_h1"
                                        v-model="settingsForm.reminder_h_plus_1_active"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    >
                                        <option value="1">Aktif (Kirim H+1)</option>
                                        <option value="0">Non-Aktif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reminder Template -->
                            <div>
                                <InputLabel for="rem_temp" value="Template Pesan Tagihan (Reminder)" />
                                <div class="text-[10px] text-gray-400 mb-1">Dukung placeholder: {name}, {amount}, {period}, {due_date}, {link}</div>
                                <textarea
                                    id="rem_temp"
                                    v-model="settingsForm.reminder_template"
                                    rows="5"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm font-mono text-xs"
                                    required
                                ></textarea>
                            </div>

                            <!-- Broadcast Template -->
                            <div>
                                <InputLabel for="broad_temp" value="Template Pesan Broadcast" />
                                <div class="text-[10px] text-gray-400 mb-1">Dukung placeholder: {message}, {name}</div>
                                <textarea
                                    id="broad_temp"
                                    v-model="settingsForm.broadcast_template"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm font-mono text-xs"
                                    required
                                ></textarea>
                            </div>

                            <div class="flex justify-end pt-2">
                                <PrimaryButton :disabled="settingsForm.processing">Simpan Setelan</PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Test Form (Right) -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm h-fit">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Uji Coba Pengiriman</h3>
                        <form @submit.prevent="sendTest" class="space-y-4">
                            <div>
                                <InputLabel for="test_phone" value="No. WhatsApp Tujuan" />
                                <TextInput id="test_phone" v-model="testForm.phone" type="text" class="mt-1 block w-full" placeholder="Contoh: 081234567890" required />
                            </div>
                            <div>
                                <InputLabel for="test_msg" value="Isi Pesan" />
                                <textarea
                                    id="test_msg"
                                    v-model="testForm.message"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs"
                                    required
                                ></textarea>
                            </div>
                            <PrimaryButton class="w-full justify-center" :disabled="testForm.processing">Kirim Uji Coba</PrimaryButton>
                        </form>
                    </div>
                </div>

                <!-- Tab: Broadcast -->
                <div v-if="activeTab === 'broadcast'" class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm max-w-2xl mx-auto">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">Kirim Broadcast Massal</h3>
                    <p class="text-xs text-gray-400 mb-6">Pesan ini akan dikirimkan kepada seluruh **{{ customersCount }}** pelanggan aktif di database.</p>

                    <form @submit.prevent="sendBroadcast" class="space-y-4">
                        <div>
                            <InputLabel for="broad_msg" value="Tulis Pesan Pengumuman" />
                            <textarea
                                id="broad_msg"
                                v-model="broadcastForm.message"
                                rows="6"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs"
                                placeholder="Contoh: Info pemeliharaan jaringan pada hari Sabtu..."
                                required
                            ></textarea>
                        </div>

                        <div class="flex justify-end pt-2">
                            <PrimaryButton :disabled="broadcastForm.processing">Kirim ke {{ customersCount }} Pelanggan</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Tab: Logs -->
                <div v-if="activeTab === 'logs'" class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Log Riwayat Pengiriman WhatsApp</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-155 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Nomor Tujuan</th>
                                    <th class="p-3">Isi Pesan</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                <tr v-if="logs.length === 0">
                                    <td colspan="5" class="p-8 text-center text-gray-400">Belum ada riwayat pengiriman log.</td>
                                </tr>
                                <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-755/20 transition">
                                    <td class="p-3 text-gray-400 font-mono">{{ formatDate(log.created_at) }}</td>
                                    <td class="p-3 font-semibold text-gray-850 dark:text-gray-200 font-mono">{{ log.phone_number }}</td>
                                    <td class="p-3 max-w-sm truncate text-gray-500" :title="log.message">{{ log.message }}</td>
                                    <td class="p-3 text-indigo-500 font-semibold uppercase tracking-wider text-[9px]">{{ log.type }}</td>
                                    <td class="p-3 text-right">
                                        <span class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
