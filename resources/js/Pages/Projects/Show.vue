<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, nextTick, watch } from 'vue';

const props = defineProps({
    project: Object,
    columns: Object,
});

// Create reactive copy of columns to avoid Inertia reloads and enable instant AJAX/Optimistic UI
const localColumns = ref(JSON.parse(JSON.stringify(props.columns)));

// Watch for prop changes from outside and keep local state in sync
watch(() => props.columns, (newVal) => {
    localColumns.value = JSON.parse(JSON.stringify(newVal));
}, { deep: true });

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

    const newTitle = taskForm.title;
    taskForm.title = '';
    showAddTask.value[status] = false;

    // Optimistic UI update: Insert temporary card instantly
    const tempId = 'temp-' + Date.now();
    const tempTask = {
        id: tempId,
        title: newTitle,
        description: '',
        status: status,
        priority: 'medium',
        project_id: props.project.id,
        due_date: null
    };
    localColumns.value[status].push(tempTask);

    // Send async AJAX request to server without blocking UI or reloading page
    fetch(route('tasks.store'), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({
            project_id: props.project.id,
            title: newTitle,
            status: status,
            priority: 'medium'
        })
    })
    .then(res => {
        if (!res.ok) throw new Error('Network error');
        return res.json();
    })
    .then(savedTask => {
        // Swap temp card with the actual saved task from DB containing real ID
        const index = localColumns.value[status].findIndex(t => t.id === tempId);
        if (index !== -1) {
            localColumns.value[status][index] = savedTask;
        }
    })
    .catch(err => {
        console.error('Error saving task:', err);
        // Rollback optimistic update on failure
        localColumns.value[status] = localColumns.value[status].filter(t => t.id !== tempId);
        alert('Failed to save task.');
    });
};

// Drag and drop logic
const draggedTask = ref(null);
const dragOverColumn = ref(null);

const onDragStart = (e, task) => {
    draggedTask.value = task;
    e.dataTransfer.effectAllowed = 'move';
    e.target.classList.add('opacity-40');
};

const onDragEnd = (e) => {
    e.target.classList.remove('opacity-40');
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

    const task = draggedTask.value;
    const oldStatus = task.status;

    // Move task locally instant
    localColumns.value[oldStatus] = localColumns.value[oldStatus].filter(t => t.id !== task.id);
    task.status = newStatus;
    localColumns.value[newStatus].push(task);

    // Re-index all cards in the new column for database ordering
    const tasksToUpdate = localColumns.value[newStatus].map((t, index) => ({
        id: t.id,
        status: newStatus,
        order: index + 1
    }));

    // Send async AJAX update to backend
    fetch(route('tasks.updatePositions'), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ tasks: tasksToUpdate }),
    })
    .catch(err => {
        console.error('Failed to update task positions:', err);
    });

    draggedTask.value = null;
};

// Delete task with immediate UI feedback
const deleteTask = (taskId) => {
    if (!confirm('Delete this task?')) return;

    let statusFound = null;
    for (const status in localColumns.value) {
        if (localColumns.value[status].some(t => t.id === taskId)) {
            statusFound = status;
            break;
        }
    }

    if (statusFound) {
        // Optimistic UI: Save copy & remove locally immediately
        const originalList = [...localColumns.value[statusFound]];
        localColumns.value[statusFound] = localColumns.value[statusFound].filter(t => t.id !== taskId);

        fetch(route('tasks.destroy', taskId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            }
        })
        .catch(err => {
            console.error('Delete failed:', err);
            // Rollback on failure
            localColumns.value[statusFound] = originalList;
            alert('Failed to delete task.');
        });
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
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('projects.index')" class="p-1.5 rounded-nex text-nexboard-on-surface-variant hover:text-white hover:bg-white/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold text-nexboard-on-surface tracking-wide">{{ project.name }}</h1>
                        <p class="text-sm text-nexboard-on-surface-variant mt-0.5">{{ project.client_name }} — Kanban Board</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="page-transition h-[calc(100vh-10rem)]">
            <!-- Kanban Columns (Trello Style Layout) -->
            <div class="flex gap-5 h-full overflow-x-auto pb-4 items-start select-none">
                <div
                    v-for="(tasks, status) in localColumns"
                    :key="status"
                    class="flex flex-col w-72 min-w-[288px] max-h-full bg-nexboard-surface-container-low/75 border border-white/5 rounded-xl p-3 shadow-lg shrink-0"
                    @dragover="onDragOver($event, status)"
                    @dragleave="onDragLeave"
                    @drop="onDrop($event, status)"
                >
                    <!-- Column Header -->
                    <div class="flex items-center gap-2 mb-3 px-1.5 cursor-default">
                        <div :class="columnConfig[status]?.color" class="w-2.5 h-2.5 rounded-full shrink-0" />
                        <span class="text-sm font-semibold text-white tracking-wide">{{ columnConfig[status]?.label }}</span>
                        <span class="text-[11px] font-bold text-nexboard-on-surface-variant bg-white/5 px-2 py-0.5 rounded-full ml-auto">
                            {{ tasks.length }}
                        </span>
                    </div>

                    <!-- Cards Container (Scrollable lists inside columns) -->
                    <div
                        :class="[
                            'flex-1 space-y-2 p-1 rounded-lg transition-all duration-200 overflow-y-auto min-h-[50px] max-h-[calc(100vh-20rem)]',
                            dragOverColumn === status
                                ? 'bg-indigo-500/10 border border-dashed border-indigo-500/30'
                                : 'border border-transparent'
                        ]"
                    >
                        <!-- Task Card (Trello look with raised surface, shadows, and spacing) -->
                        <div
                            v-for="task in tasks"
                            :key="task.id"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @dragend="onDragEnd"
                            class="bg-nexboard-surface-container-high/90 hover:bg-nexboard-surface-container-high border border-white/5 p-3 rounded-lg cursor-grab active:cursor-grabbing hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/35 group relative"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm text-white font-medium leading-snug tracking-wide">{{ task.title }}</p>
                                <button
                                    @click.prevent="deleteTask(task.id)"
                                    class="opacity-0 group-hover:opacity-100 p-1.5 rounded hover:bg-red-500/20 text-red-400 hover:text-red-300 transition-all shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="task.description" class="text-xs text-nexboard-on-surface-variant mt-1.5 line-clamp-2 leading-relaxed">
                                {{ task.description }}
                            </p>
                            <div class="flex items-center gap-2 mt-3">
                                <span
                                    :class="[
                                        'text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border',
                                        task.priority === 'low' ? 'bg-slate-500/10 text-slate-400 border-slate-500/20' : '',
                                        task.priority === 'medium' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : '',
                                        task.priority === 'high' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        task.priority === 'urgent' ? 'bg-red-500/10 text-red-400 border-red-500/20' : ''
                                    ]"
                                >
                                    {{ priorityConfig[task.priority]?.label || 'Medium' }}
                                </span>
                                <span v-if="task.due_date" class="text-[10px] text-nexboard-on-surface-variant ml-auto flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatDate(task.due_date) }}
                                </span>
                            </div>
                        </div>

                        <!-- Add Task Form -->
                        <div v-if="showAddTask[status]" class="bg-nexboard-surface-container-high/60 border border-white/5 p-3 rounded-lg">
                            <input
                                :id="`task-input-${status}`"
                                v-model="taskForm.title"
                                @keyup.enter="submitTask(status)"
                                @keyup.esc="showAddTask[status] = false"
                                type="text"
                                class="input-dark text-sm w-full"
                                placeholder="Task title..."
                            />
                            <div class="flex gap-2 mt-2">
                                <button @click="submitTask(status)" class="btn-primary text-xs py-1.5 flex-1">Add Card</button>
                                <button @click="showAddTask[status] = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Task Button -->
                    <button
                        v-if="!showAddTask[status]"
                        @click="startAddTask(status)"
                        class="w-full mt-2 py-2 rounded-lg border border-dashed border-white/10 hover:border-indigo-500/30 text-xs text-nexboard-on-surface-variant hover:text-indigo-400 transition-colors flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Card
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
