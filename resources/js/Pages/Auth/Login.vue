<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
    <GuestLayout>
        <Head title="Welcome Back" />

        <div v-if="status" class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm font-medium text-emerald-400">
            {{ status }}
        </div>

        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold text-nexboard-on-surface mb-2">Welcome Back</h2>
            <p class="text-sm text-nexboard-on-surface-variant">Sign in to your account to continue</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Email Address</label>
                <input
                    id="email"
                    type="email"
                    class="input-dark"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <InputError class="mt-2 text-red-400" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors"
                    >
                        Forgot password?
                    </Link>
                </div>
                <input
                    id="password"
                    type="password"
                    class="input-dark"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2 text-red-400" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer group">
                    <div class="relative flex items-center justify-center">
                        <Checkbox name="remember" v-model:checked="form.remember" class="peer sr-only" />
                        <div class="w-5 h-5 border border-white/20 rounded bg-white/5 peer-checked:bg-indigo-500 peer-checked:border-indigo-500 transition-all flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-nexboard-on-surface opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <span class="ms-3 text-sm text-nexboard-on-surface-variant group-hover:text-nexboard-on-surface transition-colors">Keep me signed in</span>
                </label>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="btn-primary w-full justify-center py-2.5 text-sm"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Signing in...</span>
                    <span v-else>Sign In</span>
                </button>
            </div>
            
            <div class="mt-6 text-center text-sm text-nexboard-on-surface-variant">
                Don't have an account?
                <Link :href="route('register')" class="font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                    Sign up
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
