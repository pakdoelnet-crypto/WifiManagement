<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 via-slate-50 to-emerald-50 dark:from-slate-950 dark:via-gray-950 dark:to-emerald-950 py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
        <Head title="Buat Akun Baru" />

        <div class="max-w-md w-full space-y-8 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl p-8 rounded-2xl shadow-xl border border-white/20 dark:border-slate-800/50 transition-colors duration-300">
            <!-- Header Brand -->
            <div class="text-center">
                <div class="inline-flex p-3 rounded-2xl bg-indigo-600/10 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 mb-4">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Pendaftaran Anggota
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Mulai kelola portal jaringan Anda sekarang.
                </p>
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" class="text-gray-700 dark:text-gray-300 font-medium" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Nama Lengkap"
                        />
                        <InputError class="mt-1.5" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Alamat Email" class="text-gray-700 dark:text-gray-300 font-medium" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="nama@email.com"
                        />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Kata Sandi" class="text-gray-700 dark:text-gray-300 font-medium" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="Min. 8 Karakter"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" class="text-gray-700 dark:text-gray-300 font-medium" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Ulangi Kata Sandi"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <!-- Action Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900 transition"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        Daftar Akun Baru
                    </button>
                </div>
            </form>

            <!-- Footer Login Link -->
            <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                Sudah punya akun?
                <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                    Login di sini
                </Link>
            </p>
        </div>

        <!-- Footer Copyright -->
        <div class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; 2026 PAK DOEL NET. All rights reserved.
        </div>
    </div>
</template>
