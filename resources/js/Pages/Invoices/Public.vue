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
            class="bg-white p-8 w-full max-w-md shadow-xl border border-slate-200 text-slate-800 font-sans leading-normal relative overflow-hidden rounded-3xl mx-auto flex flex-col items-center"
            style="color: #1e293b; background-color: #ffffff; border-color: #e2e8f0;"
        >
            <!-- Title -->
            <div class="text-center w-full mt-4">
                <h1 class="text-3xl font-black text-slate-900 tracking-wider">PAK DOEL.NET</h1>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-3">INVOICE TAGIHAN</p>
                <div class="border-t border-slate-100 my-5"></div>
            </div>

            <!-- Invoice Details (No. Invoice, Tanggal) -->
            <div class="w-full space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400 font-medium">No. Invoice</span>
                    <span class="font-bold text-slate-950 font-mono">{{ invoice.invoice_number }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400 font-medium">Tanggal</span>
                    <span class="font-bold text-slate-950">{{ formatDate(invoice.created_at) }}</span>
                </div>
                <div class="border-t border-slate-100 my-5"></div>
            </div>

            <!-- Customer -->
            <div class="w-full mb-5">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">PELANGGAN</h3>
                <p class="text-xl font-extrabold text-slate-950">{{ invoice.customer ? invoice.customer.name : 'Pelanggan Terhapus' }}</p>
            </div>

            <!-- Billing Month -->
            <div class="w-full mb-5">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">UNTUK BULAN</h3>
                <p class="text-xl font-extrabold text-slate-950">{{ formatPeriod(invoice.periode) }}</p>
            </div>

            <!-- Amount to Pay -->
            <div class="w-full mb-6">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">JUMLAH BAYAR</h3>
                <p class="text-3xl font-black text-emerald-600">{{ formatRupiah(invoice.total_amount || invoice.amount) }}</p>
            </div>

            <!-- Status Pill/Card -->
            <div class="w-full mb-10 text-center">
                <div 
                    class="py-3 px-6 rounded-2xl font-bold uppercase tracking-wider text-sm text-center"
                    :style="[
                        invoice.status === 'paid'
                            ? { backgroundColor: '#ecfdf5', color: '#059669' }
                            : (invoice.status === 'unpaid' ? { backgroundColor: '#fffbeb', color: '#d97706' } : { backgroundColor: '#fef2f2', color: '#dc2626' })
                    ]"
                >
                    STATUS: {{ invoice.status === 'paid' ? 'LUNAS' : (invoice.status === 'unpaid' ? 'BELUM LUNAS' : 'TERLAMBAT') }}
                </div>
            </div>

            <!-- Footer Notes -->
            <div class="w-full text-center space-y-2 mt-auto text-xs text-slate-400">
                <p class="italic">Simpan bukti ini sebagai tanda terima sah.</p>
                <p>Terima kasih atas pembayarannya.</p>
                <div class="pt-6 text-[9px] text-slate-300 space-y-1">
                    <p>Dicetak pada: {{ formatDate(new Date()) }} {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</p>
                    <p class="font-bold tracking-widest">© 2026 PAK DOEL.NET</p>
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
