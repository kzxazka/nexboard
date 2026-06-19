<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: Array,
});

const showCreateModal = ref(false);

const form = useForm({
    name: '',
    client_name: '',
    client_email: '',
    description: '',
    deadline: '',
    status: 'planning',
    budget: '',
});

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const statusConfig = {
    planning: { label: 'Planning', class: 'badge-neutral' },
    in_progress: { label: 'In Progress', class: 'badge-primary' },
    review: { label: 'Review', class: 'badge-warning' },
    completed: { label: 'Completed', class: 'badge-success' },
    on_hold: { label: 'On Hold', class: 'badge-error' },
};

const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-semibold text-nexboard-on-surface">Projects</h1>
                    <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Manage your client projects</p>
                </div>
                <button @click="showCreateModal = true" class="btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Project
                </button>
            </div>
        </template>

        <div class="page-transition">
            <!-- Empty State -->
            <div v-if="projects.length === 0" class="glass-card p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-nexboard-surface-container-high flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-nexboard-on-surface-variant" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-1">No projects yet</h3>
                <p class="text-sm text-nexboard-on-surface-variant mb-4">Create your first project to get started.</p>
                <button @click="showCreateModal = true" class="btn-primary">Create Project</button>
            </div>

            <!-- Project Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <Link
                    v-for="(project, index) in projects"
                    :key="project.id"
                    :href="route('projects.show', project.id)"
                    class="glass-card p-5 transition-all duration-300 hover:border-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/5 group animate-slide-up"
                    :style="{ animationDelay: `${index * 50}ms` }"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-white truncate group-hover:text-indigo-400 transition-colors">
                                {{ project.name }}
                            </h3>
                            <p class="text-xs text-nexboard-on-surface-variant mt-1">{{ project.client_name }}</p>
                        </div>
                        <span :class="statusConfig[project.status]?.class" class="text-[10px] ml-2 shrink-0">
                            {{ statusConfig[project.status]?.label }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-nexboard-on-surface-variant">
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            {{ formatDate(project.deadline) }}
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                            {{ project.tasks_count }} tasks
                        </div>
                        <div v-if="project.budget" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ formatCurrency(project.budget) }}
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="showCreateModal = false">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
                    <div class="glass-card-elevated w-full max-w-lg p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-white mb-4">Create New Project</h2>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Project Name *</label>
                                <input v-model="form.name" type="text" class="input-dark" placeholder="e.g., E-Commerce Redesign" required />
                                <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Client Name *</label>
                                    <input v-model="form.client_name" type="text" class="input-dark" placeholder="e.g., PT Maju Jaya" required />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Client Email</label>
                                    <input v-model="form.client_email" type="email" class="input-dark" placeholder="client@email.com" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Description</label>
                                <textarea v-model="form.description" rows="3" class="input-dark resize-none" placeholder="Project details..." />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Deadline</label>
                                    <input v-model="form.deadline" type="date" class="input-dark" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Budget (IDR)</label>
                                    <input v-model="form.budget" type="number" class="input-dark" placeholder="5000000" />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="btn-primary">
                                    {{ form.processing ? 'Creating...' : 'Create Project' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
