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
    items: Array,
});

const isFormModalOpen = ref(false);
const isAdjustModalOpen = ref(false);
const isEditing = ref(false);
const selectedItem = ref(null);

const form = useForm({
    name: '',
    sku: '',
    category: 'Router',
    unit: 'pcs',
    stock_qty: 0,
    description: '',
});

const adjustForm = useForm({
    type: 'in',
    quantity: '',
    notes: '',
});

const openAddModal = () => {
    isEditing.value = false;
    selectedItem.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    selectedItem.value = item;
    form.clearErrors();

    form.name = item.name;
    form.sku = item.sku;
    form.category = item.category;
    form.unit = item.unit;
    form.stock_qty = item.stock_qty;
    form.description = item.description || '';

    isFormModalOpen.value = true;
};

const openAdjustModal = (item) => {
    selectedItem.value = item;
    adjustForm.reset();
    adjustForm.clearErrors();
    isAdjustModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('inventory.update', selectedItem.value.id), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('inventory.store'), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const submitAdjust = () => {
    adjustForm.post(route('inventory.adjust', selectedItem.value.id), {
        onSuccess: () => {
            isAdjustModalOpen.value = false;
            adjustForm.reset();
        }
    });
};

const deleteItem = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus barang inventori ini?')) {
        form.delete(route('inventory.destroy', id));
    }
};
</script>

<template>
    <Head title="Inventori Gudang" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Inventori & Stok Alat
                </h2>
                <button
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Barang Baru
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Inventory List Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">SKU</th>
                                    <th class="p-3">Nama Barang</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-center">Stok</th>
                                    <th class="p-3">Satuan</th>
                                    <th class="p-3">QR Link</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                                <tr v-if="items.length === 0">
                                    <td colspan="7" class="p-8 text-center text-gray-400">Belum ada barang inventori terdaftar.</td>
                                </tr>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-755/20 transition">
                                    <td class="p-3 font-mono font-bold text-gray-900 dark:text-gray-100">{{ item.sku }}</td>
                                    <td class="p-3">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ item.description || '-' }}</div>
                                    </td>
                                    <td class="p-3 text-indigo-500 font-semibold">{{ item.category }}</td>
                                    <td class="p-3 text-center font-bold font-mono" :class="item.stock_qty <= 2 ? 'text-rose-500' : 'text-gray-800 dark:text-gray-250'">
                                        {{ item.stock_qty }}
                                    </td>
                                    <td class="p-3 text-gray-400 uppercase">{{ item.unit }}</td>
                                    <td class="p-3">
                                        <!-- Render dynamic local barcode simulation link -->
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700/50 rounded font-mono text-[9px] font-bold text-gray-600 dark:text-gray-400">
                                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 11v1m5-6h1m-11 0h1m2-2a2 2 0 114 0v6a2 2 0 11-4 0V8z" />
                                            </svg>
                                            QR-{{ item.sku }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right space-x-2">
                                        <button
                                            @click="openAdjustModal(item)"
                                            class="inline-flex items-center px-2 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded text-xs font-semibold transition"
                                        >
                                            Stok +/-
                                        </button>
                                        <button
                                            @click="openEditModal(item)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-xs font-semibold transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteItem(item.id)"
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

        <!-- Add/Edit Item Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-150 mb-6">
                    {{ isEditing ? 'Edit Data Barang' : 'Tambah Barang Inventori Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Barang" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="sku" value="SKU / Kode Part" />
                            <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full uppercase" required />
                            <InputError :message="form.errors.sku" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="category" value="Kategori Alat" />
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="Router">Router</option>
                                <option value="Switch">Switch</option>
                                <option value="ONU">ONU (Modem)</option>
                                <option value="OLT">OLT</option>
                                <option value="Kabel">Kabel FO</option>
                                <option value="Adaptor">Adaptor</option>
                                <option value="SFP">SFP Module</option>
                                <option value="Splitter">Splitter</option>
                                <option value="Connector">Connector</option>
                            </select>
                            <InputError :message="form.errors.category" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="unit" value="Satuan (Unit)" />
                            <TextInput id="unit" v-model="form.unit" type="text" class="mt-1 block w-full" placeholder="pcs, meter, roll" required />
                            <InputError :message="form.errors.unit" class="mt-2" />
                        </div>
                        <div v-if="!isEditing">
                            <InputLabel for="stock_qty" value="Stok Awal" />
                            <TextInput id="stock_qty" v-model="form.stock_qty" type="number" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.stock_qty" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="description" value="Deskripsi Detail" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isFormModalOpen = false" :disabled="form.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan Barang</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Stock Adjustment Modal -->
        <Modal :show="isAdjustModalOpen" @close="isAdjustModalOpen = false" max-width="sm">
            <div class="p-6">
                <h3 class="text-md font-bold text-gray-900 dark:text-gray-150 mb-6">
                    Penyesuaian Stok: <span class="text-indigo-500">{{ selectedItem?.name }}</span>
                </h3>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <div>
                        <InputLabel for="adj_type" value="Jenis Penyesuaian" />
                        <select
                            id="adj_type"
                            v-model="adjustForm.type"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="in">Stok Masuk (In)</option>
                            <option value="out">Stok Keluar (Out)</option>
                        </select>
                        <InputError :message="adjustForm.errors.type" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="adj_qty" value="Jumlah Pcs/Meter" />
                        <TextInput id="adj_qty" v-model="adjustForm.quantity" type="number" class="mt-1 block w-full" required />
                        <InputError :message="adjustForm.errors.quantity" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="adj_notes" value="Keterangan / Alasan" />
                        <textarea
                            id="adj_notes"
                            v-model="adjustForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            placeholder="Contoh: Pemasangan baru di rumah pelanggan Utami"
                        ></textarea>
                        <InputError :message="adjustForm.errors.notes" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isAdjustModalOpen = false" :disabled="adjustForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="adjustForm.processing">Simpan Penyesuaian</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
