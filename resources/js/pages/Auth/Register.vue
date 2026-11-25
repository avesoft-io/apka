<script setup lang="ts">
import { create as login } from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import { store } from '@/actions/App/Http/Controllers/RegisteredUserController';
import AppLogo from '@/components/AppLogo.vue';
import FormError from '@/components/FormError.vue';
import { passwordRules, passwordStrength } from '@/lib/password';
import { buttonClass, inputClass, labelClass, mutedClass } from '@/lib/ui';
import { home } from '@/routes';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const name = usePage().props.name;

const form = useForm({
    organization_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const rules = computed(() => passwordRules(form.password));
const strength = computed(() => passwordStrength(form.password));

function submit() {
    form.post(store.url());
}
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#FDFDFC] px-4 py-10 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <Head title="Register" />
        <form
            class="flex w-full max-w-sm flex-col gap-4 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
            @submit.prevent="submit"
        >
            <div class="flex flex-col gap-1">
                <Link
                    :href="home.url()"
                    class="flex items-center gap-2 text-sm font-semibold"
                >
                    <AppLogo />
                    {{ name }}
                </Link>
                <h1 class="text-lg font-semibold">Register your firm</h1>
                <p :class="mutedClass">
                    This creates a separate office. You will be its supervisor
                    and can add staff later.
                </p>
            </div>
            <label :class="labelClass">
                Firm name
                <input
                    v-model="form.organization_name"
                    required
                    autofocus
                    :class="inputClass"
                />
            </label>
            <FormError :message="form.errors.organization_name" />
            <label :class="labelClass">
                Your name
                <input v-model="form.name" required :class="inputClass" />
            </label>
            <FormError :message="form.errors.name" />
            <label :class="labelClass">
                Email
                <input
                    v-model="form.email"
                    type="email"
                    required
                    :class="inputClass"
                />
            </label>
            <FormError :message="form.errors.email" />
            <label :class="labelClass">
                Password
                <input
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="new-password"
                    :class="inputClass"
                />
            </label>
            <div class="flex flex-col gap-2">
                <p v-if="strength" class="text-sm">
                    Strength:
                    <span class="font-medium">{{ strength.label }}</span>
                </p>
                <div
                    class="h-1.5 overflow-hidden rounded bg-[#e3e3e0] dark:bg-[#3E3E3A]"
                    aria-hidden="true"
                >
                    <div
                        v-if="strength"
                        class="h-full"
                        :class="[strength.width, strength.barClass]"
                    />
                </div>
                <ul class="flex flex-col gap-1 text-sm">
                    <li
                        v-for="rule in rules"
                        :key="rule.label"
                        :class="
                            rule.met
                                ? 'text-[#0f3d3e] dark:text-[#7daba8]'
                                : 'text-[#706f6c] dark:text-[#A1A09A]'
                        "
                    >
                        {{ rule.met ? 'Met' : 'Needed' }}: {{ rule.label }}
                    </li>
                </ul>
            </div>
            <FormError :message="form.errors.password" />
            <label :class="labelClass">
                Confirm password
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    :class="inputClass"
                />
            </label>
            <button
                type="submit"
                :class="buttonClass"
                :disabled="form.processing"
            >
                Create firm
            </button>
            <p :class="mutedClass">
                Already registered?
                <Link :href="login.url()" class="underline">Log in</Link>
            </p>
        </form>
    </div>
</template>
