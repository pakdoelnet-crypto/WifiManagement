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
    stats: Object,
    expenses: Array,
    chartData: Object,
});

const isFormModalOpen = ref(false);

const form = useForm({
    category: '',
    amount: '',
    description: '',
    expense_date: new Date().toISOString().substr(0, 10),
});

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateStr));
};

const submitExpense = () => {
    form.post(route('finance.expenses.store'), {
        onSuccess: () => {
            isFormModalOpen.value = false;
            form.reset();
        }
    });
};

const deleteExpense = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?')) {
        form.delete(route('finance.expenses.destroy', id));
    }
};

// Calculate SVG Chart dimensions
const getMaxVal = () => {
    const vals = [...props.chartData.revenue, ...props.chartData.expense];
    const max = Math.max(...vals, 100000);
    return max * 1.1; // 10% padding
};

const maxVal = getMaxVal();
</script>

<template>
    <Head title="Keuangan & Ledger" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard Keuangan
                </h2>
                <button
                    @click="isFormModalOpen = true"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Catat Pengeluaran
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Pendapatan Hari Ini -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Pendapatan Hari Ini</div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatRupiah(stats.pendapatanHariIni) }}</div>
                    </div>

                    <!-- Pendapatan Bulan Ini -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Pendapatan Bulan Ini</div>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ formatRupiah(stats.pendapatanBulanIni) }}</div>
                    </div>

                    <!-- Laba Bersih -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Laba Bersih Bulan Ini</div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatRupiah(stats.labaBersih) }}</div>
                    </div>

                    <!-- Pengeluaran Bulan Ini -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Pengeluaran Bulan Ini</div>
                        <div class="text-xl font-bold text-rose-600 dark:text-rose-400">{{ formatRupiah(stats.pengeluaranBulanIni) }}</div>
                    </div>

                    <!-- Total Piutang -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Total Piutang (Belum Bayar)</div>
                        <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ formatRupiah(stats.totalPiutang) }}</div>
                    </div>

                    <!-- Invoice Belum Dibayar -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm col-span-1 md:col-span-2">
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Jumlah Piutang Invoice</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ stats.invoiceBelumDibayarCount }} Invoice</div>
                    </div>
                </div>

                <!-- Monthly Revenue & Expense Graph -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-6">Grafik Arus Kas 6 Bulan Terakhir</h3>
                    
                    <!-- SVG Chart Container -->
                    <div class="h-64 w-full relative flex items-end justify-between px-6 pt-4 border-b border-gray-150 dark:border-gray-700">
                        <div v-for="(label, idx) in chartData.labels" :key="idx" class="flex-1 flex flex-col items-center gap-2 group h-full justify-end relative">
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 bg-slate-900 text-white text-[10px] p-2 rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition z-30 shadow-md">
                                <p class="font-bold text-indigo-400">Pemasukan: {{ formatRupiah(chartData.revenue[idx]) }}</p>
                                <p class="font-bold text-rose-400">Pengeluaran: {{ formatRupiah(chartData.expense[idx]) }}</p>
                            </div>

                            <!-- Comparative Bars -->
                            <div class="flex items-end gap-1.5 w-full justify-center h-[90%]">
                                <div
                                    :style="{ height: (chartData.revenue[idx] / maxVal * 100) + '%' }"
                                    class="w-4 sm:w-6 bg-indigo-600 dark:bg-indigo-500 rounded-t shadow-sm transition-all duration-500"
                                ></div>
                                <div
                                    :style="{ height: (chartData.expense[idx] / maxVal * 100) + '%' }"
                                    class="w-4 sm:w-6 bg-rose-600 dark:bg-rose-500 rounded-t shadow-sm transition-all duration-500"
                                ></div>
                            </div>

                            <!-- Label -->
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider truncate max-w-full">
                                {{ label }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-4 justify-center mt-4 text-[10px] uppercase font-bold tracking-wider">
                        <div class="flex items-center gap-1 text-indigo-500">
                            <span class="h-3 w-3 bg-indigo-600 dark:bg-indigo-500 rounded"></span>
                            Pendapatan (Lunas)
                        </div>
                        <div class="flex items-center gap-1 text-rose-500">
                            <span class="h-3 w-3 bg-rose-600 dark:bg-rose-500 rounded"></span>
                            Pengeluaran / Beban
                        </div>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">Catatan Jurnal Pengeluaran</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-700/50 text-gray-400 font-bold">
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3">Deskripsi</th>
                                    <th class="p-3 text-right">Jumlah</th>
                                    <th class="p-3 text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-755">
                                <tr v-if="expenses.length === 0">
                                    <td colspan="5" class="p-8 text-center text-gray-400">Belum ada catatan pengeluaran.</td>
                                </tr>
                                <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750/30">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-gray-150">{{ formatDate(expense.expense_date) }}</td>
                                    <td class="p-3 text-indigo-500 font-semibold">{{ expense.category }}</td>
                                    <td class="p-3 text-gray-500">{{ expense.description || '-' }}</td>
                                    <td class="p-3 text-right font-bold text-rose-600 dark:text-rose-400">{{ formatRupiah(expense.amount) }}</td>
                                    <td class="p-3 text-right">
                                        <button
                                            @click="deleteExpense(expense.id)"
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

        <!-- Add Expense Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-155 mb-6">Catat Pengeluaran Operasional</h3>

                <form @submit.prevent="submitExpense" class="space-y-4">
                    <div>
                        <InputLabel for="category" value="Kategori Pengeluaran" />
                        <select
                            id="category"
                            v-model="form.category"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Operational">Operasional Kantor</option>
                            <option value="Marketing">Pemasaran / Iklan</option>
                            <option value="Gaji Staff">Gaji & Bonus Staff</option>
                            <option value="Beli Bandwidth">Pembayaran Bandwidth ISP</option>
                            <option value="Inventori">Beli Alat Inventori (ODP, OLT, dll)</option>
                            <option value="Lainnya">Lain-lain</option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="amount" value="Jumlah Pengeluaran (IDR)" />
                        <TextInput id="amount" v-model="form.amount" type="number" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="expense_date" value="Tanggal Pengeluaran" />
                        <TextInput id="expense_date" v-model="form.expense_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.expense_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Deskripsi / Catatan" />
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
                        <PrimaryButton :disabled="form.processing">Simpan Pengeluaran</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
