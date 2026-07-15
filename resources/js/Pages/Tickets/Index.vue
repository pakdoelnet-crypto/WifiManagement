<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    tickets: Array,
    customers: Array,
    technicians: Array,
});

const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingTicketId = ref(null);

const form = useForm({
    customer_id: '',
    category: '',
    priority: 'medium',
    assigned_user_id: '',
    status: 'open',
    notes: '',
    photo: null,
});

const handlePhotoUpload = (e) => {
    form.photo = e.target.files[0];
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateStr));
};

const openAddModal = () => {
    isEditing.value = false;
    editingTicketId.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (ticket) => {
    isEditing.value = true;
    editingTicketId.value = ticket.id;
    form.clearErrors();

    form.customer_id = ticket.customer_id;
    form.category = ticket.category;
    form.priority = ticket.priority;
    form.assigned_user_id = ticket.assigned_user_id || '';
    form.status = ticket.status;
    form.notes = ticket.notes || '';
    form.photo = null;

    isFormModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        // multipart/form-data edit update requires POST in Laravel with file upload
        form.post(route('tickets.update', editingTicketId.value), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('tickets.store'), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteTicket = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus ticket gangguan ini?')) {
        form.delete(route('tickets.destroy', id));
    }
};
</script>

<template>
    <Head title="Ticket Gangguan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Laporan Gangguan (Trouble Ticket)
                </h2>
                <button
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Ticket Baru
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Tickets List Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">No. Ticket</th>
                                    <th class="p-3">Pelanggan</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-center">Prioritas</th>
                                    <th class="p-3">Teknisi</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Tanggal Lapor</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                <tr v-if="tickets.length === 0">
                                    <td colspan="8" class="p-8 text-center text-gray-400">Belum ada ticket gangguan terdaftar.</td>
                                </tr>
                                <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-755/20 transition">
                                    <td class="p-3 font-mono font-bold text-gray-900 dark:text-gray-100">{{ ticket.ticket_number }}</td>
                                    <td class="p-3">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ ticket.customer ? ticket.customer.name : 'Pelanggan Terhapus' }}</div>
                                        <div class="text-[10px] text-gray-400">WA: {{ ticket.customer ? ticket.customer.whatsapp : '-' }}</div>
                                    </td>
                                    <td class="p-3 text-indigo-500 font-semibold">{{ ticket.category }}</td>
                                    <td class="p-3 text-center">
                                        <span
                                            :class="{
                                                'bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400': ticket.priority === 'high',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400': ticket.priority === 'medium',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-400': ticket.priority === 'low',
                                            }"
                                            class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px]"
                                        >
                                            {{ ticket.priority }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-500 font-semibold">{{ ticket.assigned_user ? ticket.assigned_user.name : '-' }}</td>
                                    <td class="p-3">
                                        <span
                                            :class="{
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': ticket.status === 'selesai',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': ticket.status === 'diproses',
                                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-450': ticket.status === 'open',
                                            }"
                                            class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px]"
                                        >
                                            {{ ticket.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-400 font-mono">{{ formatDate(ticket.reported_at) }}</td>
                                    <td class="p-3 text-right space-x-2">
                                        <button
                                            @click="openEditModal(ticket)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-xs font-semibold transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteTicket(ticket.id)"
                                            class="inline-flex items-center px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded text-xs font-semibold transition"
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

        <!-- Add/Edit Ticket Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" max-width="lg">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-150 mb-6">
                    {{ isEditing ? 'Update Ticket Gangguan' : 'Buat Ticket Gangguan Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div v-if="!isEditing">
                        <InputLabel for="customer_id" value="Pilih Pelanggan" />
                        <select
                            id="customer_id"
                            v-model="form.customer_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="">-- Pilih Pelanggan --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} (WA: {{ c.whatsapp }})</option>
                        </select>
                        <InputError :message="form.errors.customer_id" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="category" value="Kategori Gangguan" />
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Kabel Putus">Kabel Putus (LOS)</option>
                                <option value="Wifi Lemot">Koneksi Lambat / Lemot</option>
                                <option value="Redaman Tinggi">Redaman Tinggi / Drop</option>
                                <option value="Router Mati">Router / Adaptor Mati</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                            <InputError :message="form.errors.category" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="priority" value="Tingkat Prioritas" />
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="low">Low (Rendah)</option>
                                <option value="medium">Medium (Sedang)</option>
                                <option value="high">High (Tinggi / Urgent)</option>
                            </select>
                            <InputError :message="form.errors.priority" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="assigned_user_id" value="Pilih Teknisi Lapangan" />
                            <select
                                id="assigned_user_id"
                                v-model="form.assigned_user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            >
                                <option value="">-- Belum Ditugaskan --</option>
                                <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <InputError :message="form.errors.assigned_user_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status Pengerjaan" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="open">Open (Baru)</option>
                                <option value="diproses">Diproses (Teknisi Jalan)</option>
                                <option value="selesai">Selesai (Resolved)</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="photo" value="Upload Foto Gangguan / Bukti Lapangan" />
                        <input
                            id="photo"
                            type="file"
                            accept="image/*"
                            @change="handlePhotoUpload"
                            class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                        />
                        <InputError :message="form.errors.photo" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Catatan / Tindakan Pengerjaan" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isFormModalOpen = false" :disabled="form.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan Ticket</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
