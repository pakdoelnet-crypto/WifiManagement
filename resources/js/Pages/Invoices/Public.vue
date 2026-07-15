<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import html2canvas from 'html2canvas';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    }
});

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

const isDownloading = ref(false);
const downloadAsImage = async (format) => {
    isDownloading.value = true;
    const element = document.getElementById('invoice-capture-card');
    if (!element) {
        isDownloading.value = false;
        return;
    }

    try {
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
            dataUrl = canvas.toDataURL('image/jpeg', 0.95);
        }

        const link = document.createElement('a');
        link.download = `Invoice-${props.invoice.invoice_number}.${fileExtension}`;
        link.href = dataUrl;
        link.click();
    } catch (e) {
        console.error('Failed to convert HTML to Image:', e);
    } finally {
        isDownloading.value = false;
    }
};

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <Head :title="'Invoice ' + invoice.invoice_number" />

    <div class="min-h-screen bg-slate-900 py-10 px-4 sm:px-6 lg:px-8 text-slate-100 flex flex-col items-center justify-center">
        <!-- Action Buttons -->
        <div class="w-full max-w-2xl flex justify-end gap-2.5 mb-4 no-print">
            <button
                @click="downloadAsImage('PNG')"
                :disabled="isDownloading"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition disabled:opacity-50 flex items-center gap-1.5 shadow-md"
            >
                Download PNG
            </button>
            <button
                @click="downloadAsImage('JPG')"
                :disabled="isDownloading"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition disabled:opacity-50 flex items-center gap-1.5 shadow-md"
            >
                Download JPG
            </button>
            <button
                @click="printInvoice"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-md border border-slate-700"
            >
                Cetak Nota
            </button>
        </div>

        <!-- Invoice Card -->
        <div
            id="invoice-capture-card"
            class="bg-white p-8 w-full max-w-2xl shadow-xl border border-slate-200 text-slate-800 font-sans leading-normal relative overflow-hidden rounded-2xl"
            style="min-height: 750px; color: #1e293b; background-color: #ffffff; border-color: #e2e8f0;"
        >
            <!-- Watermark -->
            <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
                <span class="text-7xl font-extrabold rotate-45 tracking-widest uppercase">PAK DOEL NET</span>
            </div>

            <!-- Header -->
            <div class="flex items-start justify-between border-b-2 border-slate-100 pb-6 mb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
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
                    <p class="font-mono text-xs font-bold text-slate-800">{{ invoice.invoice_number }}</p>
                    <div class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider mt-2"
                         :style="[
                             invoice.status === 'paid'
                                 ? { backgroundColor: '#e6fffa', color: '#047481' }
                                 : (invoice.status === 'unpaid' ? { backgroundColor: '#fffaf0', color: '#b7791f' } : { backgroundColor: '#fff5f5', color: '#c53030' })
                         ]"
                    >
                        {{ invoice.status === 'paid' ? 'LUNAS' : (invoice.status === 'unpaid' ? 'BELUM LUNAS' : 'TERLAMBAT') }}
                    </div>
                </div>
            </div>

            <!-- Customer & Info Grid -->
            <div class="grid grid-cols-2 gap-8 mb-8 text-xs">
                <div>
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ditagih Kepada:</h3>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 text-sm">{{ invoice.customer ? invoice.customer.name : 'Pelanggan Terhapus' }}</p>
                        <p class="text-slate-600 line-clamp-2">{{ invoice.customer ? invoice.customer.address : '-' }}</p>
                        <p class="text-slate-600">WhatsApp: {{ invoice.customer ? invoice.customer.whatsapp : '-' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Rincian Periode & Tanggal:</h3>
                    <div class="space-y-1 text-slate-600">
                        <p><span class="font-semibold text-slate-500">Periode Layanan:</span> <span class="font-bold text-slate-900">{{ formatPeriod(invoice.periode) }}</span></p>
                        <p><span class="font-semibold text-slate-500">Tanggal Terbit:</span> {{ formatDate(invoice.created_at) }}</p>
                        <p><span class="font-semibold text-slate-500">Jatuh Tempo:</span> <span class="font-bold text-slate-900">{{ formatDate(invoice.due_date) }}</span></p>
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
                                    {{ invoice.customer && invoice.customer.package ? invoice.customer.package.name : 'Paket Layanan Internet' }}
                                </p>
                                <p class="text-[10px] text-slate-400">Bulanan RTRW Net internet berlangganan</p>
                            </td>
                            <td class="px-4 py-4 text-right font-bold text-slate-900">
                                {{ formatRupiah(invoice.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary & Payment Info Grid -->
            <div class="grid grid-cols-2 gap-8 mb-8 text-xs">
                <div>
                    <div v-if="invoice.status === 'paid' && invoice.payment" class="bg-slate-50 p-4 rounded-xl space-y-1.5 border border-slate-100">
                        <h4 class="font-bold text-slate-900 text-[10px] uppercase tracking-wider">Informasi Pelunasan:</h4>
                        <p class="text-slate-600"><span class="font-medium text-slate-400">Tgl Bayar:</span> {{ formatDate(invoice.payment.payment_date) }}</p>
                        <p class="text-slate-600"><span class="font-medium text-slate-400">Metode:</span> {{ invoice.payment.payment_method }}</p>
                        <p v-if="invoice.payment.notes" class="text-slate-600"><span class="font-medium text-slate-400">Catatan:</span> {{ invoice.payment.notes }}</p>
                    </div>
                    <div v-else class="bg-rose-50/50 p-4 rounded-xl border border-rose-100 text-rose-800 space-y-1 flex flex-col justify-center min-h-[90px]">
                        <p class="font-bold uppercase tracking-wider text-[10px]">Peringatan Pembayaran:</p>
                        <p class="text-[10px] leading-relaxed">Silakan lakukan transfer atau bayar tunai ke loket resmi RTRW Net sebelum tanggal jatuh tempo berakhir demi kelancaran internet Anda.</p>
                    </div>
                </div>

                <div class="space-y-2 text-right">
                    <div class="flex justify-between text-slate-600">
                        <span>Biaya Paket:</span>
                        <span>{{ formatRupiah(invoice.amount) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Denda Keterlambatan (+):</span>
                        <span>{{ formatRupiah(invoice.penalty_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Diskon Potongan (-):</span>
                        <span>{{ formatRupiah(invoice.discount_amount) }}</span>
                    </div>
                    <div class="border-t-2 border-slate-100 pt-2 flex justify-between font-black text-slate-900 text-sm">
                        <span class="text-indigo-600 text-xs">TOTAL BAYAR:</span>
                        <span class="text-lg text-indigo-600">{{ formatRupiah(invoice.total_amount || invoice.amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
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
</template>

<style>
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    .no-print {
        display: none !important;
    }
    #invoice-capture-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
