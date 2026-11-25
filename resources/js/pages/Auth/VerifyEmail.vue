<script setup lang="ts">
import { destroy } from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import { send } from '@/actions/App/Http/Controllers/EmailVerificationController';
import { home } from '@/routes';
import AppLogo from '@/components/AppLogo.vue';
import { buttonClass, buttonSecondaryClass } from '@/lib/ui';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const name = computed(() => page.props.name);
const email = computed(() => page.props.auth.user?.email ?? '');
const success = computed(() => page.props.flash.success);

const resendForm = useForm({});
const logoutForm = useForm({});

function resend() {
    resendForm.post(send.url());
}

function logout() {
    logoutForm.post(destroy.url());
}
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#FDFDFC] px-4 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <Head title="Verify email" />
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
                We sent a verification link to {{ email }}. It expires in 5
                minutes.
            </p>
            <p v-if="success" class="mt-3 text-sm text-[#0f3d3e]">
                {{ success }}
            </p>
            <form class="mt-5" @submit.prevent="resend">
                <button
                    type="submit"
                    :class="buttonClass"
                    class="w-full"
                    :disabled="resendForm.processing"
                >
                    Send a new link
                </button>
            </form>
            <form class="mt-3" @submit.prevent="logout">
                <button
                    type="submit"
                    :class="buttonSecondaryClass"
                    class="w-full"
                    :disabled="logoutForm.processing"
                >
                    Log out
                </button>
            </form>
        </div>
    </div>
</template>
