<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentProjects: Array,
    monthlyRevenue: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const statusConfig = {
    planning: { label: 'Planning', class: 'badge-neutral' },
    in_progress: { label: 'In Progress', class: 'badge-primary' },
    review: { label: 'Review', class: 'badge-warning' },
    completed: { label: 'Completed', class: 'badge-success' },
    on_hold: { label: 'On Hold', class: 'badge-error' },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-nexboard-on-surface">Dashboard</h1>
                <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Welcome back! Here's your overview.</p>
            </div>
        </template>

        <div class="page-transition space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Active Projects -->
                <div class="metric-card animate-slide-up" style="animation-delay: 0ms">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Active Projects</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-nexboard-on-surface">{{ stats.activeProjects }}</div>
                    <p class="text-xs text-nexboard-on-surface-variant mt-1">of {{ stats.totalProjects }} total</p>
                </div>

                <!-- Pending Tasks -->
                <div class="metric-card animate-slide-up" style="animation-delay: 100ms">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Pending Tasks</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-nexboard-on-surface">{{ stats.pendingTasks }}</div>
                    <p class="text-xs text-nexboard-on-surface-variant mt-1">tasks to complete</p>
                </div>

                <!-- Total Revenue -->
                <div class="metric-card animate-slide-up" style="animation-delay: 200ms">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Total Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-emerald-400">{{ formatCurrency(stats.totalRevenue) }}</div>
                    <p class="text-xs text-nexboard-on-surface-variant mt-1">total income</p>
                </div>

                <!-- Net Profit -->
                <div class="metric-card animate-slide-up" style="animation-delay: 300ms">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Net Profit</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div :class="['text-3xl font-bold', stats.netProfit >= 0 ? 'text-emerald-400' : 'text-red-400']">
                        {{ formatCurrency(stats.netProfit) }}
                    </div>
                    <p class="text-xs text-nexboard-on-surface-variant mt-1">revenue - expenses</p>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="glass-card animate-slide-up" style="animation-delay: 400ms">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
                    <h2 class="text-base font-semibold text-nexboard-on-surface">Recent Projects</h2>
                    <Link :href="route('projects.index')" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                        View All →
                    </Link>
                </div>

                <div v-if="recentProjects.length === 0" class="px-5 py-12 text-center">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-nexboard-surface-container-high flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-nexboard-on-surface-variant" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                    </div>
                    <p class="text-sm text-nexboard-on-surface-variant">No projects yet.</p>
                    <Link :href="route('projects.index')" class="btn-primary mt-3 text-xs">
                        Create First Project
                    </Link>
                </div>

                <div v-else class="divide-y divide-white/5">
                    <Link
                        v-for="project in recentProjects"
                        :key="project.id"
                        :href="route('projects.show', project.id)"
                        class="flex items-center justify-between px-5 py-3.5 hover:bg-white/[0.02] transition-colors group"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-2 h-2 rounded-full shrink-0"
                                :class="{
                                    'bg-nexboard-neutral-badge': project.status === 'planning',
                                    'bg-indigo-400': project.status === 'in_progress',
                                    'bg-nexboard-warning': project.status === 'review',
                                    'bg-nexboard-success': project.status === 'completed',
                                    'bg-nexboard-error': project.status === 'on_hold',
                                }"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-nexboard-on-surface truncate group-hover:text-indigo-400 transition-colors">
                                    {{ project.name }}
                                </p>
                                <p class="text-xs text-nexboard-on-surface-variant mt-0.5">
                                    {{ project.client_name }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <span class="text-xs text-nexboard-on-surface-variant hidden sm:inline">
                                {{ formatDate(project.deadline) }}
                            </span>
                            <span :class="statusConfig[project.status]?.class || 'badge-neutral'" class="text-[10px]">
                                {{ statusConfig[project.status]?.label || project.status }}
                            </span>
                            <span class="text-xs text-nexboard-on-surface-variant">
                                {{ project.tasks_count }} tasks
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
