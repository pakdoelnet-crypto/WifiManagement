<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    files: Array,
    routers: Array,
});

const isRestoreModalOpen = ref(false);
const selectedRouterId = ref('');

const backupForm = useForm({});
const restoreForm = useForm({
    backup_file: null,
});

const handleFileSelect = (e) => {
    restoreForm.backup_file = e.target.files[0];
};

const triggerDbBackup = () => {
    backupForm.post(route('backups.db'), {
        onSuccess: () => alert('Backup database berhasil dibuat!')
    });
};

const triggerRouterBackup = () => {
    if (!selectedRouterId.value) return;
    backupForm.post(route('backups.router', selectedRouterId.value), {
        onSuccess: () => {
            selectedRouterId.value = '';
            alert('Backup konfigurasi router berhasil dibuat!');
        }
    });
};

const deleteBackup = (filename) => {
    if (confirm(`Apakah Anda yakin ingin menghapus file backup ${filename}?`)) {
        router.delete(route('backups.destroy', filename));
    }
};

const submitRestore = () => {
    if (confirm('PERINGATAN: Melakukan restore akan menimpa seluruh database saat ini dengan data dari file backup. Apakah Anda yakin ingin melanjutkan?')) {
        restoreForm.post(route('backups.restore'), {
            onSuccess: () => {
                isRestoreModalOpen.value = false;
                restoreForm.reset();
                alert('Restore database berhasil!');
            }
        });
    }
};
</script>

<template>
    <Head title="Manajemen Backup" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Pusat Backup & Restore (Database & Router)
                </h2>
                <button
                    @click="isRestoreModalOpen = true"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium text-sm transition shadow-sm border border-slate-700 flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Upload & Restore DB
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Backup triggers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Backup DB -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm mb-2">Backup Database Web</h3>
                            <p class="text-xs text-gray-400 mb-6">Mencadangkan seluruh data transaksi, pelanggan, tagihan, dan log sistem ke dalam format file SQL terkompresi.</p>
                        </div>
                        <PrimaryButton @click="triggerDbBackup" class="w-fit">Mulai Backup Database</PrimaryButton>
                    </div>

                    <!-- Backup Router -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm mb-2">Backup Konfigurasi Router MikroTik</h3>
                            <p class="text-xs text-gray-400 mb-4">Mengunduh seluruh konfigurasi user secret PPPoE dari router MikroTik terpilih ke dalam format script `.rsc`.</p>
                            
                            <div class="mb-4">
                                <select
                                    v-model="selectedRouterId"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                >
                                    <option value="">-- Pilih Router MikroTik --</option>
                                    <option v-for="r in routers" :key="r.id" :value="r.id">{{ r.name }} ({{ r.host }})</option>
                                </select>
                            </div>
                        </div>
                        <PrimaryButton @click="triggerRouterBackup" :disabled="!selectedRouterId" class="w-fit">Mulai Backup Router</PrimaryButton>
                    </div>
                </div>

                <!-- Backups List -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Daftar File Backup Tersimpan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">Nama File</th>
                                    <th class="p-3">Ukuran File</th>
                                    <th class="p-3">Tanggal Dibuat</th>
                                    <th class="p-3 text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                <tr v-if="files.length === 0">
                                    <td colspan="4" class="p-8 text-center text-gray-450">Belum ada file backup terdaftar.</td>
                                </tr>
                                <tr v-for="file in files" :key="file.name" class="hover:bg-gray-50/50 dark:hover:bg-gray-755/20 transition">
                                    <td class="p-3 font-mono font-semibold text-gray-900 dark:text-gray-100">{{ file.name }}</td>
                                    <td class="p-3 font-mono text-gray-500">{{ file.size }}</td>
                                    <td class="p-3 text-gray-400 font-mono">{{ file.created_at }}</td>
                                    <td class="p-3 text-right space-x-2">
                                        <a
                                            :href="route('backups.download', file.name)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-xs font-semibold transition"
                                        >
                                            Download
                                        </a>
                                        <button
                                            @click="deleteBackup(file.name)"
                                            class="inline-flex items-center px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-450 rounded text-xs font-semibold transition"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Modal -->
        <Modal :show="isRestoreModalOpen" @close="isRestoreModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-150 mb-6">Upload & Restore Database (.sql)</h3>
                <form @submit.prevent="submitRestore" class="space-y-4">
                    <div>
                        <InputLabel for="backup_file" value="Pilih File Backup SQL" />
                        <input
                            id="backup_file"
                            type="file"
                            accept=".sql"
                            @change="handleFileSelect"
                            class="mt-2 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                            required
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isRestoreModalOpen = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="restoreForm.processing" class="!bg-rose-600 hover:!bg-rose-700">Mulai Restore DB</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
