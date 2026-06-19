<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <Head title="NexBoard - Agency Management" />
    <div class="min-h-screen bg-nexboard-base flex flex-col">
        <!-- Nav -->
        <nav class="flex items-center justify-between px-6 lg:px-12 py-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-white">Nex<span class="text-indigo-400">Board</span></span>
            </div>
            <div v-if="canLogin" class="flex items-center gap-3">
                <Link :href="route('login')" class="text-sm text-nexboard-on-surface-variant hover:text-white transition-colors">Log in</Link>
                <Link v-if="canRegister" :href="route('register')" class="btn-primary text-sm">Get Started</Link>
            </div>
        </nav>

        <!-- Hero -->
        <main class="flex-1 flex items-center justify-center px-6">
            <div class="text-center max-w-2xl animate-slide-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-xs font-medium text-indigo-400 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse" />
                    Internal Agency ERP System
                </div>
                <h1 class="text-4xl lg:text-6xl font-bold text-white leading-tight tracking-tight">
                    Manage Your
                    <span class="bg-gradient-to-r from-indigo-400 to-violet-400 bg-clip-text text-transparent"> Agency </span>
                    Like a Pro
                </h1>
                <p class="mt-4 text-lg text-nexboard-on-surface-variant max-w-xl mx-auto">
                    Project management, Kanban boards, whiteboard sketching, and financial tracking — all in one beautiful dashboard.
                </p>
                <div class="mt-8 flex items-center justify-center gap-4">
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary px-8 py-3 text-base">
                        Start Free →
                    </Link>
                    <Link v-if="canLogin" :href="route('login')" class="btn-secondary px-8 py-3 text-base">
                        Log In
                    </Link>
                </div>

                <!-- Feature pills -->
                <div class="mt-12 flex flex-wrap justify-center gap-3">
                    <span v-for="f in ['📊 Dashboard', '📋 Kanban Board', '🎨 Whiteboard', '💰 Finance Tracker']" :key="f"
                        class="px-3 py-1.5 rounded-full text-xs font-medium text-nexboard-on-surface-variant bg-white/5 border border-white/10">
                        {{ f }}
                    </span>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center py-6 text-xs text-nexboard-on-surface-variant">
            NexBoard © {{ new Date().getFullYear() }} — Built with Laravel + Vue.js + ❤️
        </footer>
    </div>
</template>
