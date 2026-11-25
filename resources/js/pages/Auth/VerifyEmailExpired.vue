<script setup lang="ts">
import { create as login } from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import { home } from '@/routes';
import AppLogo from '@/components/AppLogo.vue';
import { buttonClass } from '@/lib/ui';
import { Head, Link, usePage } from '@inertiajs/vue3';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#FDFDFC] px-4 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <Head title="Link expired" />
        <div
            class="w-full max-w-sm rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <h1 class="text-lg font-semibold">
                <Link :href="home.url()" class="flex items-center gap-2">
                    <AppLogo />
                    {{ name }}
                </Link>
            </h1>
            <p class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                This verification link has expired. Links last 5 minutes. Sign
                in and request a new one.
            </p>
            <Link :href="login.url()" :class="buttonClass" class="mt-5 w-full">
                Log in
            </Link>
        </div>
    </div>
</template>
