<script setup lang="ts">
import { create as login } from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import { index as dashboard } from '@/actions/App/Http/Controllers/DashboardController';
import { create as register } from '@/actions/App/Http/Controllers/RegisteredUserController';
import AppLogo from '@/components/AppLogo.vue';
import { buttonClass, buttonSecondaryClass } from '@/lib/ui';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const name = computed(() => page.props.name);
const signedIn = computed(() => page.props.auth.user !== null);

const types = ['SER', 'SSER', 'FER', 'NFOA', 'FOA', 'RR', 'ADV', 'Other'];

const features = [
    {
        title: 'One task per office action',
        body: 'Record the client, an application number or HCI reference, the paper number, the office action type, the mailing date, and the response period.',
    },
    {
        title: 'A checklist that follows the work',
        body: 'Report to the client, wait for instructions, request an extension only when you need one, then draft, check, approve, file, and send the filing report.',
    },
    {
        title: 'Deadlines that stay separate',
        body: 'The official deadline, the internal response date, and the reminder date each stay on the record. Reminders due today sit on the dashboard.',
    },
    {
        title: 'A firm of its own',
        body: 'Registration opens a separate office. Staff, clients, templates, and history stay inside that firm.',
    },
];

const steps = [
    {
        title: 'Register the firm',
        body: 'You become the supervisor. Add the people who will work the office actions.',
    },
    {
        title: 'Keep client instructions',
        body: 'Standing instructions live with the client and appear on every office action for that client.',
    },
    {
        title: 'Work it through to filing',
        body: 'Assign the office action, follow the checklist, and export the list when you need it in a spreadsheet.',
    },
];
</script>

<template>
    <div
        class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <Head title="Office action tracker" />
        <header
            class="border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3"
            >
                <span
                    class="flex items-center gap-2 text-sm font-semibold tracking-tight"
                >
                    <AppLogo />
                    {{ name }}
                </span>
                <nav class="ml-auto flex items-center gap-2">
                    <Link
                        v-if="signedIn"
                        :href="dashboard.url()"
                        :class="buttonClass"
                    >
                        Open your office
                    </Link>
                    <template v-else>
                        <Link :href="login.url()" :class="buttonSecondaryClass">
                            Log in
                        </Link>
                        <Link :href="register.url()" :class="buttonClass">
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>
        <main class="mx-auto flex max-w-6xl flex-col gap-16 px-4 py-16">
            <section class="flex max-w-3xl flex-col gap-6">
                <p
                    class="text-sm font-medium tracking-wide text-[#0f3d3e] uppercase dark:text-[#7daba8]"
                >
                    For patent firms
                </p>
                <h1
                    class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                >
                    Office actions, from mailing to filing.
                </h1>
                <p class="max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                    {{ name }} keeps each office action in one place: who has
                    it, what the client said, which deadline is which, and
                    whether the filing report has gone out.
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="signedIn"
                        :href="dashboard.url()"
                        :class="buttonClass"
                    >
                        Open your office
                    </Link>
                    <template v-else>
                        <Link :href="register.url()" :class="buttonClass">
                            Register your firm
                        </Link>
                        <Link :href="login.url()" :class="buttonSecondaryClass">
                            Log in
                        </Link>
                    </template>
                </div>
                <ul class="flex flex-wrap gap-2">
                    <li
                        v-for="type in types"
                        :key="type"
                        class="rounded-full border border-[#e3e3e0] px-2.5 py-1 text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                    >
                        {{ type }}
                    </li>
                </ul>
            </section>
            <section class="grid gap-4 sm:grid-cols-2">
                <article
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex flex-col gap-2 rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <h2 class="font-semibold">{{ feature.title }}</h2>
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ feature.body }}
                    </p>
                </article>
            </section>
            <section class="flex flex-col gap-6">
                <h2 class="text-2xl font-semibold tracking-tight">
                    How a firm starts
                </h2>
                <ol class="grid gap-4 md:grid-cols-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="flex flex-col gap-2"
                    >
                        <span
                            class="text-sm font-medium text-[#0f3d3e] dark:text-[#7daba8]"
                        >
                            {{ index + 1 }}
                        </span>
                        <h3 class="font-semibold">{{ step.title }}</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {{ step.body }}
                        </p>
                    </li>
                </ol>
            </section>
        </main>
        <footer class="border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
            <p
                class="mx-auto max-w-6xl px-4 py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]"
            >
                {{ name }} · Office action tracker
            </p>
        </footer>
    </div>
</template>
