<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    customer: Object,
    invoices: Array,
    tickets: Array,
});

const isTicketModalOpen = ref(false);
const isPasswordModalOpen = ref(false);

const ticketForm = useForm({
    category: 'Wifi Lemot',
    notes: '',
    photo: null,
});

const passwordForm = useForm({
    new_password: '',
});

const handlePhotoUpload = (e) => {
    ticketForm.photo = e.target.files[0];
};

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

const submitTicket = () => {
    ticketForm.post(route('portal.ticket'), {
        onSuccess: () => {
            isTicketModalOpen.value = false;
            ticketForm.reset();
            alert('Laporan gangguan berhasil dikirim ke teknisi kami.');
        }
    });
};

const submitPassword = () => {
    passwordForm.post(route('portal.change-password'), {
        onSuccess: () => {
            isPasswordModalOpen.value = false;
            passwordForm.reset();
            alert('Password PPPoE Anda berhasil diubah! Silakan restart router/modem Anda jika koneksi terputus.');
        }
    });
};
</script>

<template>
    <Head title="Portal Pelanggan Mandiri" />

    <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col">
        <!-- Top Navbar -->
        <header class="h-16 bg-slate-800 border-b border-slate-700/60 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-2">
                <img src="/images/logo.jpg" alt="PAK DOEL NET" class="h-9 w-9 rounded-full object-cover shadow-sm shrink-0" />
                <span class="text-md font-bold tracking-wider text-indigo-400">PAK DOEL NET</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden sm:inline text-xs font-semibold text-gray-400">Halo, {{ customer.name }}</span>
                <Link
                    :href="route('portal.logout')"
                    method="post"
                    as="button"
                    class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-sm"
                >
                    Keluar
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-6">
            <!-- Profile Info & Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile details -->
                <div class="md:col-span-2 bg-slate-800 p-6 rounded-2xl border border-slate-700/60 shadow-lg space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-indigo-400">{{ customer.name }}</h2>
                            <p class="text-xs text-gray-400 mt-1 font-mono">PPPoE Username: {{ customer.pppoe_username }}</p>
                        </div>
                        <span
                            :class="customer.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                            class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                        >
                            {{ customer.status === 'active' ? 'AKTIF' : 'TERISOLIR' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-700/50 text-xs">
                        <div>
                            <span class="text-gray-400 font-semibold uppercase tracking-wider block mb-1">Paket Internet</span>
                            <span class="text-white font-bold">{{ customer.package ? customer.package.name : 'Belum Pilih Paket' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-semibold uppercase tracking-wider block mb-1">Tarif Bulanan</span>
                            <span class="text-indigo-400 font-bold font-mono">{{ customer.package ? formatRupiah(customer.package.price) : 'Rp 0' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-400 font-semibold uppercase tracking-wider block mb-1">Alamat Pemasangan</span>
                            <span class="text-white font-medium">{{ customer.address }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700/60 shadow-lg flex flex-col justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-white text-sm mb-2">Bantuan & Layanan Mandiri</h3>
                        <p class="text-[11px] text-gray-400">Gunakan layanan mandiri ini untuk merubah password router wifi Anda atau melaporkan gangguan langsung ke tim teknis kami.</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button
                            @click="isTicketModalOpen = true"
                            class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                        >
                            Laporkan Gangguan (LOS / Wifi Lemot)
                        </button>
                        <button
                            @click="isPasswordModalOpen = true"
                            class="w-full py-2.5 bg-slate-700 hover:bg-slate-650 text-slate-200 rounded-xl text-xs font-bold transition shadow-sm border border-slate-600"
                        >
                            Ganti Password Router PPPoE
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700/60 shadow-lg space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Riwayat Tagihan & Nota Pembayaran</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-900 border-b border-slate-700/80 text-gray-400 font-bold">
                                <th class="p-3">No. Nota</th>
                                <th class="p-3">Periode</th>
                                <th class="p-3">Tanggal Jatuh Tempo</th>
                                <th class="p-3 text-right">Jumlah</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-right">Nota Digital</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <tr v-if="invoices.length === 0">
                                <td colspan="6" class="p-8 text-center text-gray-500">Belum ada riwayat invoice tagihan.</td>
                            </tr>
                            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-700/30">
                                <td class="p-3 font-mono font-bold text-white">{{ inv.invoice_number }}</td>
                                <td class="p-3 font-semibold text-gray-300">{{ formatPeriod(inv.periode) }}</td>
                                <td class="p-3 text-gray-400 font-mono">{{ formatDate(inv.due_date) }}</td>
                                <td class="p-3 text-right font-bold text-indigo-400 font-mono">{{ formatRupiah(inv.total_amount || inv.amount) }}</td>
                                <td class="p-3 text-center">
                                    <span
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': inv.status === 'paid',
                                            'bg-amber-500/10 text-amber-400 border border-amber-500/20': inv.status === 'unpaid',
                                            'bg-rose-500/10 text-rose-400 border border-rose-500/20': inv.status === 'overdue',
                                        }"
                                        class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px] inline-block"
                                    >
                                        {{ inv.status === 'paid' ? 'LUNAS' : (inv.status === 'unpaid' ? 'BELUM BAYAR' : 'TERLAMBAT') }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <a
                                        :href="route('invoices.public', inv.invoice_number)"
                                        target="_blank"
                                        class="inline-flex items-center px-2.5 py-1 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 rounded text-xs font-semibold border border-indigo-500/10 transition"
                                    >
                                        Lihat & Download
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reported Tickets -->
            <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700/60 shadow-lg space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Laporan Gangguan Anda</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-900 border-b border-slate-700/80 text-gray-400 font-bold">
                                <th class="p-3">No. Ticket</th>
                                <th class="p-3">Kategori</th>
                                <th class="p-3">Tanggal Lapor</th>
                                <th class="p-3">Keterangan</th>
                                <th class="p-3 text-right">Status Pengerjaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <tr v-if="tickets.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-500">Anda belum pernah melaporkan gangguan.</td>
                            </tr>
                            <tr v-for="t in tickets" :key="t.id" class="hover:bg-slate-700/30">
                                <td class="p-3 font-mono font-bold text-white">{{ t.ticket_number }}</td>
                                <td class="p-3 text-indigo-400 font-semibold">{{ t.category }}</td>
                                <td class="p-3 text-gray-400 font-mono">{{ formatDate(t.reported_at) }}</td>
                                <td class="p-3 text-gray-400 max-w-xs truncate" :title="t.notes">{{ t.notes || '-' }}</td>
                                <td class="p-3 text-right">
                                    <span
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': t.status === 'selesai',
                                            'bg-amber-500/10 text-amber-400 border border-amber-500/20': t.status === 'diproses',
                                            'bg-gray-500/10 text-gray-400 border border-gray-500/20': t.status === 'open',
                                        }"
                                        class="px-2.5 py-1 rounded font-bold uppercase tracking-wider text-[9px] inline-block"
                                    >
                                        {{ t.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Form Modal: Report Ticket -->
        <Modal :show="isTicketModalOpen" @close="isTicketModalOpen = false" max-width="md">
            <div class="p-6 bg-slate-800 border border-slate-700 text-slate-100 rounded-2xl">
                <h3 class="text-lg font-bold text-white mb-6">Laporkan Gangguan Baru</h3>
                <form @submit.prevent="submitTicket" class="space-y-4">
                    <div>
                        <InputLabel for="t_category" value="Kategori Gangguan" class="text-gray-300" />
                        <select
                            id="t_category"
                            v-model="ticketForm.category"
                            class="mt-1 block w-full rounded-md border-slate-700 bg-slate-900 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="Wifi Lemot">Koneksi Lambat / Lemot</option>
                            <option value="Kabel Putus">Lampu Router Merah (LOS)</option>
                            <option value="Router Mati">Router / Adaptor Mati</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="t_photo" value="Upload Foto Bukti Gangguan (Opsional)" class="text-gray-300" />
                        <input
                            id="t_photo"
                            type="file"
                            accept="image/*"
                            @change="handlePhotoUpload"
                            class="mt-2 block w-full text-xs text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-slate-200 hover:file:bg-slate-650"
                        />
                    </div>

                    <div>
                        <InputLabel for="t_notes" value="Tulis Keluhan Anda" class="text-gray-300" />
                        <textarea
                            id="t_notes"
                            v-model="ticketForm.notes"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-slate-700 bg-slate-900 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            placeholder="Contoh: Koneksi terputus total sejak jam 10 pagi, sudah coba restart router masih belum bisa..."
                            required
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                        <SecondaryButton @click="isTicketModalOpen = false" class="!bg-slate-700 hover:!bg-slate-650 text-white border-0">Batal</SecondaryButton>
                        <PrimaryButton :disabled="ticketForm.processing" class="!bg-indigo-600 hover:!bg-indigo-700">Kirim Laporan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Form Modal: Change Password -->
        <Modal :show="isPasswordModalOpen" @close="isPasswordModalOpen = false" max-width="sm">
            <div class="p-6 bg-slate-800 border border-slate-700 text-slate-100 rounded-2xl">
                <h3 class="text-lg font-bold text-white mb-6">Ubah Password Router PPPoE</h3>
                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div>
                        <InputLabel for="new_pwd" value="Password Baru" class="text-gray-300" />
                        <TextInput
                            id="new_pwd"
                            v-model="passwordForm.new_password"
                            type="password"
                            class="mt-1 block w-full bg-slate-900 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            placeholder="Min. 4 karakter"
                        />
                        <InputError :message="passwordForm.errors.new_password" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                        <SecondaryButton @click="isPasswordModalOpen = false" class="!bg-slate-700 hover:!bg-slate-650 text-white border-0">Batal</SecondaryButton>
                        <PrimaryButton :disabled="passwordForm.processing" class="!bg-indigo-600 hover:!bg-indigo-700">Ubah Password</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
