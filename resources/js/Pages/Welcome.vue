<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <Head title="NexBoard - Agency Management" />
    <div class="min-h-screen bg-nexboard-base flex flex-col relative overflow-hidden">
        <!-- Animated Background Grid -->
        <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
        
        <!-- Glowing Orbs -->
        <div class="absolute top-[-20%] left-[-10%] w-[800px] h-[800px] bg-indigo-600/20 rounded-full mix-blend-screen filter blur-[150px] opacity-70 animate-pulse-slow"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[800px] h-[800px] bg-purple-600/20 rounded-full mix-blend-screen filter blur-[150px] opacity-70 animate-pulse-slow" style="animation-delay: 3s;"></div>
        <div class="absolute top-[20%] right-[20%] w-[500px] h-[500px] bg-blue-500/10 rounded-full mix-blend-screen filter blur-[100px] opacity-50 animate-pulse-slow" style="animation-delay: 1.5s;"></div>

        <!-- Nav -->
        <nav class="relative z-10 flex items-center justify-between px-6 lg:px-12 py-4">
            <div class="flex items-center gap-2">
                <img src="/nextboard_favicon.png" alt="NexBoard Logo" class="w-8 h-8 object-contain drop-shadow-lg" />
                <span class="text-lg font-bold text-white">Nex<span class="text-indigo-400">Board</span></span>
            </div>
            <div v-if="canLogin" class="flex items-center gap-3">
                <Link :href="route('login')" class="text-sm text-nexboard-on-surface-variant hover:text-white transition-colors">Log in</Link>
                <Link v-if="canRegister" :href="route('register')" class="btn-primary text-sm shadow-lg shadow-indigo-500/20">Get Started</Link>
            </div>
        </nav>

        <!-- Hero -->
        <main class="relative z-10 flex-1 flex items-center justify-center px-6">
            <div class="text-center max-w-3xl animate-slide-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-indigo-300 mb-8 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse shadow-[0_0_8px_rgba(129,140,248,0.8)]" />
                    Internal Agency ERP System
                </div>
                <h1 class="text-5xl lg:text-7xl font-extrabold text-white leading-tight tracking-tight drop-shadow-2xl">
                    Manage Your
                    <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent animate-gradient bg-[length:200%_auto]"> Agency </span>
                    <br /> Like a Pro
                </h1>
                <p class="mt-6 text-lg lg:text-xl text-nexboard-on-surface-variant max-w-2xl mx-auto drop-shadow-md">
                    Project management, Kanban boards, whiteboard sketching, and financial tracking — all in one beautiful, high-performance dashboard.
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary px-8 py-3.5 text-base shadow-xl shadow-indigo-500/30 hover:scale-105 transition-transform duration-300">
                        Start Free →
                    </Link>
                    <Link v-if="canLogin" :href="route('login')" class="btn-secondary px-8 py-3.5 text-base hover:scale-105 transition-transform duration-300 backdrop-blur-md bg-white/5">
                        Log In
                    </Link>
                </div>

                <!-- Feature pills -->
                <div class="mt-16 flex flex-wrap justify-center gap-3 opacity-90">
                    <span v-for="f in ['📊 Dashboard', '📋 Kanban Board', '🎨 Whiteboard', '💡 Brainstorming', '💰 Finance Tracker']" :key="f"
                        class="px-4 py-2 rounded-full text-xs lg:text-sm font-medium text-nexboard-on-surface-variant bg-white/5 border border-white/10 backdrop-blur-md hover:bg-white/10 transition-colors cursor-default">
                        {{ f }}
                    </span>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 text-center py-6 text-xs text-nexboard-on-surface-variant/70 font-medium">
            made by frankazastain.studio
        </footer>
    </div>
</template>
