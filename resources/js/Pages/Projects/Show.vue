<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';

const props = defineProps({
    project: Object,
    columns: Object,
});

const columnConfig = {
    todo: { label: 'To Do', color: 'bg-nexboard-neutral-badge', accent: 'border-t-slate-500' },
    in_progress: { label: 'In Progress', color: 'bg-indigo-500', accent: 'border-t-indigo-500' },
    review: { label: 'Review', color: 'bg-nexboard-warning', accent: 'border-t-amber-500' },
    done: { label: 'Done', color: 'bg-nexboard-success', accent: 'border-t-emerald-500' },
};

const priorityConfig = {
    low: { label: 'Low', class: 'text-slate-400' },
    medium: { label: 'Medium', class: 'text-blue-400' },
    high: { label: 'High', class: 'text-amber-400' },
    urgent: { label: 'Urgent', class: 'text-red-400' },
};

// Task creation
const showAddTask = ref({});
const taskForm = useForm({
    project_id: props.project.id,
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
});

const startAddTask = (status) => {
    showAddTask.value[status] = true;
    taskForm.status = status;
    taskForm.title = '';
    nextTick(() => {
        const input = document.getElementById(`task-input-${status}`);
        if (input) input.focus();
    });
};

const submitTask = (status) => {
    if (!taskForm.title.trim()) {
        showAddTask.value[status] = false;
        return;
    }
    taskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.title = '';
            showAddTask.value[status] = false;
        },
    });
};

// Drag and drop
const draggedTask = ref(null);
const dragOverColumn = ref(null);

const onDragStart = (e, task) => {
    draggedTask.value = task;
    e.dataTransfer.effectAllowed = 'move';
    e.target.classList.add('opacity-50');
};

const onDragEnd = (e) => {
    e.target.classList.remove('opacity-50');
    dragOverColumn.value = null;
};

const onDragOver = (e, column) => {
    e.preventDefault();
    dragOverColumn.value = column;
};

const onDragLeave = () => {
    dragOverColumn.value = null;
};

const onDrop = (e, newStatus) => {
    e.preventDefault();
    dragOverColumn.value = null;

    if (!draggedTask.value || draggedTask.value.status === newStatus) {
        draggedTask.value = null;
        return;
    }

    // Update via API
    const tasksToUpdate = [
        { id: draggedTask.value.id, status: newStatus, order: 0 }
    ];

    fetch(route('tasks.updatePositions'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ tasks: tasksToUpdate }),
    }).then(() => {
        router.reload({ preserveScroll: true });
    });

    draggedTask.value = null;
};

// Delete task
const deleteTask = (taskId) => {
    if (confirm('Delete this task?')) {
        router.delete(route('tasks.destroy', taskId), { preserveScroll: true });
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head :title="project.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('projects.index')" class="text-nexboard-on-surface-variant hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-nexboard-on-surface">{{ project.name }}</h1>
                    <p class="text-sm text-nexboard-on-surface-variant mt-0.5">{{ project.client_name }} — Kanban Board</p>
                </div>
            </div>
        </template>

        <div class="page-transition h-full">
            <!-- Kanban Columns -->
            <div class="flex gap-4 h-full overflow-x-auto pb-4">
                <div
                    v-for="(tasks, status) in columns"
                    :key="status"
                    class="flex flex-col w-[300px] min-w-[300px] shrink-0"
                    @dragover="onDragOver($event, status)"
                    @dragleave="onDragLeave"
                    @drop="onDrop($event, status)"
                >
                    <!-- Column Header -->
                    <div class="flex items-center gap-2 mb-3 px-1">
                        <div :class="columnConfig[status]?.color" class="w-2.5 h-2.5 rounded-full shrink-0" />
                        <span class="text-sm font-semibold text-white">{{ columnConfig[status]?.label }}</span>
                        <span class="text-xs text-nexboard-on-surface-variant bg-white/5 px-1.5 py-0.5 rounded-full ml-auto">
                            {{ tasks.length }}
                        </span>
                    </div>

                    <!-- Cards Container -->
                    <div
                        :class="[
                            'flex-1 space-y-2 p-2 rounded-nex-lg transition-colors duration-200 overflow-y-auto',
                            dragOverColumn === status
                                ? 'bg-indigo-500/5 border-2 border-dashed border-indigo-500/30'
                                : 'bg-nexboard-surface-container-lowest/50 border-2 border-transparent'
                        ]"
                    >
                        <!-- Task Card -->
                        <div
                            v-for="task in tasks"
                            :key="task.id"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @dragend="onDragEnd"
                            class="glass-card p-3 cursor-grab active:cursor-grabbing group border-t-2 transition-all duration-200 hover:border-indigo-500/30"
                            :class="columnConfig[status]?.accent"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm text-white font-medium leading-snug">{{ task.title }}</p>
                                <button
                                    @click.prevent="deleteTask(task.id)"
                                    class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-500/20 transition-all shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="task.description" class="text-xs text-nexboard-on-surface-variant mt-1 line-clamp-2">{{ task.description }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span :class="priorityConfig[task.priority]?.class" class="text-[10px] font-semibold uppercase tracking-wider">
                                    {{ priorityConfig[task.priority]?.label }}
                                </span>
                                <span v-if="task.due_date" class="text-[10px] text-nexboard-on-surface-variant ml-auto">
                                    {{ formatDate(task.due_date) }}
                                </span>
                            </div>
                        </div>

                        <!-- Add Task Form -->
                        <div v-if="showAddTask[status]" class="glass-card p-3">
                            <input
                                :id="`task-input-${status}`"
                                v-model="taskForm.title"
                                @keyup.enter="submitTask(status)"
                                @keyup.esc="showAddTask[status] = false"
                                type="text"
                                class="input-dark text-sm"
                                placeholder="Task title..."
                            />
                            <div class="flex gap-2 mt-2">
                                <button @click="submitTask(status)" class="btn-primary text-xs py-1.5 flex-1">Add</button>
                                <button @click="showAddTask[status] = false" class="btn-secondary text-xs py-1.5">Cancel</button>
                            </div>
                        </div>

                        <!-- Add Task Button -->
                        <button
                            v-else
                            @click="startAddTask(status)"
                            class="w-full py-2 rounded-nex border border-dashed border-white/10 text-xs text-nexboard-on-surface-variant hover:border-indigo-500/30 hover:text-indigo-400 transition-colors flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
