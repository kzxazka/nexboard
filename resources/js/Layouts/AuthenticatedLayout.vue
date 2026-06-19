<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const page = usePage();
const showLogoutConfirm = ref(false);
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const currentTheme = ref('dark');
if (typeof window !== 'undefined') {
    currentTheme.value = localStorage.getItem('nexboard_theme') || 'dark';
}

const setTheme = (theme) => {
    currentTheme.value = theme;
    localStorage.setItem('nexboard_theme', theme);
    document.documentElement.setAttribute('data-theme', theme);
};

const currentRoute = computed(() => route().current());

const navItems = [
    { name: 'Dashboard', route: 'dashboard', icon: 'dashboard' },
    { name: 'Projects', route: 'projects.index', icon: 'projects' },
    { name: 'Brainstorm', route: 'notes.index', icon: 'notes' },
    { name: 'Whiteboard', route: 'sketches.index', icon: 'sketch' },
    { name: 'Finance', route: 'finance.index', icon: 'finance' },
];

const isActive = (routeName) => {
    if (routeName === 'dashboard') return currentRoute.value === 'dashboard';
    if (routeName === 'projects.index') return currentRoute.value?.startsWith('projects');
    if (routeName === 'notes.index') return currentRoute.value?.startsWith('notes');
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
                    <svg class="w-5 h-5 text-nexboard-on-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <Transition
                    enter-active-class="transition-opacity duration-200"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <span v-if="!sidebarCollapsed" class="text-lg font-bold text-nexboard-on-surface tracking-tight">
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
                    <!-- Notes Icon -->
                    <svg v-if="item.icon === 'notes'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
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

            <!-- Sidebar Footer - Collapse Toggle & Theme Switcher -->
            <div class="px-3 py-3 border-t border-white/10 shrink-0 space-y-2">
                <!-- Theme Switcher -->
                <div :class="['flex bg-black/20 p-1 rounded-lg transition-all', sidebarCollapsed ? 'flex-col items-center' : 'justify-between']">
                    <button 
                        @click="setTheme('dark')" 
                        :class="['p-2 rounded-md transition-all', currentTheme === 'dark' ? 'bg-indigo-500 text-white shadow-md' : 'text-nexboard-on-surface-variant hover:text-nexboard-on-surface hover:bg-white/5']"
                        title="Dark Mode"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                    <button 
                        @click="setTheme('light')" 
                        :class="['p-2 rounded-md transition-all', currentTheme === 'light' ? 'bg-indigo-500 text-white shadow-md' : 'text-nexboard-on-surface-variant hover:text-nexboard-on-surface hover:bg-white/5']"
                        title="Light Mode"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    <button 
                        @click="setTheme('reading')" 
                        :class="['p-2 rounded-md transition-all', currentTheme === 'reading' ? 'bg-indigo-500 text-white shadow-md' : 'text-nexboard-on-surface-variant hover:text-nexboard-on-surface hover:bg-white/5']"
                        title="Reading Mode"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </button>
                </div>

                <!-- Collapse Toggle -->
                <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden lg:flex items-center justify-center w-full py-2 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors"
                    title="Toggle Sidebar"
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
            <header class="h-16 flex items-center justify-between px-4 lg:px-6 border-b border-white/10 bg-nexboard-surface/50 backdrop-blur-xl shrink-0 relative z-30">
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
                <div class="hidden lg:flex flex-1 items-center justify-between mx-6">
                    <slot name="header" />
                </div>

                <!-- Right Side -->
                <div class="flex items-center gap-4 ml-auto">
                    <!-- Actions Slot for + New buttons -->
                    <slot name="actions" />
                    
                    <div class="w-px h-6 bg-white/10 hidden sm:block"></div>

                    <!-- Notification Dropdown -->
                    <Dropdown align="right" width="80" content-classes="py-2 bg-nexboard-surface-container-high border border-white/10 rounded-nex overflow-hidden shadow-xl">
                        <template #trigger>
                            <button class="p-2 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors relative">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                <span v-if="$page.props.notifications.length > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                            </button>
                        </template>
                        <template #content>
                            <div class="px-4 py-2 border-b border-white/5 flex items-center justify-between">
                                <span class="font-semibold text-sm text-nexboard-on-surface">Notifications</span>
                                <Link v-if="$page.props.notifications.length > 0" :href="route('notifications.markAllRead')" method="post" as="button" class="text-xs text-indigo-400 hover:text-indigo-300">Mark all read</Link>
                            </div>
                            
                            <div v-if="$page.props.notifications.length === 0" class="px-4 py-6 text-center">
                                <p class="text-sm text-nexboard-on-surface-variant">No new notifications</p>
                            </div>
                            
                            <div v-else class="max-h-80 overflow-y-auto divide-y divide-white/5">
                                <div v-for="notif in $page.props.notifications" :key="notif.id" class="px-4 py-3 hover:bg-white/5 transition-colors group relative">
                                    <div class="flex items-start gap-3">
                                        <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" 
                                            :class="{
                                                'bg-indigo-500': notif.data.type === 'info',
                                                'bg-emerald-500': notif.data.type === 'success',
                                                'bg-amber-500': notif.data.type === 'warning',
                                            }"
                                        ></div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-nexboard-on-surface">{{ notif.data.title }}</p>
                                            <p class="text-xs text-nexboard-on-surface-variant mt-0.5 line-clamp-2">{{ notif.data.message }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link v-if="notif.data.action_url" :href="notif.data.action_url" class="text-[10px] bg-white/10 hover:bg-white/20 text-nexboard-on-surface px-2 py-1 rounded">View</Link>
                                        <Link :href="route('notifications.markRead', notif.id)" method="post" as="button" class="text-[10px] bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-400 px-2 py-1 rounded">Mark Read</Link>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Dropdown>

                    <!-- User Dropdown -->
                    <Dropdown align="right" width="48" content-classes="py-1 bg-nexboard-surface-container-high border border-white/10 rounded-nex overflow-hidden">
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
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <button @click="showLogoutConfirm = true" class="block w-full px-4 py-2 text-start text-sm leading-5 text-nexboard-on-surface hover:bg-white/5 focus:outline-none focus:bg-white/5 transition duration-150 ease-in-out">
                                Log Out
                            </button>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>
        
        <!-- Logout Confirmation Modal -->
        <ConfirmModal 
            :show="showLogoutConfirm" 
            title="Log Out" 
            message="Are you sure you want to log out? Any unsaved changes in your sketches or notes might be lost."
            confirm-text="Log Out"
            @close="showLogoutConfirm = false"
            @confirm="router.post(route('logout'))"
        />
    </div>
</template>
