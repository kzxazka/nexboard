<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const currentRoute = computed(() => route().current());

const navItems = [
    { name: 'Dashboard', route: 'dashboard', icon: 'dashboard' },
    { name: 'Projects', route: 'projects.index', icon: 'projects' },
    { name: 'Whiteboard', route: 'sketches.index', icon: 'sketch' },
    { name: 'Finance', route: 'finance.index', icon: 'finance' },
];

const isActive = (routeName) => {
    if (routeName === 'dashboard') return currentRoute.value === 'dashboard';
    if (routeName === 'projects.index') return currentRoute.value?.startsWith('projects');
    if (routeName === 'sketches.index') return currentRoute.value?.startsWith('sketches');
    if (routeName === 'finance.index') return currentRoute.value?.startsWith('finance');
    return false;
};
</script>

<template>
    <div class="flex h-screen bg-nexboard-base overflow-hidden">
        <!-- Mobile Sidebar Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileSidebarOpen"
                class="fixed inset-0 bg-black/50 z-40 lg:hidden"
                @click="mobileSidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed lg:static inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out',
                sidebarCollapsed ? 'w-[72px]' : 'w-64',
                mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
            class="glass-sidebar"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <Transition
                    enter-active-class="transition-opacity duration-200"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <span v-if="!sidebarCollapsed" class="text-lg font-bold text-white tracking-tight">
                        Nex<span class="text-indigo-400">Board</span>
                    </span>
                </Transition>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="isActive(item.route) ? 'sidebar-link-active' : 'sidebar-link'"
                    :title="sidebarCollapsed ? item.name : ''"
                >
                    <!-- Dashboard Icon -->
                    <svg v-if="item.icon === 'dashboard'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    <!-- Projects Icon -->
                    <svg v-if="item.icon === 'projects'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                    <!-- Sketch Icon -->
                    <svg v-if="item.icon === 'sketch'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                    </svg>
                    <!-- Finance Icon -->
                    <svg v-if="item.icon === 'finance'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <Transition
                        enter-active-class="transition-opacity duration-200"
                        leave-active-class="transition-opacity duration-100"
                        enter-from-class="opacity-0"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                    </Transition>
                </Link>
            </nav>

            <!-- Sidebar Footer - Collapse Toggle -->
            <div class="px-3 py-3 border-t border-white/10 shrink-0">
                <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden lg:flex items-center justify-center w-full py-2 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors"
                >
                    <svg
                        :class="{ 'rotate-180': sidebarCollapsed }"
                        class="w-5 h-5 transition-transform duration-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Bar -->
            <header class="h-16 flex items-center justify-between px-4 lg:px-6 border-b border-white/10 bg-nexboard-surface/50 backdrop-blur-xl shrink-0">
                <!-- Mobile Menu Button -->
                <button
                    @click="mobileSidebarOpen = true"
                    class="lg:hidden p-2 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page Title -->
                <div class="hidden lg:block">
                    <slot name="header" />
                </div>

                <!-- Right Side -->
                <div class="flex items-center gap-3 ml-auto">
                    <!-- Notification Bell -->
                    <button class="p-2 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors relative">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-indigo-500 rounded-full"></span>
                    </button>

                    <!-- User Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 px-3 py-1.5 rounded-nex text-sm text-nexboard-on-surface hover:bg-white/5 transition-colors">
                                <div class="w-7 h-7 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <span class="text-xs font-semibold text-indigo-400">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
                                <svg class="w-4 h-4 text-nexboard-on-surface-variant" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <div class="py-1 bg-nexboard-surface-container-high rounded-nex border border-white/10">
                                <DropdownLink :href="route('profile.edit')" class="text-nexboard-on-surface hover:bg-white/5">
                                    Profile
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-nexboard-on-surface hover:bg-white/5">
                                    Log Out
                                </DropdownLink>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
