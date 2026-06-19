<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-nexboard-on-surface-variant hover:text-nexboard-on-surface hover:bg-white/5 focus:bg-white/5 focus:outline-none transition duration-150 ease-in-out"
    >
        <slot />
    </Link>
</template>
