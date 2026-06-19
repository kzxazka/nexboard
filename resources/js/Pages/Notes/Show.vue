<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    note: Object,
    notes: Array,
});

const contentTextarea = ref(null);

const form = useForm({
    title: props.note.title,
    content: props.note.content || '',
    icon: props.note.icon || '📝',
});

let typingTimer;
const doneTypingInterval = 1000; // 1 second
const isSaving = ref(false);
const saveStatus = ref('Saved');

const autoSave = () => {
    saveStatus.value = 'Saving...';
    isSaving.value = true;
    
    axios.put(route('notes.update', props.note.id), {
        title: form.title,
        content: form.content,
        icon: form.icon,
    }).then(() => {
        saveStatus.value = 'Saved';
        isSaving.value = false;
    }).catch((error) => {
        saveStatus.value = 'Error saving';
        isSaving.value = false;
        console.error("Autosave error", error);
    });
};

const handleInput = () => {
    saveStatus.value = 'Unsaved changes...';
    clearTimeout(typingTimer);
    typingTimer = setTimeout(autoSave, doneTypingInterval);
    resizeTextarea();
};

const resizeTextarea = () => {
    if (contentTextarea.value) {
        contentTextarea.value.style.height = 'auto';
        contentTextarea.value.style.height = contentTextarea.value.scrollHeight + 'px';
    }
};

const deleteNote = () => {
    if (confirm('Are you sure you want to delete this note?')) {
        router.delete(route('notes.destroy', props.note.id));
    }
};

onMounted(() => {
    resizeTextarea();
});
</script>

<template>
    <Head :title="form.title || 'Untitled Note'" />

    <div class="h-screen flex bg-nexboard-surface text-nexboard-on-surface overflow-hidden">
        
        <!-- Sidebar -->
        <div class="w-64 border-r border-white/10 flex flex-col bg-[#141b2d] shrink-0">
            <div class="p-4 border-b border-white/10 flex items-center justify-between shrink-0">
                <Link :href="route('notes.index')" class="text-sm font-semibold text-slate-300 hover:text-nexboard-on-surface flex items-center gap-2 group transition-colors">
                    <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Brainstorm
                </Link>
            </div>
            
            <div class="flex-1 overflow-y-auto p-3 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-2">Your Notes</div>
                <Link
                    v-for="n in notes"
                    :key="n.id"
                    :href="route('notes.show', n.id)"
                    class="block px-3 py-2 rounded-lg text-sm transition-colors truncate"
                    :class="n.id === note.id ? 'bg-indigo-500/20 text-indigo-300' : 'text-slate-400 hover:bg-white/5 hover:text-nexboard-on-surface'"
                >
                    <span class="mr-2">{{ n.icon || '📄' }}</span> {{ n.title || 'Untitled' }}
                </Link>
            </div>
        </div>

        <!-- Editor Area -->
        <div class="flex-1 flex flex-col relative">
            <!-- Topbar -->
            <div class="h-14 border-b border-white/10 flex items-center justify-between px-6 shrink-0 bg-[#0b1326]/80 backdrop-blur-sm sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full" :class="isSaving ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></div>
                        {{ saveStatus }}
                    </span>
                </div>
                
                <div class="flex items-center gap-3">
                    <button @click="deleteNote" class="p-1.5 hover:bg-red-500/20 rounded-lg text-slate-400 hover:text-red-400 transition-colors" title="Delete Note">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto w-full scroll-smooth">
                <div class="max-w-3xl mx-auto py-16 px-8 sm:px-12 w-full">
                    
                    <!-- Icon Selector (Simple) -->
                    <div class="mb-6 group relative inline-block">
                        <input 
                            v-model="form.icon" 
                            @input="handleInput"
                            class="bg-transparent border-none text-5xl cursor-pointer hover:bg-white/5 rounded-xl p-2 transition-colors w-20 text-center outline-none focus:ring-0"
                            title="Change Icon"
                        />
                    </div>
                    
                    <!-- Title -->
                    <input
                        v-model="form.title"
                        @input="handleInput"
                        class="w-full bg-transparent border-none text-4xl md:text-5xl font-bold text-nexboard-on-surface placeholder-slate-600 focus:ring-0 p-0 mb-6"
                        placeholder="Untitled Note"
                    />

                    <!-- Body -->
                    <textarea
                        ref="contentTextarea"
                        v-model="form.content"
                        @input="handleInput"
                        class="w-full bg-transparent border-none text-lg text-slate-300 placeholder-slate-600 focus:ring-0 p-0 min-h-[500px] resize-none leading-relaxed"
                        placeholder="Start typing your ideas here..."
                    ></textarea>

                </div>
            </div>
        </div>
        
    </div>
</template>

<style scoped>
/* Optional: Hide scrollbar for cleaner look like Notion */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
