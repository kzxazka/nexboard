<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showConfirmModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    name: '',
    client_name: '',
    client_email: '',
    description: '',
    deadline: '',
    status: 'planning',
    budget: '',
});

const editForm = useForm({
    id: null,
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

const openEditModal = (project) => {
    editForm.id = project.id;
    editForm.name = project.name;
    editForm.client_name = project.client_name || '';
    editForm.client_email = project.client_email || '';
    editForm.description = project.description || '';
    editForm.deadline = project.deadline || '';
    editForm.status = project.status || 'planning';
    editForm.budget = project.budget || '';
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('projects.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
    });
};

const confirmDelete = (project) => {
    itemToDelete.value = project;
    showConfirmModal.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(route('projects.destroy', itemToDelete.value.id), {
            onSuccess: () => {
                showConfirmModal.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const updateProjectStatus = (project, newStatus) => {
    if (project.status === newStatus) return;
    router.put(route('projects.update', project.id), {
        name: project.name,
        client_name: project.client_name,
        client_email: project.client_email,
        description: project.description,
        deadline: project.deadline,
        budget: project.budget,
        status: newStatus,
    }, { preserveScroll: true });
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
            <div>
                <h1 class="text-xl font-semibold text-nexboard-on-surface">Projects</h1>
                <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Manage your client projects</p>
            </div>
        </template>
        <template #actions>
            <button @click="showCreateModal = true" class="btn-primary gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Project
            </button>
        </template>

        <div class="page-transition">
            <!-- Empty State -->
            <div v-if="projects.length === 0" class="glass-card p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-nexboard-surface-container-high flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-nexboard-on-surface-variant" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-nexboard-on-surface mb-1">No projects yet</h3>
                <p class="text-sm text-nexboard-on-surface-variant mb-4">Create your first project to get started.</p>
                <button @click="showCreateModal = true" class="btn-primary">Create Project</button>
            </div>

        <!-- Project Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link
                v-for="(project, index) in projects"
                :key="project.id"
                :href="route('projects.show', project.id)"
                class="glass-card p-5 transition-all duration-300 hover:border-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/5 group animate-slide-up relative flex flex-col justify-between"
                :style="{ animationDelay: `${index * 50}ms` }"
            >
                <div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1 pr-4">
                            <h3 class="text-sm font-semibold text-nexboard-on-surface truncate group-hover:text-indigo-400 transition-colors">
                                {{ project.name }}
                            </h3>
                            <p class="text-xs text-nexboard-on-surface-variant mt-1">{{ project.client_name }}</p>
                        </div>
                        <div class="shrink-0 relative z-20">
                            <select 
                                :value="project.status" 
                                @change="updateProjectStatus(project, $event.target.value)"
                                @click.prevent.stop
                                class="text-[10px] appearance-none pl-2 pr-6 py-1 rounded-full font-medium border-0 focus:ring-0 focus:outline-none bg-opacity-20 cursor-pointer"
                                :class="[statusConfig[project.status]?.class, 'hover:brightness-110 transition-all']"
                            >
                                <option v-for="(config, key) in statusConfig" :key="key" :value="key" class="bg-nexboard-surface text-nexboard-on-surface">
                                    {{ config.label }}
                                </option>
                            </select>
                            <svg class="w-3 h-3 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-nexboard-on-surface-variant mb-4">
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
                </div>

                <!-- Hover CRUD Actions -->
                <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 flex gap-1 transition-opacity z-30" @click.prevent>
                    <button @click.prevent="openEditModal(project)" class="p-1.5 rounded-md bg-[#1c2333]/80 hover:bg-indigo-500/20 text-slate-400 hover:text-indigo-400 backdrop-blur-md transition-colors" title="Edit Project">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.89 1.12l-2.83.993a.375.375 0 01-.47-.47l.993-2.83a4.5 4.5 0 011.12-1.89l13.156-13.156z" />
                        </svg>
                    </button>
                    <button @click.prevent="confirmDelete(project)" class="p-1.5 rounded-md bg-[#1c2333]/80 hover:bg-red-500/20 text-slate-400 hover:text-red-400 backdrop-blur-md transition-colors" title="Delete Project">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
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
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false" />
                    <div class="glass-card-elevated w-full max-w-lg p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-nexboard-on-surface mb-4">Create New Project</h2>

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

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Description</label>
                                    <textarea v-model="form.description" rows="3" class="input-dark resize-none" placeholder="Project details..." />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Status</label>
                                    <select v-model="form.status" class="input-dark">
                                        <option v-for="(config, key) in statusConfig" :key="key" :value="key">{{ config.label }}</option>
                                    </select>
                                </div>
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
        <!-- Edit Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false" />
                    <div class="glass-card-elevated w-full max-w-lg p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-nexboard-on-surface mb-4">Edit Project</h2>

                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Project Name *</label>
                                <input v-model="editForm.name" type="text" class="input-dark" placeholder="e.g., E-Commerce Redesign" required />
                                <p v-if="editForm.errors.name" class="text-xs text-red-400 mt-1">{{ editForm.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Client Name *</label>
                                    <input v-model="editForm.client_name" type="text" class="input-dark" placeholder="e.g., PT Maju Jaya" required />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Client Email</label>
                                    <input v-model="editForm.client_email" type="email" class="input-dark" placeholder="client@email.com" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Description</label>
                                    <textarea v-model="editForm.description" rows="3" class="input-dark resize-none" placeholder="Project details..." />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Status</label>
                                    <select v-model="editForm.status" class="input-dark">
                                        <option v-for="(config, key) in statusConfig" :key="key" :value="key">{{ config.label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Deadline</label>
                                    <input v-model="editForm.deadline" type="date" class="input-dark" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Budget (IDR)</label>
                                    <input v-model="editForm.budget" type="number" class="input-dark" placeholder="5000000" />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showEditModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" :disabled="editForm.processing" class="btn-primary">
                                    {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showConfirmModal"
            title="Delete Project"
            message="Are you sure you want to delete this project? All associated boards, tasks, and data will be permanently removed."
            confirm-text="Delete Project"
            @confirm="executeDelete"
            @cancel="showConfirmModal = false"
        />

    </AuthenticatedLayout>
</template>
