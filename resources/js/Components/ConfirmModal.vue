<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Are you sure?'
    },
    message: {
        type: String,
        default: 'This action cannot be undone.'
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    danger: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300"
            leave-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('cancel')" />
                <div class="glass-card-elevated w-full max-w-sm p-6 relative animate-slide-up z-10 flex flex-col items-center text-center">
                    
                    <div 
                        class="w-12 h-12 rounded-full flex items-center justify-center mb-4"
                        :class="danger ? 'bg-red-500/20 text-red-400' : 'bg-indigo-500/20 text-indigo-400'"
                    >
                        <svg v-if="danger" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-nexboard-on-surface mb-2">{{ title }}</h2>
                    <p class="text-sm text-slate-400 mb-6">{{ message }}</p>

                    <div class="flex gap-3 w-full">
                        <button @click="emit('cancel')" class="btn-secondary flex-1 py-2">{{ cancelText }}</button>
                        <button 
                            @click="emit('confirm')" 
                            class="flex-1 py-2 font-semibold text-sm rounded-lg transition-all shadow-md active:scale-[0.98]"
                            :class="danger ? 'bg-red-500 hover:bg-red-400 text-white shadow-red-500/20' : 'btn-primary'"
                        >
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
