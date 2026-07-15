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
    odps: Array,
    customers: Array,
});

const isFormModalOpen = ref(false);
const isAssignModalOpen = ref(false);
const isEditing = ref(false);
const selectedOdp = ref(null);

const form = useForm({
    name: '',
    capacity: 8,
    lat: '',
    lng: '',
    description: '',
});

const assignForm = useForm({
    customer_ids: [],
});

const openAddModal = () => {
    isEditing.value = false;
    selectedOdp.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (odp) => {
    isEditing.value = true;
    selectedOdp.value = odp;
    form.clearErrors();

    form.name = odp.name;
    form.capacity = odp.capacity;
    form.lat = odp.lat || '';
    form.lng = odp.lng || '';
    form.description = odp.description || '';

    isFormModalOpen.value = true;
};

const openAssignModal = (odp) => {
    selectedOdp.value = odp;
    assignForm.reset();
    assignForm.clearErrors();
    
    // Pre-select customers connected to this ODP
    assignForm.customer_ids = props.customers
        .filter(c => c.odp_id === odp.id)
        .map(c => c.id);

    isAssignModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('odp.update', selectedOdp.value.id), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('odp.store'), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const submitAssign = () => {
    assignForm.post(route('odp.assign', selectedOdp.value.id), {
        onSuccess: () => {
            isAssignModalOpen.value = false;
            assignForm.reset();
        }
    });
};

const deleteOdp = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus ODP ini?')) {
        form.delete(route('odp.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen ODP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Kotak Distribusi ODP (Optical Distribution Point)
                </h2>
                <button
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah ODP Baru
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- ODP List Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">Nama ODP</th>
                                    <th class="p-3 text-center">Kapasitas Port</th>
                                    <th class="p-3 text-center">Port Terpakai</th>
                                    <th class="p-3 text-center">Sisa Port</th>
                                    <th class="p-3">Koordinat Lokasi</th>
                                    <th class="p-3">Deskripsi</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                <tr v-if="odps.length === 0">
                                    <td colspan="7" class="p-8 text-center text-gray-400">Belum ada ODP terdaftar.</td>
                                </tr>
                                <tr v-for="odp in odps" :key="odp.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-755/20 transition">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-gray-100">{{ odp.name }}</td>
                                    <td class="p-3 text-center font-mono font-bold">{{ odp.capacity }} Port</td>
                                    <td class="p-3 text-center font-mono font-bold text-indigo-500">{{ odp.used_ports }} Port</td>
                                    <td class="p-3 text-center font-mono font-bold" :class="odp.remaining_ports === 0 ? 'text-rose-500' : 'text-emerald-500'">
                                        {{ odp.remaining_ports }} Port
                                    </td>
                                    <td class="p-3 font-mono text-gray-500">
                                        <div v-if="odp.lat && odp.lng" class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ odp.lat }}, {{ odp.lng }}
                                        </div>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="p-3 text-gray-500">{{ odp.description || '-' }}</td>
                                    <td class="p-3 text-right space-x-2">
                                        <button
                                            @click="openAssignModal(odp)"
                                            class="inline-flex items-center px-2 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded text-xs font-semibold transition"
                                        >
                                            Petakan User
                                        </button>
                                        <button
                                            @click="openEditModal(odp)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-xs font-semibold transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteOdp(odp.id)"
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

        <!-- Add/Edit ODP Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-150 mb-6">
                    {{ isEditing ? 'Edit Data ODP' : 'Tambah ODP Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama ODP" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Contoh: ODP-MANGIR-01" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="capacity" value="Kapasitas Port" />
                            <select
                                id="capacity"
                                v-model="form.capacity"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option :value="8">8 Port</option>
                                <option :value="16">16 Port</option>
                                <option :value="24">24 Port</option>
                            </select>
                            <InputError :message="form.errors.capacity" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="lat" value="Latitude" />
                            <TextInput id="lat" v-model="form.lat" type="number" step="0.000001" class="mt-1 block w-full font-mono" placeholder="-6.200000" />
                            <InputError :message="form.errors.lat" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="lng" value="Longitude" />
                            <TextInput id="lng" v-model="form.lng" type="number" step="0.000001" class="mt-1 block w-full font-mono" placeholder="106.816666" />
                            <InputError :message="form.errors.lng" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="description" value="Deskripsi / Catatan Lokasi" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            placeholder="Contoh: Di tiang listrik depan pertigaan warung Mbak Utami"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isFormModalOpen = false" :disabled="form.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan ODP</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Assign Customers Modal -->
        <Modal :show="isAssignModalOpen" @close="isAssignModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-md font-bold text-gray-900 dark:text-gray-150 mb-6">
                    Petakan Pelanggan ke ODP: <span class="text-indigo-500 font-mono">{{ selectedOdp?.name }}</span>
                </h3>

                <form @submit.prevent="submitAssign" class="space-y-4">
                    <div class="max-h-[300px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-2">
                        <div v-for="c in customers" :key="c.id" class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :id="'cust_' + c.id"
                                :value="c.id"
                                v-model="assignForm.customer_ids"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label :for="'cust_' + c.id" class="text-xs text-gray-750 dark:text-gray-300 font-medium">
                                {{ c.name }} <span v-if="c.odp_id" class="text-[9px] font-bold text-gray-400 font-mono">({{ c.odp_id === selectedOdp?.id ? 'Saat ini' : 'ODP Lain' }})</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isAssignModalOpen = false" :disabled="assignForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="assignForm.processing">Simpan Pemetaan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
