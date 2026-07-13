<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 via-slate-50 to-emerald-50 dark:from-slate-950 dark:via-gray-950 dark:to-emerald-950 py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
        <Head title="Masuk Ke Sistem" />

        <div class="max-w-md w-full space-y-8 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl p-8 rounded-2xl shadow-xl border border-white/20 dark:border-slate-800/50 transition-colors duration-300">
            <!-- Header Brand -->
            <div class="text-center">
                <div class="inline-flex p-3 rounded-2xl bg-indigo-600/10 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 mb-4">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    PAK DOEL NET
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Silakan masuk untuk mengelola portal jaringan Anda.
                </p>
            </div>

            <!-- Status Alert -->
            <div v-if="status" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 text-sm font-medium rounded-lg border border-emerald-100 dark:border-emerald-900/50">
                {{ status }}
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Alamat Email" class="text-gray-700 dark:text-gray-300 font-medium" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autofocus
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
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ms-2 text-xs text-gray-600 dark:text-gray-400">Ingat saya</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <!-- Action Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900 transition"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        Masuk Ke Portal
                    </button>
                </div>
            </form>

            <!-- Separator -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-200 dark:border-slate-800"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="px-2 bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 transition-colors duration-300">atau</span>
                </div>
            </div>

            <!-- OAuth Options -->
            <div>
                <a
                    href="/auth/google"
                    class="w-full inline-flex justify-center items-center py-2.5 px-4 border border-gray-300 dark:border-slate-700 rounded-lg bg-white hover:bg-gray-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm transition"
                >
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    Masuk dengan Google
                </a>
            </div>

            <!-- Footer Register Link -->
            <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                Belum punya akun?
                <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                    Daftar di sini
                </Link>
            </p>
        </div>

        <!-- Footer Copyright -->
        <div class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; 2026 PAK DOEL NET. All rights reserved.
        </div>
    </div>
</template>
