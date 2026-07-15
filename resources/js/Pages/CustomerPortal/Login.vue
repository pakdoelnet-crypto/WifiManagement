<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post(route('portal.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Portal Pelanggan" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8">
        <!-- Login Card Wrapper -->
        <div class="w-full max-w-md bg-white/5 dark:bg-gray-900/50 backdrop-blur-xl border border-white/10 dark:border-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <!-- Logo Section -->
            <div class="flex flex-col items-center gap-3 text-center">
                <img src="/images/logo.jpg" alt="PAK DOEL NET" class="h-20 w-20 rounded-full object-cover shadow-lg border-2 border-indigo-600/30" />
                <div>
                    <h1 class="text-xl font-black text-white uppercase tracking-wider">PAK DOEL NET</h1>
                    <p class="text-xs text-indigo-400 font-bold uppercase tracking-wide">Portal Mandiri Pelanggan</p>
                </div>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="username" value="Username PPPoE Anda" class="text-gray-300" />
                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-indigo-500 focus:ring-indigo-500"
                        v-model="form.username"
                        required
                        autofocus
                        placeholder="Contoh: Mbak_Utami"
                    />
                    <InputError class="mt-2" :message="form.errors.username" />
                </div>

                <div>
                    <InputLabel for="password" value="Password PPPoE Anda" class="text-gray-300" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-indigo-500 focus:ring-indigo-500"
                        v-model="form.password"
                        required
                        placeholder="Masukkan password router/PPPoE"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="pt-2">
                    <PrimaryButton class="w-full justify-center !py-3 !bg-indigo-600 hover:!bg-indigo-700" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Masuk ke Portal
                    </PrimaryButton>
                </div>
            </form>

            <!-- Bottom Disclaimer -->
            <div class="text-center text-[10px] text-gray-500 font-semibold tracking-wider">
                © 2026 PAK DOEL NET — WIFI PATAS TANPA BATAS
            </div>
        </div>
    </div>
</template>
