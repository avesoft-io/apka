<script setup lang="ts">
withDefaults(
    defineProps<{
        size?: number;
    }>(),
    {
        size: 20,
    },
);
</script>

<template>
    <img
        src="/favicon.svg"
        alt=""
        :width="size"
        :height="size"
        class="shrink-0"
    />
</template>
