<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium text-nexboard-on-surface">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
