<script setup>
import { ref, onMounted, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import axios from 'axios';

const props = defineProps({
    customers: Array,
    routers: Array,
    packages: Array,
    canManage: Boolean,
});

// Search & Filter
const searchQuery = ref('');
const filterRouter = ref('');
const filterStatus = ref('');

// Modals State
const isFormModalOpen = ref(false);
const isImportModalOpen = ref(false);
const isEditing = ref(false);
const editingCustomerId = ref(null);

// Forms
const form = useForm({
    router_id: '',
    package_id: '',
    name: '',
    phone: '',
    whatsapp: '',
    email: '',
    ktp_number: '',
    ktp_photo: null,
    photo: null,
    address: '',
    lat: -6.200000,
    lng: 106.816666,
    pppoe_username: '',
    pppoe_password: '',
});

// Import State
const selectedImportRouterId = ref('');
const importableSecrets = ref([]);
const isFetchingSecrets = ref(false);
const fetchSecretsError = ref('');
const activeImportRow = ref(null); // to show inline details form

const importForm = useForm({
    router_id: '',
    package_id: '',
    name: '',
    phone: '',
    whatsapp: '',
    address: '',
    pppoe_username: '',
    pppoe_password: '',
    pppoe_secret_id: '',
    pppoe_profile: '',
});

// Leaflet Map Helpers
const leafletLoaded = ref(false);
let mapInstance = null;
let markerInstance = null;

const loadLeaflet = () => {
    return new Promise((resolve) => {
        if (window.L) {
            resolve(window.L);
            return;
        }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            leafletLoaded.value = true;
            resolve(window.L);
        };
        document.head.appendChild(script);
    });
};

const initMap = async (lat, lng) => {
    const L = await loadLeaflet();
    await nextTick();
    
    const container = document.getElementById('map-container');
    if (!container) return;

    if (mapInstance) {
        mapInstance.remove();
    }

    mapInstance = L.map('map-container', { doubleClickZoom: false }).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);

    markerInstance.on('dragend', () => {
        const pos = markerInstance.getLatLng();
        form.lat = parseFloat(pos.lat.toFixed(6));
        form.lng = parseFloat(pos.lng.toFixed(6));
    });

    mapInstance.on('click', (e) => {
        const pos = e.latlng;
        markerInstance.setLatLng(pos);
        form.lat = parseFloat(pos.lat.toFixed(6));
        form.lng = parseFloat(pos.lng.toFixed(6));
    });
};

// File handlers
const handleKtpUpload = (e) => {
    form.ktp_photo = e.target.files[0];
};

const handlePhotoUpload = (e) => {
    form.photo = e.target.files[0];
};

// Fetch Secrets for Import
const fetchSecrets = async () => {
    if (!selectedImportRouterId.value) return;
    isFetchingSecrets.value = true;
    fetchSecretsError.value = '';
    importableSecrets.value = [];
    activeImportRow.value = null;

    try {
        const response = await axios.get(route('customers.sync-secrets', selectedImportRouterId.value));
        importableSecrets.value = response.data.secrets;
    } catch (error) {
        fetchSecretsError.value = error.response?.data?.message || 'Gagal mengambil data secrets dari MikroTik.';
    } finally {
        isFetchingSecrets.value = false;
    }
};

const startImportRow = (secret) => {
    activeImportRow.value = secret.id;
    importForm.reset();
    importForm.router_id = selectedImportRouterId.value;
    importForm.pppoe_username = secret.name;
    importForm.pppoe_password = secret.password;
    importForm.pppoe_secret_id = secret.id;
    importForm.pppoe_profile = secret.profile;
    importForm.name = secret.name; // default name to username
    
    // Auto-map package if profile name matches
    const matchedPackage = props.packages.find(p => p.mikrotik_profile === secret.profile);
    if (matchedPackage) {
        importForm.package_id = matchedPackage.id;
    }
};

const submitImport = () => {
    importForm.post(route('customers.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            activeImportRow.value = null;
            importableSecrets.value = importableSecrets.value.filter(s => s.id !== importForm.pppoe_secret_id);
            router.reload();
        },
    });
};

// Customer CRUDS
const openAddModal = () => {
    isEditing.value = false;
    editingCustomerId.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
    
    if (props.routers.length > 0) {
        form.router_id = props.routers[0].id;
    }
    if (props.packages.length > 0) {
        form.package_id = props.packages[0].id;
    }
    
    setTimeout(() => {
        initMap(-6.200000, 106.816666);
    }, 200);
};

const openEditModal = (customer) => {
    isEditing.value = true;
    editingCustomerId.value = customer.id;
    form.clearErrors();
    
    form.router_id = customer.router_id;
    form.package_id = customer.package_id || '';
    form.name = customer.name;
    form.phone = customer.phone;
    form.whatsapp = customer.whatsapp;
    form.email = customer.email || '';
    form.ktp_number = customer.ktp_number || '';
    form.address = customer.address;
    form.lat = customer.lat ? parseFloat(customer.lat) : -6.200000;
    form.lng = customer.lng ? parseFloat(customer.lng) : 106.816666;
    form.pppoe_username = customer.pppoe_username;
    form.pppoe_password = ''; // don't prefill password for updates unless they want to change it
    form.ktp_photo = null;
    form.photo = null;
    
    isFormModalOpen.value = true;

    setTimeout(() => {
        initMap(form.lat, form.lng);
    }, 200);
};

const submitForm = () => {
    if (isEditing.value) {
        // multipart/form-data update requires POST with custom Laravel spoofing or normal route
        form.post(route('customers.update', editingCustomerId.value), {
            onSuccess: () => isFormModalOpen.value = false,
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => isFormModalOpen.value = false,
        });
    }
};

const toggleCustomerStatus = (id, newStatus) => {
    if (confirm(`Apakah Anda yakin ingin mengubah status pelanggan menjadi ${newStatus}?`)) {
        router.post(route('customers.toggle-status', id), { status: newStatus });
    }
};

const deleteCustomer = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini? Tindakan ini juga akan menghapus user secret terkait di MikroTik.')) {
        router.delete(route('customers.destroy', id));
    }
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

const formatRupiah = (value) => {
    if (value === null || value === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

const sendWhatsAppReminder = (customer) => {
    if (!customer || !customer.current_invoice) return;
    let phone = customer.whatsapp.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    const customerName = customer.name;
    const invoiceNum = customer.current_invoice.invoice_number;
    const amountStr = formatRupiah(customer.current_invoice.total_amount || customer.current_invoice.amount);
    const dueDate = customer.current_invoice.due_date;
    const dueDateStr = dueDate ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(dueDate)) : '-';
    const periodStr = formatPeriod(customer.current_invoice.periode);
    
    // Generate public link for invoice
    const publicUrl = `${window.location.origin}/invoices/${invoiceNum}/public`;
    
    const message = `Halo Kak *${customerName}*,\n\nKami dari *PAK DOEL NET* menginfokan bahwa tagihan internet RTRW Net Anda untuk periode *${periodStr}* dengan No. Nota *${invoiceNum}* sebesar *${amountStr}* telah diterbitkan.\n\nAnda dapat melihat detail nota dan mendownload gambar JPG langsung melalui link berikut:\n🔗 ${publicUrl}\n\nTagihan jatuh tempo pada *${dueDateStr}*.\n\nSilakan lakukan pembayaran tunai ke loket atau transfer bank sebelum jatuh tempo demi kelancaran koneksi internet Anda.\n\nTerima kasih. 🙏`;
    
    const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Manajemen Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Pelanggan (PPPoE)
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="canManage"
                        @click="isImportModalOpen = true"
                        class="px-4 py-2 bg-slate-750 hover:bg-slate-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import dari MikroTik
                    </button>
                    <button
                        v-if="canManage"
                        @click="openAddModal"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm flex items-center gap-1.5"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Pelanggan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search & Filters -->
                <div class="mb-6 flex flex-col md:flex-row gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex-1">
                        <TextInput
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau username PPPoE..."
                            class="w-full"
                        />
                    </div>
                    <div class="w-full md:w-48">
                        <select
                            v-model="filterRouter"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Router</option>
                            <option v-for="routerItem in routers" :key="routerItem.id" :value="routerItem.id">
                                {{ routerItem.name }}
                            </option>
                        </select>
                    </div>
                    <div class="w-full md:w-48">
                        <select
                            v-model="filterStatus"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="isolir">Isolir</option>
                            <option value="suspended">Suspend</option>
                        </select>
                    </div>
                </div>

                <!-- Customers List Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 dark:bg-gray-900/40 dark:border-gray-700">
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Pelanggan</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">PPPoE Account</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Router / Paket</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Peta & Kontak</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Tagihan</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-right text-gray-500 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150 dark:divide-gray-700">
                                <tr v-if="customers.length === 0">
                                    <td colspan="7" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada data pelanggan terdaftar.
                                    </td>
                                </tr>
                                <tr
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                                >
                                    <!-- Pelanggan Details -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center overflow-hidden">
                                                <img
                                                    v-if="customer.photo_path"
                                                    :src="route('customers.media', { type: 'photo', filename: customer.photo_path.split('/').pop() })"
                                                    class="h-full w-full object-cover"
                                                />
                                                <svg v-else class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ customer.name }}</div>
                                                <div class="text-xs text-gray-400">Grup: PPPoE</div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- PPPoE Account -->
                                    <td class="p-4">
                                        <div class="text-sm font-mono text-gray-800 dark:text-gray-300 font-semibold">{{ customer.pppoe_username }}</div>
                                        <span
                                            :class="customer.source === 'imported' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-400'"
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider mt-1"
                                        >
                                            {{ customer.source === 'imported' ? 'Imported' : 'App Created' }}
                                        </span>
                                    </td>

                                    <!-- Router & Package -->
                                    <td class="p-4 text-sm">
                                        <div class="font-medium text-gray-800 dark:text-gray-300">{{ customer.router?.name || 'N/A' }}</div>
                                        <div class="text-xs text-indigo-500 font-semibold mt-0.5">{{ customer.package?.name || 'Belum Pilih Paket' }}</div>
                                    </td>

                                    <!-- Location & Phone -->
                                    <td class="p-4 text-xs text-gray-500 dark:text-gray-400">
                                        <div>WA: {{ customer.whatsapp }}</div>
                                        <div class="mt-1 flex items-center gap-1 font-semibold text-indigo-400" v-if="customer.lat && customer.lng">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ customer.lat }}, {{ customer.lng }}
                                        </div>
                                    </td>

                                    <!-- Tagihan Badge -->
                                    <td class="p-4 text-xs">
                                        <div v-if="customer.current_invoice" class="space-y-1">
                                            <span
                                                v-if="customer.current_invoice.status === 'paid'"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 uppercase tracking-wider text-[10px]"
                                            >
                                                Lunas
                                            </span>
                                            <span
                                                v-else-if="customer.current_invoice.status === 'unpaid'"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 uppercase tracking-wider text-[10px]"
                                            >
                                                Belum Bayar
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400 uppercase tracking-wider text-[10px]"
                                            >
                                                Terlambat
                                            </span>
                                            <div class="text-[9px] text-gray-400 font-mono">
                                                {{ formatPeriod(customer.current_invoice.periode) }}
                                            </div>
                                        </div>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-gray-150 text-gray-600 dark:bg-gray-800 dark:text-gray-400 text-[10px]"
                                        >
                                            Belum Terbit
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="p-4">
                                        <select
                                            v-model="customer.status"
                                            @change="toggleCustomerStatus(customer.id, $event.target.value)"
                                            class="block rounded-md border-0 bg-transparent text-xs font-semibold py-1 focus:ring-1 focus:ring-indigo-500"
                                            :class="{
                                                'text-emerald-600 dark:text-emerald-400': customer.status === 'active',
                                                'text-amber-600 dark:text-amber-400': customer.status === 'isolir',
                                                'text-rose-600 dark:text-rose-400': customer.status === 'suspended'
                                            }"
                                        >
                                            <option value="active">Aktif</option>
                                            <option value="isolir">Isolir</option>
                                            <option value="suspended">Suspend</option>
                                        </select>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-sm text-right space-x-2">
                                        <button
                                            v-if="customer.current_invoice && customer.current_invoice.status !== 'paid'"
                                            @click="sendWhatsAppReminder(customer)"
                                            class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm"
                                        >
                                            WA Nota
                                        </button>
                                        <Link
                                            :href="route('connection-history.index', { customer_id: customer.id })"
                                            class="inline-flex items-center px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg text-xs font-medium transition"
                                        >
                                            Koneksi
                                        </Link>
                                        <button
                                            v-if="canManage"
                                            @click="openEditModal(customer)"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="canManage"
                                            @click="deleteCustomer(customer.id)"
                                            class="inline-flex items-center px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-lg text-xs font-medium transition"
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

        <!-- Add/Edit Form Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    {{ isEditing ? 'Edit Data Pelanggan' : 'Tambah Pelanggan Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: Details -->
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Nama Lengkap" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="phone" value="No. Telepon / HP" />
                                <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="whatsapp" value="WhatsApp" />
                                <TextInput id="whatsapp" v-model="form.whatsapp" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.whatsapp" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="email" value="Email (Opsional)" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="ktp_number" value="Nomor KTP (Opsional)" />
                                <TextInput id="ktp_number" v-model="form.ktp_number" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.ktp_number" class="mt-2" />
                            </div>
                        </div>

                        <!-- Upload Photo & KTP -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="ktp_photo" value="Upload Foto KTP" />
                                <input
                                    id="ktp_photo"
                                    type="file"
                                    accept="image/*"
                                    @change="handleKtpUpload"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                                />
                                <InputError :message="form.errors.ktp_photo" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="photo" value="Upload Foto Pelanggan" />
                                <input
                                    id="photo"
                                    type="file"
                                    accept="image/*"
                                    @change="handlePhotoUpload"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                                />
                                <InputError :message="form.errors.photo" class="mt-2" />
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <InputLabel for="address" value="Alamat Lengkap" />
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            ></textarea>
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>
                    </div>

                    <!-- Right Column: Network & Coordinates -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="form_router_id" value="Router MikroTik" />
                                <select
                                    id="form_router_id"
                                    v-model="form.router_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    required
                                >
                                    <option v-for="routerItem in routers" :key="routerItem.id" :value="routerItem.id">
                                        {{ routerItem.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.router_id" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="form_package_id" value="Paket Internet" />
                                <select
                                    id="form_package_id"
                                    v-model="form.package_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    required
                                >
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                        {{ pkg.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.package_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="pppoe_username" value="PPPoE Username" />
                                <TextInput id="pppoe_username" v-model="form.pppoe_username" type="text" class="mt-1 block w-full font-mono" required />
                                <InputError :message="form.errors.pppoe_username" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="pppoe_password" :value="isEditing ? 'PPPoE Password (Isi jika ingin diubah)' : 'PPPoE Password'" />
                                <TextInput id="pppoe_password" v-model="form.pppoe_password" type="text" class="mt-1 block w-full font-mono" :required="!isEditing" />
                                <InputError :message="form.errors.pppoe_password" class="mt-2" />
                            </div>
                        </div>

                        <!-- GPS Coordinates Map Selection -->
                        <div>
                            <InputLabel value="Posisikan Lokasi Pelanggan pada Peta" />
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <TextInput v-model="form.lat" type="number" step="0.000001" class="text-xs font-mono" placeholder="Latitude" readonly />
                                <TextInput v-model="form.lng" type="number" step="0.000001" class="text-xs font-mono" placeholder="Longitude" readonly />
                            </div>
                            <div id="map-container" class="h-44 w-full rounded-md border border-gray-200 dark:border-gray-700 z-10"></div>
                        </div>
                    </div>

                    <div class="col-span-1 lg:col-span-2 flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isFormModalOpen = false" :disabled="form.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Pelanggan' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Import from Mikrotik Modal -->
        <Modal :show="isImportModalOpen" @close="isImportModalOpen = false" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Import Pelanggan dari Secret PPPoE MikroTik
                </h3>

                <!-- Router select and fetch -->
                <div class="flex gap-3 mb-6 bg-slate-50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <div class="flex-1">
                        <select
                            v-model="selectedImportRouterId"
                            @change="fetchSecrets"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">-- Pilih Router untuk Mengambil Secrets --</option>
                            <option v-for="routerItem in routers" :key="routerItem.id" :value="routerItem.id">
                                {{ routerItem.name }} ({{ routerItem.host }})
                            </option>
                        </select>
                    </div>
                    <button
                        type="button"
                        @click="fetchSecrets"
                        :disabled="isFetchingSecrets || !selectedImportRouterId"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <svg v-if="isFetchingSecrets" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Tarik Data Secret
                    </button>
                </div>

                <div v-if="fetchSecretsError" class="text-sm text-rose-500 mb-4 bg-rose-50 dark:bg-rose-950/20 p-3 rounded">
                    {{ fetchSecretsError }}
                </div>

                <!-- Secrets Table -->
                <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-lg max-h-[300px] overflow-y-auto mb-6">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 z-20">
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase">PPPoE Username</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase">Profile</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase">Service</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-if="importableSecrets.length === 0 && !isFetchingSecrets">
                                <td colspan="4" class="p-6 text-center text-sm text-gray-500">
                                    Belum ada data secret yang ditarik, atau semua secret sudah terdaftar sebagai pelanggan.
                                </td>
                            </tr>
                            <template v-for="secret in importableSecrets" :key="secret.id">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/20 transition">
                                    <td class="p-3 text-sm font-semibold font-mono">{{ secret.name }}</td>
                                    <td class="p-3 text-sm font-mono">{{ secret.profile }}</td>
                                    <td class="p-3 text-sm text-gray-500">{{ secret.service }}</td>
                                    <td class="p-3 text-sm text-right">
                                        <button
                                            @click="startImportRow(secret)"
                                            class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-xs font-semibold"
                                        >
                                            Pilih & Import
                                        </button>
                                    </td>
                                </tr>

                                <!-- Inline Import Form if chosen -->
                                <tr v-if="activeImportRow === secret.id" class="bg-indigo-50/20 dark:bg-indigo-950/10">
                                    <td colspan="4" class="p-4 border-t border-b border-indigo-100/50 dark:border-indigo-950/50">
                                        <h4 class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-4">
                                            Lengkapi Data Profil Pelanggan untuk Import
                                        </h4>
                                        <form @submit.prevent="submitImport" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel for="imp_name" value="Nama Pelanggan" />
                                                <TextInput id="imp_name" v-model="importForm.name" type="text" class="mt-1 block w-full" required />
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <InputLabel for="imp_phone" value="No. Telepon" />
                                                    <TextInput id="imp_phone" v-model="importForm.phone" type="text" class="mt-1 block w-full" required />
                                                </div>
                                                <div>
                                                    <InputLabel for="imp_whatsapp" value="WhatsApp" />
                                                    <TextInput id="imp_whatsapp" v-model="importForm.whatsapp" type="text" class="mt-1 block w-full" required />
                                                </div>
                                            </div>
                                            <div>
                                                <InputLabel for="imp_package_id" value="Paket Internet Mapping" />
                                                <select
                                                    id="imp_package_id"
                                                    v-model="importForm.package_id"
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                                    required
                                                >
                                                    <option value="">-- Pilih Paket --</option>
                                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                                        {{ pkg.name }} (Profile: {{ pkg.mikrotik_profile }})
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel for="imp_address" value="Alamat" />
                                                <TextInput id="imp_address" v-model="importForm.address" type="text" class="mt-1 block w-full" required />
                                            </div>
                                            <div class="col-span-1 md:col-span-2 flex justify-end gap-2 pt-2">
                                                <button
                                                    type="button"
                                                    @click="activeImportRow = null"
                                                    class="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded text-xs font-semibold hover:bg-gray-300 dark:hover:bg-gray-750 transition"
                                                >
                                                    Batal
                                                </button>
                                                <button
                                                    type="submit"
                                                    :disabled="importForm.processing"
                                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold transition"
                                                >
                                                    Konfirmasi Import
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-150 dark:border-gray-700">
                    <SecondaryButton @click="isImportModalOpen = false">
                        Tutup
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
