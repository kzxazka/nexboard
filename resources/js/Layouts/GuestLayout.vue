<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-nexboard-base relative overflow-hidden py-12 sm:px-6 lg:px-8">
        <!-- Abstract Background Orbs -->
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-indigo-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-pulse-slow"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-purple-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-pulse-slow" style="animation-delay: 2s;"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center mb-8">
            <Link href="/" class="inline-flex items-center gap-3">
                <div class="w-12 h-12 flex items-center justify-center">
                    <img src="/nextboard_favicon.png" alt="NexBoard Logo" class="w-10 h-10 object-contain drop-shadow-lg" />
                </div>
                <span class="text-3xl font-bold text-nexboard-on-surface tracking-tight">NexBoard</span>
            </Link>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-[480px] relative z-10">
            <div class="glass-card-elevated px-8 py-10 shadow-2xl sm:rounded-2xl sm:px-12 w-full animate-slide-up">
                <slot />
            </div>
        </div>
    </div>
</template>
