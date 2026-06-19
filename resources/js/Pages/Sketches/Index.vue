<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ sketches: Array, projects: Array });
const showCreateModal = ref(false);
const form = useForm({ project_id: '', title: '' });

const submit = () => {
    form.post(route('sketches.store'), {
        onSuccess: () => { showCreateModal.value = false; form.reset(); },
    });
};

const deleteSketch = (id) => {
    if (confirm('Delete this sketch?')) router.delete(route('sketches.destroy', id));
};

const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
</script>

<template>
    <Head title="Whiteboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-semibold text-nexboard-on-surface">Whiteboard</h1>
                    <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Sketch and visualize ideas</p>
                </div>
                <button @click="showCreateModal = true" class="btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Sketch
                </button>
            </div>
        </template>

        <div class="page-transition">
            <div v-if="sketches.length === 0" class="glass-card p-12 text-center">
                <h3 class="text-lg font-semibold text-white mb-1">No sketches yet</h3>
                <p class="text-sm text-nexboard-on-surface-variant mb-4">Create your first whiteboard sketch.</p>
                <button @click="showCreateModal = true" class="btn-primary">Create Sketch</button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="(sketch, i) in sketches" :key="sketch.id" class="glass-card overflow-hidden hover:border-indigo-500/30 group animate-slide-up" :style="{ animationDelay: `${i * 50}ms` }">
                    <Link :href="route('sketches.show', sketch.id)" class="block aspect-video bg-nexboard-surface-container-lowest flex items-center justify-center">
                        <svg class="w-12 h-12 text-nexboard-outline-variant group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128z" /></svg>
                    </Link>
                    <div class="p-4 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <Link :href="route('sketches.show', sketch.id)" class="text-sm font-semibold text-white truncate block group-hover:text-indigo-400 transition-colors">{{ sketch.title }}</Link>
                            <p class="text-xs text-nexboard-on-surface-variant mt-1">{{ sketch.project?.name }} · {{ formatDate(sketch.created_at) }}</p>
                        </div>
                        <button @click="deleteSketch(sketch.id)" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-500/20 transition-all shrink-0 ml-2">
                            <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300" leave-active-class="transition-all duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="showCreateModal = false">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
                    <div class="glass-card-elevated w-full max-w-md p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-white mb-4">New Sketch</h2>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Project *</label>
                                <select v-model="form.project_id" class="input-dark" required>
                                    <option value="" disabled>Select a project</option>
                                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Title</label>
                                <input v-model="form.title" type="text" class="input-dark" placeholder="Untitled Sketch" />
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="btn-primary">{{ form.processing ? 'Creating...' : 'Open Canvas' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
