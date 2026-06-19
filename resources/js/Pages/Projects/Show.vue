<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, nextTick, watch, computed } from 'vue';

const props = defineProps({
    project: Object,
    columns: Object,
    projects: Array,
});

// Dynamic columns setup
const columnNames = ref([]);
const localColumns = ref({});

const initColumns = () => {
    const colList = props.project.columns || ['To Do', 'In Progress', 'Review', 'Done'];
    columnNames.value = [...colList];
    
    const cols = {};
    colList.forEach(c => {
        cols[c] = [];
    });
    
    // Group props.columns into the dynamic columns
    Object.keys(props.columns).forEach(statusKey => {
        const tasks = props.columns[statusKey] || [];
        tasks.forEach(task => {
            let matchedCol = colList.find(c => c.toLowerCase() === statusKey.toLowerCase()) || 
                             colList.find(c => c.replace(/\s+/g, '_').toLowerCase() === statusKey.toLowerCase());
            if (!matchedCol) {
                if (statusKey === 'todo' && colList.includes('To Do')) matchedCol = 'To Do';
                else if (statusKey === 'in_progress' && colList.includes('In Progress')) matchedCol = 'In Progress';
                else matchedCol = colList[0];
            }
            if (cols[matchedCol]) {
                cols[matchedCol].push(task);
            }
        });
    });
    localColumns.value = cols;
};

initColumns();

// Watch for prop changes from outside and keep local state in sync
watch(() => props.columns, () => {
    initColumns();
}, { deep: true });

// Column Colors & Badges Helper
const getColumnColorClass = (colName) => {
    const name = colName.toLowerCase();
    if (name.includes('todo') || name.includes('to do')) return 'bg-slate-400';
    if (name.includes('progress') || name.includes('jalan')) return 'bg-indigo-500';
    if (name.includes('review') || name.includes('pending')) return 'bg-amber-500';
    if (name.includes('done') || name.includes('selesai')) return 'bg-emerald-500';
    return 'bg-purple-500'; // Default custom column color
};

// Priority Colors Configuration
const priorityConfig = {
    low: { label: 'Low', class: 'bg-slate-500/10 text-slate-400 border-slate-500/20' },
    medium: { label: 'Medium', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
    high: { label: 'High', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
    urgent: { label: 'Urgent', class: 'bg-red-500/10 text-red-400 border-red-500/20' },
};

// Board Name Editing
const isEditingBoardName = ref(false);
const editedBoardName = ref(props.project.name);
const saveBoardName = () => {
    if (!editedBoardName.value.trim() || editedBoardName.value === props.project.name) {
        isEditingBoardName.value = false;
        return;
    }
    const oldName = props.project.name;
    props.project.name = editedBoardName.value;
    isEditingBoardName.value = false;

    fetch(route('projects.update', props.project.id), {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ name: editedBoardName.value }),
    })
    .catch(err => {
        console.error(err);
        props.project.name = oldName;
        alert('Failed to update board name.');
    });
};

// Column Management
const showAddColumn = ref(false);
const newColumnName = ref('');
const submitAddColumn = () => {
    const name = newColumnName.value.trim();
    if (!name) {
        showAddColumn.value = false;
        return;
    }
    if (columnNames.value.includes(name)) {
        alert('Column already exists!');
        return;
    }
    
    columnNames.value.push(name);
    localColumns.value[name] = [];
    newColumnName.value = '';
    showAddColumn.value = false;
    saveColumnsList();
};

const editingColumnName = ref(null);
const editedColumnNameVal = ref('');
const startRenameColumn = (colName) => {
    editingColumnName.value = colName;
    editedColumnNameVal.value = colName;
    nextTick(() => {
        const input = document.getElementById(`col-input-${colName}`);
        if (input) input.focus();
    });
};

const submitRenameColumn = (oldName) => {
    const newName = editedColumnNameVal.value.trim();
    editingColumnName.value = null;
    if (!newName || newName === oldName) return;

    if (columnNames.value.includes(newName)) {
        alert('Column name already exists!');
        return;
    }

    const idx = columnNames.value.indexOf(oldName);
    if (idx !== -1) {
        columnNames.value[idx] = newName;
    }
    
    const tasks = localColumns.value[oldName] || [];
    tasks.forEach(t => t.status = newName);
    localColumns.value[newName] = tasks;
    delete localColumns.value[oldName];

    saveColumnsList();
};

const deleteColumn = (colName) => {
    if (!confirm(`Delete column "${colName}" and all its tasks?`)) return;

    const tasksToDelete = localColumns.value[colName] || [];
    columnNames.value = columnNames.value.filter(c => c !== colName);
    delete localColumns.value[colName];
    
    saveColumnsList();

    tasksToDelete.forEach(task => {
        fetch(route('tasks.destroy', task.id), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            }
        });
    });
};

const saveColumnsList = () => {
    fetch(route('projects.update', props.project.id), {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ columns: columnNames.value }),
    })
    .catch(err => {
        console.error('Failed to update columns:', err);
    });
};

// Task Creation
const showAddTask = ref({});
const taskForm = useForm({
    project_id: props.project.id,
    title: '',
    description: '',
    status: '',
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

    // Optimistic UI update
    const tempId = 'temp-' + Date.now();
    const tempTask = {
        id: tempId,
        title: newTitle,
        description: '',
        status: status,
        priority: 'medium',
        project_id: props.project.id,
        due_date: null,
        checklist: [],
        comments: []
    };
    localColumns.value[status].push(tempTask);

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
        const index = localColumns.value[status].findIndex(t => t.id === tempId);
        if (index !== -1) {
            localColumns.value[status][index] = savedTask;
        }
    })
    .catch(err => {
        console.error('Error saving task:', err);
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

    // Move task locally
    localColumns.value[oldStatus] = localColumns.value[oldStatus].filter(t => t.id !== task.id);
    task.status = newStatus;
    localColumns.value[newStatus].push(task);

    // Re-index cards in the new column
    const tasksToUpdate = localColumns.value[newStatus].map((t, index) => ({
        id: t.id,
        status: newStatus,
        order: index + 1
    }));

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

// Delete task
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
            localColumns.value[statusFound] = originalList;
            alert('Failed to delete task.');
        });
    }
};

// Card Detail Modal
const activeTask = ref(null);
const showDetailModal = ref(false);
const newChecklistItemText = ref('');
const newCommentText = ref('');

const openDetailModal = (task) => {
    activeTask.value = JSON.parse(JSON.stringify(task));
    if (!activeTask.value.checklist) activeTask.value.checklist = [];
    if (!activeTask.value.comments) activeTask.value.comments = [];
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    activeTask.value = null;
};

// Progress checklist
const checklistProgress = computed(() => {
    if (!activeTask.value || !activeTask.value.checklist || activeTask.value.checklist.length === 0) return 0;
    const completed = activeTask.value.checklist.filter(item => item.completed).length;
    return Math.round((completed / activeTask.value.checklist.length) * 100);
});

// Update single task in DB and sync local columns
const updateActiveTask = () => {
    if (!activeTask.value) return;
    
    // Save locally
    const colTasks = localColumns.value[activeTask.value.status] || [];
    const idx = colTasks.findIndex(t => t.id === activeTask.value.id);
    if (idx !== -1) {
        colTasks[idx] = JSON.parse(JSON.stringify(activeTask.value));
    }

    fetch(route('tasks.update', activeTask.value.id), {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({
            title: activeTask.value.title,
            description: activeTask.value.description,
            status: activeTask.value.status,
            priority: activeTask.value.priority,
            due_date: activeTask.value.due_date,
            checklist: activeTask.value.checklist,
            comments: activeTask.value.comments,
        })
    })
    .catch(err => {
        console.error('Error updating task:', err);
    });
};

const toggleChecklistItem = (item) => {
    item.completed = !item.completed;
    logActivity(`${item.completed ? 'completed' : 'uncompleted'} checklist item: "${item.text}"`);
    updateActiveTask();
};

const addChecklistItem = () => {
    const text = newChecklistItemText.value.trim();
    if (!text) return;
    const nextId = activeTask.value.checklist.length ? Math.max(...activeTask.value.checklist.map(i => i.id)) + 1 : 1;
    
    activeTask.value.checklist.push({
        id: nextId,
        text: text,
        completed: false
    });
    newChecklistItemText.value = '';
    logActivity(`added checklist item: "${text}"`);
    updateActiveTask();
};

const deleteChecklistItem = (itemId) => {
    const item = activeTask.value.checklist.find(i => i.id === itemId);
    activeTask.value.checklist = activeTask.value.checklist.filter(i => i.id !== itemId);
    if (item) {
        logActivity(`removed checklist item: "${item.text}"`);
    }
    updateActiveTask();
};

const addComment = () => {
    const text = newCommentText.value.trim();
    if (!text) return;
    const nextId = activeTask.value.comments.length ? Math.max(...activeTask.value.comments.map(c => c.id)) + 1 : 1;
    
    activeTask.value.comments.unshift({
        id: nextId,
        user: 'Azka Atqiya',
        text: text,
        date: new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        is_comment: true
    });
    newCommentText.value = '';
    updateActiveTask();
};

const logActivity = (actionText) => {
    const nextId = activeTask.value.comments.length ? Math.max(...activeTask.value.comments.map(c => c.id)) + 1 : 1;
    activeTask.value.comments.unshift({
        id: nextId,
        user: 'Azka Atqiya',
        text: actionText,
        date: new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        is_comment: false
    });
};

// Card Quick Editor
const activeQuickEditTask = ref(null);
const quickEditTitle = ref('');
const quickEditX = ref(0);
const quickEditY = ref(0);
const quickEditWidth = ref(0);
const isQuickEditOnRight = ref(true);

const openQuickEditor = (task, event) => {
    const cardEl = event.currentTarget.closest('.task-card-el').getBoundingClientRect();
    activeQuickEditTask.value = JSON.parse(JSON.stringify(task));
    quickEditTitle.value = task.title;
    quickEditX.value = cardEl.left;
    quickEditY.value = cardEl.top;
    quickEditWidth.value = cardEl.width;
    isQuickEditOnRight.value = cardEl.left < window.innerWidth - 320;
};

const saveQuickEdit = () => {
    if (!quickEditTitle.value.trim()) return;
    
    const task = activeQuickEditTask.value;
    task.title = quickEditTitle.value;
    
    // Update locally
    const colTasks = localColumns.value[task.status] || [];
    const idx = colTasks.findIndex(t => t.id === task.id);
    if (idx !== -1) {
        colTasks[idx].title = task.title;
    }
    
    fetch(route('tasks.update', task.id), {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify(task)
    });
    
    activeQuickEditTask.value = null;
};

const quickOpenDetail = () => {
    const task = activeQuickEditTask.value;
    activeQuickEditTask.value = null;
    openDetailModal(task);
};

const quickEditDate = () => {
    const date = prompt("Enter due date (YYYY-MM-DD) or leave empty to clear:", activeQuickEditTask.value.due_date ? activeQuickEditTask.value.due_date.substring(0,10) : "");
    if (date !== null) {
        const task = activeQuickEditTask.value;
        task.due_date = date ? date : null;
        
        const colTasks = localColumns.value[task.status] || [];
        const idx = colTasks.findIndex(t => t.id === task.id);
        if (idx !== -1) {
            colTasks[idx].due_date = task.due_date;
        }
        
        fetch(route('tasks.update', task.id), {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(task)
        });
    }
};

const quickEditPriority = () => {
    const priorities = ['low', 'medium', 'high', 'urgent'];
    const currentIdx = priorities.indexOf(activeQuickEditTask.value.priority);
    const nextIdx = (currentIdx + 1) % priorities.length;
    const nextPriority = priorities[nextIdx];
    
    const task = activeQuickEditTask.value;
    task.priority = nextPriority;
    
    const colTasks = localColumns.value[task.status] || [];
    const idx = colTasks.findIndex(t => t.id === task.id);
    if (idx !== -1) {
        colTasks[idx].priority = nextPriority;
    }
    
    fetch(route('tasks.update', task.id), {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify(task)
    });
};

const quickMoveCard = () => {
    const options = columnNames.value.map((name, i) => `${i + 1}. ${name}`).join('\n');
    const choice = prompt(`Select column to move to:\n${options}`, "1");
    if (choice) {
        const idx = parseInt(choice) - 1;
        if (idx >= 0 && idx < columnNames.value.length) {
            const targetCol = columnNames.value[idx];
            const task = activeQuickEditTask.value;
            const oldCol = task.status;
            
            if (oldCol !== targetCol) {
                localColumns.value[oldCol] = localColumns.value[oldCol].filter(t => t.id !== task.id);
                task.status = targetCol;
                localColumns.value[targetCol].push(task);
                
                fetch(route('tasks.update', task.id), {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify(task)
                });
                
                activeQuickEditTask.value = null;
            }
        }
    }
};

const quickDeleteCard = () => {
    const task = activeQuickEditTask.value;
    if (confirm(`Delete card "${task.title}"?`)) {
        deleteTask(task.id);
        activeQuickEditTask.value = null;
    }
};

// Board Dropdown & creation
const showBoardDropdown = ref(false);
const showCreateBoardModal = ref(false);
const boardForm = useForm({
    name: '',
    client_name: '',
    budget: '',
    deadline: '',
});

const submitCreateBoard = () => {
    boardForm.post(route('projects.store'), {
        onSuccess: () => {
            showCreateBoardModal.value = false;
            boardForm.reset();
        }
    });
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
            <!-- Board Title Section -->
            <div class="flex items-center gap-3 w-full">
                <Link :href="route('projects.index')" class="p-1.5 rounded-nex text-nexboard-on-surface-variant hover:text-nexboard-on-surface hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                
                <!-- Inline Editable Board Name -->
                <div class="flex items-center gap-2 relative">
                    <div v-if="isEditingBoardName" class="flex items-center">
                        <input
                            v-model="editedBoardName"
                            @keyup.enter="saveBoardName"
                            @blur="saveBoardName"
                            type="text"
                            class="bg-nexboard-surface-container-high border-0 focus:ring-2 focus:ring-indigo-500 rounded-lg text-lg font-semibold text-nexboard-on-surface px-2 py-0.5 w-64 focus:outline-none"
                            ref="boardNameInput"
                        />
                    </div>
                    <div v-else class="flex items-center gap-1.5 group">
                        <h1 
                            @dblclick="isEditingBoardName = true" 
                            class="text-xl font-semibold text-nexboard-on-surface tracking-wide cursor-pointer hover:bg-white/5 px-2 py-0.5 rounded-md transition-all duration-150"
                        >
                            {{ project.name }}
                        </h1>
                        <button @click="isEditingBoardName = true" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-white/5 text-slate-400 hover:text-nexboard-on-surface transition-opacity">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Board Switcher Dropdown Toggle -->
                    <button 
                        @click="showBoardDropdown = !showBoardDropdown" 
                        class="p-1 rounded hover:bg-white/5 text-slate-400 hover:text-nexboard-on-surface transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Board Switcher Dropdown Content -->
                    <div 
                        v-if="showBoardDropdown" 
                        class="absolute top-10 left-2 w-72 bg-nexboard-surface-container-high border border-white/10 rounded-xl shadow-2xl z-[80] p-3 text-sm"
                        @mouseleave="showBoardDropdown = false"
                    >
                        <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                            <span class="font-semibold text-nexboard-on-surface tracking-wide text-xs uppercase">Your Boards</span>
                            <button 
                                @click="showCreateBoardModal = true; showBoardDropdown = false" 
                                class="text-xs text-indigo-400 hover:text-indigo-300 font-medium"
                            >
                                + Add Board
                            </button>
                        </div>
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            <Link 
                                v-for="b in projects" 
                                :key="b.id" 
                                :href="route('projects.show', b.id)" 
                                :class="[
                                    'block w-full text-left px-2.5 py-1.5 rounded-lg transition-colors',
                                    b.id === project.id 
                                        ? 'bg-indigo-500/20 text-nexboard-on-surface font-medium border-l-2 border-indigo-500' 
                                        : 'text-slate-300 hover:bg-white/5 hover:text-nexboard-on-surface'
                                ]"
                            >
                                {{ b.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <template #actions>
            <button 
                @click="showCreateBoardModal = true" 
                class="btn-primary py-1.5 px-4 text-xs font-semibold flex items-center gap-1"
            >
                <span>+ New Board</span>
            </button>
        </template>

        <!-- Kanban Board Area -->
        <div class="page-transition h-[calc(100vh-10rem)]">
            <div class="flex gap-5 h-full overflow-x-auto pb-4 items-start select-none">
                
                <!-- Dynamic Columns -->
                <div
                    v-for="status in columnNames"
                    :key="status"
                    class="flex flex-col w-72 min-w-[288px] max-h-full bg-nexboard-surface-container-low/75 border border-white/5 rounded-xl p-3 shadow-lg shrink-0"
                    @dragover="onDragOver($event, status)"
                    @dragleave="onDragLeave"
                    @drop="onDrop($event, status)"
                >
                    <!-- Column Header -->
                    <div class="flex items-center gap-2 mb-3 px-1.5 group cursor-default">
                        <div :class="getColumnColorClass(status)" class="w-2.5 h-2.5 rounded-full shrink-0" />
                        
                        <!-- Inline Edit Column Name -->
                        <div v-if="editingColumnName === status" class="flex-1">
                            <input
                                v-model="editedColumnNameVal"
                                :id="`col-input-${status}`"
                                @keyup.enter="submitRenameColumn(status)"
                                @blur="submitRenameColumn(status)"
                                type="text"
                                class="bg-nexboard-surface-container-high border-0 focus:ring-1 focus:ring-indigo-500 text-xs font-semibold text-nexboard-on-surface px-1.5 py-0.5 rounded w-full focus:outline-none"
                            />
                        </div>
                        <span 
                            v-else
                            @dblclick="startRenameColumn(status)"
                            class="text-sm font-semibold text-nexboard-on-surface tracking-wide truncate hover:bg-white/5 px-1.5 py-0.5 rounded cursor-pointer"
                        >
                            {{ status }}
                        </span>

                        <span class="text-[11px] font-bold text-nexboard-on-surface-variant bg-white/5 px-2 py-0.5 rounded-full ml-auto">
                            {{ (localColumns[status] || []).length }}
                        </span>
                        
                        <!-- Column actions -->
                        <button 
                            @click="deleteColumn(status)" 
                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition-all shrink-0"
                            title="Delete List"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Cards Container -->
                    <div
                        :class="[
                            'flex-1 space-y-2 p-1 rounded-lg transition-all duration-200 overflow-y-auto min-h-[50px] max-h-[calc(100vh-20rem)]',
                            dragOverColumn === status
                                ? 'bg-indigo-500/10 border border-dashed border-indigo-500/30'
                                : 'border border-transparent'
                        ]"
                    >
                        <!-- Task Card -->
                        <div
                            v-for="task in (localColumns[status] || [])"
                            :key="task.id"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @dragend="onDragEnd"
                            @click="openDetailModal(task)"
                            class="task-card-el bg-nexboard-surface-container-high/90 hover:bg-nexboard-surface-container-high border border-white/5 p-3 rounded-lg cursor-grab active:cursor-grabbing hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/35 group relative"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm text-nexboard-on-surface font-medium leading-snug tracking-wide">{{ task.title }}</p>
                                
                                <!-- Hover edit card icon -->
                                <button
                                    @click.prevent.stop="openQuickEditor(task, $event)"
                                    class="opacity-0 group-hover:opacity-100 p-1 rounded bg-black/40 hover:bg-black/60 text-slate-300 hover:text-nexboard-on-surface transition-all shrink-0"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            </div>
                            
                            <p v-if="task.description" class="text-xs text-nexboard-on-surface-variant mt-1.5 line-clamp-2 leading-relaxed">
                                {{ task.description }}
                            </p>
                            
                            <!-- Indicators (Checklist indicator + Priority) -->
                            <div class="flex items-center gap-2 mt-3">
                                <span
                                    :class="[
                                        'text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border shrink-0',
                                        priorityConfig[task.priority]?.class || 'bg-slate-500/10 text-slate-400'
                                    ]"
                                >
                                    {{ priorityConfig[task.priority]?.label || 'Medium' }}
                                </span>

                                <!-- Checklist completion indicator -->
                                <div 
                                    v-if="task.checklist && task.checklist.length > 0" 
                                    class="flex items-center gap-1 text-[10px] bg-emerald-500/15 border border-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded shrink-0 font-medium"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>
                                        {{ task.checklist.filter(i => i.completed).length }}/{{ task.checklist.length }}
                                    </span>
                                </div>

                                <span v-if="task.due_date" class="text-[10px] text-nexboard-on-surface-variant ml-auto flex items-center gap-1 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
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
                                class="input-dark text-sm w-full focus:ring-1 focus:ring-indigo-500"
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

                <!-- Add List Button -->
                <div class="w-72 min-w-[288px] bg-nexboard-surface-container-low/40 border border-dashed border-white/10 hover:border-white/20 rounded-xl p-3 shrink-0">
                    <div v-if="showAddColumn">
                        <input
                            v-model="newColumnName"
                            @keyup.enter="submitAddColumn"
                            @keyup.esc="showAddColumn = false"
                            type="text"
                            class="input-dark text-sm w-full focus:ring-1 focus:ring-indigo-500"
                            placeholder="Enter list title..."
                            ref="newColInput"
                        />
                        <div class="flex gap-2 mt-2">
                            <button @click="submitAddColumn" class="btn-primary text-xs py-1.5 flex-1">Add List</button>
                            <button @click="showAddColumn = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                        </div>
                    </div>
                    <button
                        v-else
                        @click="showAddColumn = true"
                        class="w-full py-2.5 text-xs text-slate-300 hover:text-nexboard-on-surface transition-colors flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add another list
                    </button>
                </div>

            </div>
        </div>

        <!-- QUICK CARD EDITOR (Trello Style Context Menu) -->
        <div 
            v-if="activeQuickEditTask" 
            class="fixed inset-0 bg-black/75 z-[90] backdrop-blur-xs flex overflow-y-auto" 
            @click.self="activeQuickEditTask = null"
        >
            <div 
                class="absolute flex gap-4 items-start" 
                :class="isQuickEditOnRight ? 'flex-row' : 'flex-row-reverse'"
                :style="{ 
                    left: `${quickEditX}px`, 
                    top: `${quickEditY}px`, 
                    width: `${quickEditWidth}px` 
                }"
            >
                <!-- Text Area Editor -->
                <div class="flex-1 bg-nexboard-surface-container-high rounded-xl p-3 shadow-2xl border border-white/10">
                    <textarea
                        v-model="quickEditTitle"
                        rows="3"
                        class="w-full bg-transparent text-nexboard-on-surface text-sm font-medium border-0 focus:ring-0 focus:outline-none p-0 resize-none"
                        placeholder="Card title..."
                        @keyup.enter.prevent="saveQuickEdit"
                    ></textarea>
                    <button 
                        @click="saveQuickEdit" 
                        class="mt-2 btn-primary py-1 px-4 text-xs font-semibold shadow-md"
                    >
                        Save
                    </button>
                </div>
                
                <!-- Action Sidebar -->
                <div class="flex flex-col gap-1 w-44 shrink-0">
                    <button @click="quickOpenDetail" class="quick-edit-btn">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>Open card</span>
                    </button>
                    <button @click="quickEditDate" class="quick-edit-btn">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Edit dates</span>
                    </button>
                    <button @click="quickEditPriority" class="quick-edit-btn">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Change priority</span>
                    </button>
                    <button @click="quickMoveCard" class="quick-edit-btn">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span>Move card</span>
                    </button>
                    <button @click="quickDeleteCard" class="quick-edit-btn text-red-400 hover:bg-red-500/20 hover:text-red-300">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Archive (Delete)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- DETAILED CARD MODAL (Trello Card Details Popup) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div 
                    v-if="showDetailModal && activeTask" 
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto"
                    @click.self="closeDetailModal"
                >
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeDetailModal" />
                    <div class="glass-card-elevated border border-white/10 rounded-2xl w-full max-w-3xl shadow-2xl p-6 relative flex flex-col md:flex-row gap-6 max-h-[90vh] overflow-y-auto animate-slide-up z-10">
                
                <!-- Close Button -->
                <button 
                    @click="closeDetailModal" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-nexboard-on-surface p-1 hover:bg-white/5 rounded-full transition-colors z-20"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Left Content Area (Title, Checklist, Description) -->
                <div class="flex-1 space-y-6">
                    <!-- Header/Title -->
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-indigo-400 mt-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="flex-1">
                            <input
                                v-model="activeTask.title"
                                @blur="updateActiveTask"
                                @keyup.enter="updateActiveTask"
                                type="text"
                                class="w-full bg-transparent text-xl font-semibold text-nexboard-on-surface border-0 focus:ring-0 focus:outline-none p-0 border-b border-transparent hover:border-white/15 focus:border-indigo-500 focus:bg-white/5 px-1.5 py-0.5 rounded transition-all"
                            />
                            <p class="text-xs text-slate-400 mt-1">
                                in column <span class="text-slate-300 font-medium">{{ activeTask.status }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-nexboard-on-surface font-semibold">
                            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span>Description</span>
                        </div>
                        <textarea
                            v-model="activeTask.description"
                            @blur="updateActiveTask"
                            rows="4"
                            class="w-full bg-white/5 border border-white/5 hover:border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl p-3 text-sm text-slate-300 focus:outline-none placeholder-slate-500"
                            placeholder="Add a more detailed description..."
                        ></textarea>
                    </div>

                    <!-- Checklist -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-nexboard-on-surface font-semibold">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span>Checklist</span>
                            </div>
                            <span class="text-xs text-indigo-400 font-bold bg-indigo-500/10 px-2 py-0.5 rounded-full">
                                {{ checklistProgress }}%
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                            <div 
                                :class="checklistProgress === 100 ? 'bg-emerald-500' : 'bg-indigo-500'" 
                                class="h-full transition-all duration-300" 
                                :style="{ width: `${checklistProgress}%` }"
                            ></div>
                        </div>

                        <!-- Checklist Items -->
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            <div 
                                v-for="item in activeTask.checklist" 
                                :key="item.id" 
                                class="flex items-center justify-between gap-3 group/item py-1.5 px-2 rounded-lg hover:bg-white/5 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        :checked="item.completed"
                                        @change="toggleChecklistItem(item)"
                                        class="w-4.5 h-4.5 rounded-md border-white/20 bg-transparent text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 transition-colors"
                                    />
                                    <span 
                                        :class="[
                                            'text-sm transition-all',
                                            item.completed ? 'line-through text-slate-500 font-normal' : 'text-slate-300 font-medium'
                                        ]"
                                    >
                                        {{ item.text }}
                                    </span>
                                </div>
                                <button 
                                    @click="deleteChecklistItem(item.id)"
                                    class="opacity-0 group-hover/item:opacity-100 p-1 rounded hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition-all"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Add checklist item form -->
                        <div class="flex gap-2">
                            <input
                                v-model="newChecklistItemText"
                                @keyup.enter="addChecklistItem"
                                type="text"
                                class="input-dark text-xs flex-1 focus:ring-1 focus:ring-indigo-500"
                                placeholder="Add checklist item..."
                            />
                            <button 
                                @click="addChecklistItem" 
                                class="btn-primary text-xs py-1.5 px-4 font-semibold"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Side Actions & Comments panel -->
                <div class="w-full md:w-64 space-y-6">
                    
                    <!-- Task Settings (Priority / Date) -->
                    <div class="bg-white/5 border border-white/5 rounded-xl p-4 space-y-4">
                        <h3 class="text-nexboard-on-surface font-bold text-xs tracking-wider uppercase">Card Info</h3>
                        
                        <!-- Priority Selector -->
                        <div class="space-y-1">
                            <label class="text-xs text-slate-400 block">Priority</label>
                            <select 
                                v-model="activeTask.priority"
                                @change="updateActiveTask"
                                class="w-full bg-nexboard-surface-container-high border-white/10 rounded-lg text-xs text-nexboard-on-surface focus:ring-1 focus:ring-indigo-500 py-1.5"
                            >
                                <option value="low" class="bg-nexboard-surface-container-high text-nexboard-on-surface">Low</option>
                                <option value="medium" class="bg-nexboard-surface-container-high text-nexboard-on-surface">Medium</option>
                                <option value="high" class="bg-nexboard-surface-container-high text-nexboard-on-surface">High</option>
                                <option value="urgent" class="bg-nexboard-surface-container-high text-nexboard-on-surface">Urgent</option>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div class="space-y-1">
                            <label class="text-xs text-slate-400 block">Due Date</label>
                            <input 
                                type="date" 
                                v-model="activeTask.due_date"
                                @change="updateActiveTask"
                                class="w-full bg-nexboard-surface-container-high border-white/10 rounded-lg text-xs text-nexboard-on-surface focus:ring-1 focus:ring-indigo-500 py-1.5"
                            />
                        </div>
                    </div>

                    <!-- Comments and Activity Feed -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-nexboard-on-surface font-semibold">
                            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                            <span>Activity</span>
                        </div>

                        <!-- Write comment form -->
                        <div class="space-y-2">
                            <textarea
                                v-model="newCommentText"
                                rows="2"
                                class="w-full bg-white/5 border border-white/5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg p-2 text-xs text-slate-300 focus:outline-none placeholder-slate-500 resize-none"
                                placeholder="Write a comment..."
                            ></textarea>
                            <button 
                                @click="addComment" 
                                class="btn-primary text-xs py-1 px-3 font-semibold shadow-md block ml-auto"
                            >
                                Comment
                            </button>
                        </div>

                        <!-- Activity feed stream -->
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                            <div 
                                v-for="c in activeTask.comments" 
                                :key="c.id" 
                                class="flex gap-2 items-start"
                            >
                                <!-- Avatar -->
                                <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-[10px] font-bold text-indigo-400 flex items-center justify-center shrink-0">
                                    {{ c.user.split(' ').map(n => n[0]).join('') }}
                                </div>
                                
                                <div class="flex-1 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-semibold text-nexboard-on-surface">{{ c.user }}</span>
                                        <span class="text-[9px] text-slate-500">{{ c.date }}</span>
                                    </div>
                                    <p 
                                        :class="[
                                            'mt-1 leading-relaxed',
                                            c.is_comment ? 'bg-white/5 border border-white/5 p-2 rounded-lg text-slate-300 font-medium' : 'text-slate-400 italic'
                                        ]"
                                    >
                                        {{ c.text }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
                </div>
            </Transition>
        </Teleport>

        <!-- CREATE NEW BOARD MODAL -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div 
                    v-if="showCreateBoardModal" 
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                    @click.self="showCreateBoardModal = false"
                >
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateBoardModal = false" />
                    <div class="glass-card-elevated border border-white/10 rounded-2xl w-full max-w-md shadow-2xl p-6 relative animate-slide-up z-10">
                        <button 
                            @click="showCreateBoardModal = false" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-nexboard-on-surface p-1 hover:bg-white/5 rounded-full transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h2 class="text-lg font-bold text-nexboard-on-surface mb-4 tracking-wide">Create New Board</h2>
                
                <form @submit.prevent="submitCreateBoard" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-300">Board Name</label>
                        <input 
                            v-model="boardForm.name" 
                            type="text" 
                            class="input-dark w-full text-sm" 
                            placeholder="e.g. Website Redesign"
                            required
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-300">Client Name (Optional)</label>
                        <input 
                            v-model="boardForm.client_name" 
                            type="text" 
                            class="input-dark w-full text-sm" 
                            placeholder="e.g. Internal"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-300">Budget (Optional)</label>
                            <input 
                                v-model="boardForm.budget" 
                                type="number" 
                                class="input-dark w-full text-sm" 
                                placeholder="0"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-300">Deadline (Optional)</label>
                            <input 
                                v-model="boardForm.deadline" 
                                type="date" 
                                class="input-dark w-full text-sm"
                            />
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="showCreateBoardModal = false" 
                            class="btn-secondary text-xs py-2 flex-1"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="btn-primary text-xs py-2 flex-1"
                            :disabled="boardForm.processing"
                        >
                            Create Board
                        </button>
                    </div>
                </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.quick-edit-btn {
    @apply flex items-center gap-2 w-full text-left bg-black/60 hover:bg-black/85 border border-white/5 hover:border-indigo-500/20 text-slate-200 hover:text-nexboard-on-surface px-3 py-1.5 rounded-lg text-xs font-medium backdrop-blur-md transition-all;
}
</style>
