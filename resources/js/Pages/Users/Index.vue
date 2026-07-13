<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const currentUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
});

const openAddModal = () => {
    isEditing.value = false;
    currentUserId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    currentUserId.value = user.id;
    form.reset();
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.role = user.role;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('users.update', currentUserId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (user) => {
    if (confirm(`Apakah Anda yakin ingin ${user.is_active ? 'menonaktifkan' : 'mengaktifkan'} akun ${user.name}?`)) {
        router.post(route('users.toggle-status', user.id));
    }
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                    Manajemen Pengguna & Staf
                </h2>
                <button
                    @click="openAddModal"
                    class="self-start sm:self-center px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition"
                >
                    + Tambah Pengguna Baru
                </button>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            
            <!-- Users List Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-150 dark:border-gray-700/50">
                                <th class="px-6 py-4">Nama Pengguna</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Telepon / HP</th>
                                <th class="px-6 py-4">Role Akses</th>
                                <th class="px-6 py-4">Status Akun</th>
                                <th class="px-6 py-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-700/50 text-sm">
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300 transition"
                            >
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ user.name }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ user.phone || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-lg text-xs">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            user.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400'
                                        ]"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                                    >
                                        {{ user.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button
                                        @click="openEditModal(user)"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium transition"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="user.id !== $page.props.auth.user.id"
                                        @click="toggleStatus(user)"
                                        :class="[
                                            user.is_active
                                                ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/20 dark:hover:bg-rose-900/30 dark:text-rose-400'
                                                : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400'
                                        ]"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                    >
                                        {{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="md">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                        {{ isEditing ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Nama Lengkap" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Alamat Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Nomor HP/Telepon" />
                            <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password" :value="isEditing ? 'Sandi Baru (Kosongkan jika tidak diubah)' : 'Kata Sandi'" />
                            <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" :required="!isEditing" />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="role" value="Role Otoritas Akses" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-gray-300"
                                required
                            >
                                <option value="" disabled>Pilih Role</option>
                                <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                            </select>
                            <InputError :message="form.errors.role" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-150 dark:border-gray-700/50">
                            <SecondaryButton @click="isModalOpen = false">Batal</SecondaryButton>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEditing ? 'Simpan' : 'Tambah Baru' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
