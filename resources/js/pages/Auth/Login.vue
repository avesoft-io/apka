<script setup lang="ts">
import {
    email as checkEmail,
    store,
} from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import { create as register } from '@/actions/App/Http/Controllers/RegisteredUserController';
import { home } from '@/routes';
import AppLogo from '@/components/AppLogo.vue';
import FormError from '@/components/FormError.vue';
import { buttonClass, inputClass } from '@/lib/ui';
import { Head, Link, useForm, useHttp, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const name = usePage().props.name;

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const clientEmailError = ref<string>();
const emailCheck = useHttp<{ email: string }, { message: string | null }>({
    email: '',
});
let emailCheckId = 0;

function clearEmailError() {
    emailCheckId += 1;
    emailCheck.cancel();
    clientEmailError.value = undefined;
}

async function verifyEmail() {
    if (form.email.length === 0) {
        clearEmailError();

        return;
    }

    const check = ++emailCheckId;
    emailCheck.email = form.email;

    try {
        const response = await emailCheck.post(checkEmail.url());

        if (check !== emailCheckId) {
            return;
        }

        clientEmailError.value = response.message ?? undefined;
    } catch {
        if (check === emailCheckId) {
            clientEmailError.value = undefined;
        }
    }
}

function submit() {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#FDFDFC] px-4 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <Head title="Log in" />
        <form
            class="w-full max-w-sm rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
            @submit.prevent="submit"
        >
            <h1 class="text-lg font-semibold">
                <Link :href="home.url()" class="flex items-center gap-2">
                    <AppLogo />
                    {{ name }}
                </Link>
            </h1>
            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Sign in to see your office actions.
            </p>
            <label class="mt-5 block text-sm">
                Email
                <input
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    :aria-invalid="
                        Boolean(form.errors.email || clientEmailError)
                    "
                    :class="inputClass"
                    @input="clearEmailError"
                    @blur="verifyEmail"
                />
            </label>
            <div aria-live="polite">
                <FormError :message="form.errors.email ?? clientEmailError" />
            </div>
            <label class="mt-4 block text-sm">
                Password
                <input
                    v-model="form.password"
                    type="password"
                    required
                    :class="inputClass"
                />
            </label>
            <label class="mt-4 flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox" />
                Remember me
            </label>
            <button
                type="submit"
                :class="buttonClass"
                class="mt-5 w-full"
                :disabled="form.processing"
            >
                Log in
            </button>
            <p class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                New firm?
                <Link :href="register.url()" class="underline">Register</Link>
            </p>
        </form>
    </div>
</template>
