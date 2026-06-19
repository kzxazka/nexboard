<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    notes: Array,
});

const showCreateModal = ref(false);
const form = useForm({
    title: '',
});

const submit = () => {
    form.post(route('notes.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const showConfirmModal = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (id) => {
    itemToDelete.value = id;
    showConfirmModal.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(route('notes.destroy', itemToDelete.value), {
            onSuccess: () => {
                showConfirmModal.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head title="Brainstorming" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-nexboard-on-surface">Brainstorming</h1>
                <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Capture ideas and organize your thoughts</p>
            </div>
        </template>
        
        <template #actions>
            <button @click="showCreateModal = true" class="btn-primary gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Note
            </button>
        </template>

        <div class="page-transition">
            <!-- Empty State -->
            <div v-if="notes.length === 0" class="glass-card p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-nexboard-surface-container-high flex items-center justify-center mb-4">
                    <span class="text-3xl">💡</span>
                </div>
                <h3 class="text-lg font-semibold text-nexboard-on-surface mb-1">No notes yet</h3>
                <p class="text-sm text-nexboard-on-surface-variant mb-4">Create your first note to start brainstorming.</p>
                <button @click="showCreateModal = true" class="btn-primary">Create Note</button>
            </div>

            <!-- Notes Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <Link
                    v-for="(note, index) in notes"
                    :key="note.id"
                    :href="route('notes.show', note.id)"
                    class="glass-card overflow-hidden hover:border-indigo-500/30 group animate-slide-up transition-all flex flex-col h-48 relative"
                    :style="{ animationDelay: `${index * 50}ms` }"
                >
                    
                    <!-- Card Content -->
                    <div class="p-5 flex flex-col h-full z-10">
                        <div class="flex items-start justify-between mb-2">
                            <span class="text-2xl" v-if="note.icon">{{ note.icon }}</span>
                            <span class="text-2xl" v-else>📄</span>
                            
                            <button @click.prevent="confirmDelete(note.id)" class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition-all z-20 relative">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        
                        <h3 class="text-base font-semibold text-nexboard-on-surface truncate mb-1 group-hover:text-indigo-400 transition-colors">
                            {{ note.title }}
                        </h3>
                        
                        <p class="text-xs text-slate-500 flex-1 overflow-hidden">
                            {{ note.content ? note.content.substring(0, 100) + '...' : 'Empty note...' }}
                        </p>
                        
                        <div class="text-[10px] text-nexboard-on-surface-variant mt-4 pt-3 border-t border-white/5 font-medium tracking-wide uppercase">
                            {{ formatDate(note.updated_at) }}
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
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false" />
                    <div class="glass-card-elevated w-full max-w-md p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-nexboard-on-surface mb-4">Create New Note</h2>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Note Title *</label>
                                <input v-model="form.title" type="text" class="input-dark" placeholder="e.g., Marketing Ideas, Meeting Notes..." required />
                                <p v-if="form.errors.title" class="text-xs text-red-400 mt-1">{{ form.errors.title }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="btn-primary">
                                    {{ form.processing ? 'Creating...' : 'Create Note' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
        
        <ConfirmModal
            :show="showConfirmModal"
            title="Delete Note"
            message="Are you sure you want to delete this note? This action cannot be undone."
            confirm-text="Delete Note"
            @confirm="executeDelete"
            @cancel="showConfirmModal = false"
        />
    </AuthenticatedLayout>
</template>
