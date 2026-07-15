<script setup>
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};
import html2canvas from 'html2canvas';

const props = defineProps({
    invoices: {
        type: Object,
        required: true,
    },
    customers: {
        type: Array,
        required: true,
    },
    periodes: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

// Filter states
const search = ref(props.filters.search || '');
const customerId = ref(props.filters.customer_id || '');
const status = ref(props.filters.status || '');
const periodFilter = ref(props.filters.periode || '');

const page = usePage();
const canManage = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('Super Admin') || roles.includes('Owner') || roles.includes('Admin') || roles.includes('Kasir');
});

// Modal state
const isModalOpen = ref(false);
const activeInvoice = ref(null);

const formatRupiah = (value) => {
    if (value === null || value === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
};

const formatPeriod = (periodStr) => {
    if (!periodStr || periodStr.length !== 6) return periodStr;
    const year = periodStr.substring(0, 4);
    const monthIndex = parseInt(periodStr.substring(4, 6)) - 1;
    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${monthNames[monthIndex]} ${year}`;
};

const formatKbpsToMbps = (kbps) => {
    if (!kbps) return '-';
    const mbps = kbps / 1024;
    return `${mbps} Mbps`;
};

// Debounce filtering
const applyFilters = debounce(() => {
    router.get(
        route('invoices.index'),
        {
            search: search.value,
            customer_id: customerId.value,
            status: status.value,
            periode: periodFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}, 300);

watch([search, customerId, status, periodFilter], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    customerId.value = '';
    status.value = '';
    periodFilter.value = '';
};

// Open detail modal
const showInvoiceDetail = (invoice) => {
    activeInvoice.value = invoice;
    isModalOpen.value = true;
};

// Download as image (PNG/JPG) using html2canvas
const isDownloading = ref(false);
const downloadAsImage = async (format) => {
    if (!activeInvoice.value) return;
    
    isDownloading.value = true;
    
    // We capture the #invoice-capture-card element
    const element = document.getElementById('invoice-capture-card');
    if (!element) {
        isDownloading.value = false;
        return;
    }

    try {
        // High quality scale: 3x resolution
        const canvas = await html2canvas(element, {
            scale: 3,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
        });

        const fileExtension = format.toLowerCase();
        let dataUrl;
        
        if (fileExtension === 'png') {
            dataUrl = canvas.toDataURL('image/png');
        } else {
            // JPG needs white canvas explicitly (which we set via backgroundColor)
            dataUrl = canvas.toDataURL('image/jpeg', 0.95);
        }

        const link = document.createElement('a');
        link.download = `Invoice-${activeInvoice.value.invoice_number}.${fileExtension}`;
        link.href = dataUrl;
        link.click();
    } catch (e) {
        console.error('Failed to convert HTML to Image:', e);
    } finally {
        isDownloading.value = false;
    }
};

// Print Invoice
const printInvoice = () => {
    window.print();
};

// Automated Billing & Payments States & Methods
const isGenerateModalOpen = ref(false);
const generatePeriod = ref('');
const isGenerating = ref(false);

const submitGenerateInvoices = () => {
    if (!generatePeriod.value) return;
    isGenerating.value = true;
    router.post(route('invoices.generate'), {
        periode: generatePeriod.value.replace('-', ''),
    }, {
        onSuccess: () => {
            isGenerateModalOpen.value = false;
            isGenerating.value = false;
            generatePeriod.value = '';
        },
        onError: () => {
            isGenerating.value = false;
        }
    });
};

const isPayModalOpen = ref(false);
const payInvoiceItem = ref(null);
const paymentMethod = ref('Tunai');
const paymentNotes = ref('');
const isProcessingPayment = ref(false);

const showPayModal = (invoice) => {
    payInvoiceItem.value = invoice;
    paymentMethod.value = 'Tunai';
    paymentNotes.value = '';
    isPayModalOpen.value = true;
};

const submitPayment = () => {
    if (!payInvoiceItem.value) return;
    isProcessingPayment.value = true;
    router.post(route('invoices.pay', payInvoiceItem.value.id), {
        payment_method: paymentMethod.value,
        notes: paymentNotes.value,
    }, {
        onSuccess: () => {
            isPayModalOpen.value = false;
            isProcessingPayment.value = false;
            payInvoiceItem.value = null;
        },
        onError: () => {
            isProcessingPayment.value = false;
        }
    });
};

const sendWhatsAppReminder = (invoice) => {
    if (!invoice || !invoice.customer) return;
    let phone = invoice.customer.whatsapp.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    const customerName = invoice.customer.name;
    const invoiceNum = invoice.invoice_number;
    const amountStr = formatRupiah(invoice.total_amount || invoice.amount);
    const dueDateStr = formatDate(invoice.due_date);
    const periodStr = formatPeriod(invoice.periode);
    
    // Generate public link for invoice
    const publicUrl = `${window.location.origin}/invoices/${invoiceNum}/public`;
    
    const message = `Halo Kak *${customerName}*,\n\nKami dari *PAK DOEL NET* menginfokan bahwa tagihan internet RTRW Net Anda untuk periode *${periodStr}* dengan No. Nota *${invoiceNum}* sebesar *${amountStr}* telah diterbitkan.\n\nAnda dapat melihat detail nota dan mendownload gambar JPG langsung melalui link berikut:\n🔗 ${publicUrl}\n\nTagihan jatuh tempo pada *${dueDateStr}*.\n\nSilakan lakukan pembayaran tunai ke loket atau transfer bank sebelum jatuh tempo demi kelancaran koneksi internet Anda.\n\nTerima kasih. 🙏`;
    
    const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Invoice Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Cetak & Download Invoice
                </h2>
                <div class="flex items-center gap-2.5">
                    <button
                        v-if="$page.props.auth.user"
                        @click="isGenerateModalOpen = true"
                        class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                    >
                        + Generate Tagihan Bulanan
                    </button>
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-xl shadow-sm transition"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Filters Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/50 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search input -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Cari Pelanggan/No Invoice</label>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Ketik nama / username / invoice..."
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    />
                </div>

                <!-- Customer dropdown filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Filter Pelanggan</label>
                    <select
                        v-model="customerId"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    >
                        <option value="">Semua Pelanggan</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">
                            {{ c.name }} ({{ c.pppoe_username }})
                        </option>
                    </select>
                </div>

                <!-- Period filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Periode Tagihan</label>
                    <select
                        v-model="periodFilter"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    >
                        <option value="">Semua Periode</option>
                        <option v-for="p in periodes" :key="p" :value="p">
                            {{ formatPeriod(p) }}
                        </option>
                    </select>
                </div>

                <!-- Status filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Status Pembayaran</label>
                    <select
                        v-model="status"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                    >
                        <option value="">Semua Status</option>
                        <option value="paid">Lunas</option>
                        <option value="unpaid">Belum Lunas</option>
                        <option value="overdue">Terlambat</option>
                    </select>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-150 dark:border-gray-700/50">
                                <th class="px-6 py-4">Nomor Invoice</th>
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Username PPPoE</th>
                                <th class="px-6 py-4">Periode</th>
                                <th class="px-6 py-4">Total Tagihan</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-700/50 text-sm">
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300 transition"
                            >
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white font-mono text-xs">
                                    {{ invoice.invoice_number }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ invoice.customer ? invoice.customer.name : 'Pelanggan Tidak Terdaftar' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">
                                    {{ invoice.customer ? invoice.customer.pppoe_username : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatPeriod(invoice.periode) }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ formatRupiah(invoice.total_amount || invoice.amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="invoice.status === 'paid'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 uppercase tracking-wider"
                                    >
                                        Lunas
                                    </span>
                                    <span
                                        v-else-if="invoice.status === 'unpaid'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 uppercase tracking-wider"
                                    >
                                        Belum Lunas
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400 uppercase tracking-wider"
                                    >
                                        Terlambat
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                    <button
                                        v-if="invoice.status !== 'paid'"
                                        @click="showPayModal(invoice)"
                                        class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                                    >
                                        Bayar
                                    </button>
                                    <button
                                        v-if="invoice.status !== 'paid' && invoice.customer"
                                        @click="sendWhatsAppReminder(invoice)"
                                        class="inline-flex items-center px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl text-xs font-bold transition shadow-sm"
                                    >
                                        WA
                                    </button>
                                    <button
                                        @click="showInvoiceDetail(invoice)"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-xl text-xs font-bold transition shadow-sm"
                                    >
                                        Detail
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="7" class="text-center py-10 text-gray-400 dark:text-gray-500 font-medium">
                                    Tidak ada data invoice ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="invoices.links.length > 3" class="px-6 py-4 bg-gray-50 dark:bg-gray-900/10 border-t border-gray-150 dark:border-gray-700/50 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ invoices.from || 0 }} sampai {{ invoices.to || 0 }} dari {{ invoices.total }} baris
                    </div>
                    <div class="flex space-x-1">
                        <Component
                            :is="link.url ? 'Link' : 'span'"
                            v-for="(link, i) in invoices.links"
                            :key="i"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            class="px-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition"
                        />
                    </div>
                </div>
            </div>

            <!-- Detail & Print Invoice Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="2xl">
                <div v-if="activeInvoice" class="p-6 space-y-6">
                    
                    <!-- Modal Header (Tindakan/Download Buttons) -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-150 dark:border-gray-700 pb-4 no-print">
                        <h4 class="text-md font-bold text-gray-900 dark:text-gray-100">Detail Nota Pembayaran</h4>
                        <div class="flex items-center gap-2">
                            <button
                                @click="downloadAsImage('PNG')"
                                :disabled="isDownloading"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                PNG
                            </button>
                            <button
                                @click="downloadAsImage('JPG')"
                                :disabled="isDownloading"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                JPG
                            </button>
                            <button
                                @click="printInvoice"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-lg transition flex items-center gap-1.5"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak
                            </button>
                            <button
                                @click="isModalOpen = false"
                                class="px-3 py-1.5 bg-gray-250 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-400 text-xs font-bold rounded-lg transition"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>

                    <!-- Capture & Print Area -->
                    <div id="print-area" class="bg-slate-50 dark:bg-slate-900/50 p-2 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/30">
                        <div
                            id="invoice-capture-card"
                            class="bg-white p-8 max-w-2xl mx-auto shadow-sm border border-gray-200 text-slate-800 font-sans leading-normal relative overflow-hidden"
                            style="min-height: 800px; color: #1e293b; background-color: #ffffff; border-color: #e2e8f0;"
                        >
                            <!-- Watermark Background for Premium Feel -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
                                <span class="text-7xl font-extrabold rotate-45 tracking-widest uppercase">PAK DOEL NET</span>
                            </div>

                            <!-- Header Section -->
                            <div class="flex items-start justify-between border-b-2 border-slate-100 pb-6 mb-6">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <!-- Application Brand Logo/Text -->
                                        <img src="/images/logo.jpg" alt="PAK DOEL NET" class="h-9 w-9 rounded-full object-cover shrink-0 shadow-sm" />
                                        <span class="text-xl font-black text-slate-900 tracking-wider">PAK DOEL NET</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 space-y-0.5">
                                        <p>RTRW Net Internet Service Provider</p>
                                        <p>Kepanjen, Kabupaten Malang, Jawa Timur</p>
                                        <p>WhatsApp: 0812-3456-7890 | Email: support@pakdoel.net</p>
                                    </div>
                                </div>
                                <div class="text-right space-y-1">
                                    <h1 class="text-2xl font-black text-indigo-600 uppercase tracking-widest">NO. NOTA</h1>
                                    <p class="font-mono text-xs font-bold text-slate-800">{{ activeInvoice.invoice_number }}</p>
                                    <div class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider mt-2"
                                         :style="[
                                             activeInvoice.status === 'paid'
                                                 ? { backgroundColor: '#e6fffa', color: '#047481' }
                                                 : (activeInvoice.status === 'unpaid' ? { backgroundColor: '#fffaf0', color: '#b7791f' } : { backgroundColor: '#fff5f5', color: '#c53030' })
                                         ]"
                                    >
                                        {{ activeInvoice.status === 'paid' ? 'LUNAS' : (activeInvoice.status === 'unpaid' ? 'BELUM LUNAS' : 'TERLAMBAT') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Customer & General Info Grid -->
                            <div class="grid grid-cols-2 gap-8 mb-8 text-xs">
                                <div>
                                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ditagih Kepada:</h3>
                                    <div class="space-y-1">
                                        <p class="font-bold text-slate-900 text-sm">{{ activeInvoice.customer ? activeInvoice.customer.name : 'Pelanggan Terhapus' }}</p>
                                        <p class="text-slate-600 line-clamp-2">{{ activeInvoice.customer ? activeInvoice.customer.address : '-' }}</p>
                                        <p class="text-slate-600">WhatsApp: {{ activeInvoice.customer ? activeInvoice.customer.whatsapp : '-' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Rincian Periode & Tanggal:</h3>
                                    <div class="space-y-1 text-slate-600">
                                        <p><span class="font-semibold text-slate-500">Periode Layanan:</span> <span class="font-bold text-slate-900">{{ formatPeriod(activeInvoice.periode) }}</span></p>
                                        <p><span class="font-semibold text-slate-500">Tanggal Terbit:</span> {{ formatDate(activeInvoice.created_at) }}</p>
                                        <p><span class="font-semibold text-slate-500">Jatuh Tempo:</span> <span class="font-bold text-slate-900">{{ formatDate(activeInvoice.due_date) }}</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Table -->
                            <div class="border border-slate-100 rounded-xl overflow-hidden mb-8 text-xs">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold">
                                            <th class="px-4 py-3">Deskripsi Layanan</th>
                                            <th class="px-4 py-3 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="px-4 py-4">
                                                <p class="font-bold text-slate-900">
                                                    {{ activeInvoice.customer && activeInvoice.customer.package ? activeInvoice.customer.package.name : 'Paket Layanan Internet' }}
                                                </p>
                                                <p class="text-[10px] text-slate-400">Bulanan RTRW Net internet berlangganan</p>
                                            </td>
                                            <td class="px-4 py-4 text-right font-bold text-slate-900">
                                                {{ formatRupiah(activeInvoice.amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary & Payment Info Grid -->
                            <div class="grid grid-cols-2 gap-8 mb-8 text-xs">
                                <!-- Payment details if paid -->
                                <div>
                                    <div v-if="activeInvoice.status === 'paid' && activeInvoice.payment" class="bg-slate-50 p-4 rounded-xl space-y-1.5 border border-slate-100">
                                        <h4 class="font-bold text-slate-900 text-[10px] uppercase tracking-wider">Informasi Pelunasan:</h4>
                                        <p class="text-slate-600"><span class="font-medium text-slate-400">Tgl Bayar:</span> {{ formatDate(activeInvoice.payment.payment_date) }}</p>
                                        <p class="text-slate-600"><span class="font-medium text-slate-400">Metode:</span> {{ activeInvoice.payment.payment_method }}</p>
                                        <p v-if="activeInvoice.payment.notes" class="text-slate-600"><span class="font-medium text-slate-400">Catatan:</span> {{ activeInvoice.payment.notes }}</p>
                                    </div>
                                    <div v-else class="bg-rose-50/50 p-4 rounded-xl border border-rose-100 text-rose-800 space-y-1 flex flex-col justify-center min-h-[90px]">
                                        <p class="font-bold uppercase tracking-wider text-[10px]">Peringatan Pembayaran:</p>
                                        <p class="text-[10px] leading-relaxed">Silakan lakukan transfer atau bayar tunai ke loket resmi RTRW Net sebelum tanggal jatuh tempo berakhir demi kelancaran internet Anda.</p>
                                    </div>
                                </div>

                                <!-- Summary calculation -->
                                <div class="space-y-2 text-right">
                                    <div class="flex justify-between text-slate-600">
                                        <span>Biaya Paket:</span>
                                        <span>{{ formatRupiah(activeInvoice.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span>Denda Keterlambatan (+):</span>
                                        <span>{{ formatRupiah(activeInvoice.penalty_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span>Diskon Potongan (-):</span>
                                        <span>{{ formatRupiah(activeInvoice.discount_amount) }}</span>
                                    </div>
                                    <div class="border-t-2 border-slate-100 pt-2 flex justify-between font-black text-slate-900 text-sm">
                                        <span class="text-indigo-600 text-xs">TOTAL BAYAR:</span>
                                        <span class="text-lg text-indigo-600">{{ formatRupiah(activeInvoice.total_amount || activeInvoice.amount) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Section -->
                            <div class="border-t border-slate-100 pt-6 mt-auto flex items-center justify-between text-[10px] text-slate-400">
                                <div>
                                    <p>Dicetak pada: {{ formatDate(new Date()) }}</p>
                                </div>
                                <div class="text-right">
                                    <p>Invoice ini dibuat otomatis oleh sistem PAK DOEL NET</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </Modal>

            <!-- Modal Generate Tagihan -->
            <Modal :show="isGenerateModalOpen" @close="isGenerateModalOpen = false" max-width="md">
                <div class="p-6 space-y-4">
                    <h3 class="text-md font-bold text-gray-950 dark:text-gray-100">
                        Generate Tagihan Bulanan
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Sistem akan otomatis membuat invoice tagihan baru untuk semua pelanggan berstatus <strong>Aktif</strong> yang memiliki paket internet terdaftar.
                    </p>
                    
                    <form @submit.prevent="submitGenerateInvoices" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Pilih Bulan & Tahun Periode</label>
                            <input
                                type="month"
                                v-model="generatePeriod"
                                required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="isGenerateModalOpen = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isGenerating || !generatePeriod"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition disabled:opacity-50"
                            >
                                {{ isGenerating ? 'Memproses...' : 'Mulai Generate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Modal Bayar Tagihan (Record Payment) -->
            <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
                <div v-if="payInvoiceItem" class="p-6 space-y-4">
                    <h3 class="text-md font-bold text-gray-950 dark:text-gray-100">
                        Catat Pembayaran Tagihan
                    </h3>
                    
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/40 rounded-xl space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">No. Invoice:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200 font-mono">{{ payInvoiceItem.invoice_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Pelanggan:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ payInvoiceItem.customer ? payInvoiceItem.customer.name : '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Username PPPoE:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200 font-mono">{{ payInvoiceItem.customer ? payInvoiceItem.customer.pppoe_username : '-' }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 dark:border-slate-850 pt-2 font-bold text-sm">
                            <span class="text-indigo-600">Total Harus Dibayar:</span>
                            <span class="text-indigo-600">{{ formatRupiah(payInvoiceItem.total_amount || payInvoiceItem.amount) }}</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitPayment" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                            <select
                                v-model="paymentMethod"
                                required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                            >
                                <option value="Tunai">Tunai (Cash)</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Catatan Pembayaran (Optional)</label>
                            <textarea
                                v-model="paymentNotes"
                                placeholder="Contoh: Transfer ke rek BCA, bayar di loket, dll."
                                rows="3"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="isPayModalOpen = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isProcessingPayment"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition disabled:opacity-50"
                            >
                                {{ isProcessingPayment ? 'Memproses...' : 'Konfirmasi Lunas' }}
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Print stylesheet integration */
@media print {
    /* Hide layout panels */
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    .no-print, header, aside, .py-6, #print-area {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        background: none !important;
        box-shadow: none !important;
    }
    #invoice-capture-card {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        page-break-after: avoid;
    }
    /* We display the print modal container only */
    div[role="dialog"] {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 9999 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .relative.bg-white {
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    /* Make sure captured card is fully visible */
    #invoice-capture-card {
        visibility: visible !important;
    }
    /* Reset visibility for children in modal */
    div[role="dialog"] * {
        visibility: hidden;
    }
    #invoice-capture-card, #invoice-capture-card * {
        visibility: visible !important;
    }
}
</style>
