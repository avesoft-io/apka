<script setup lang="ts">
import { index as clientIndex } from '@/actions/App/Http/Controllers/ClientController';
import { index as dashboard } from '@/actions/App/Http/Controllers/DashboardController';
import { index as templateIndex } from '@/actions/App/Http/Controllers/EmailTemplateController';
import { index as officeActionIndex } from '@/actions/App/Http/Controllers/OfficeActionController';
import { index as staffIndex } from '@/actions/App/Http/Controllers/StaffController';
import { destroy } from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
import AppLogo from '@/components/AppLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const success = computed(() => page.props.flash.success);
const isSupervisor = computed(() => user.value?.role === 'supervisor');

const links = computed(() => {
    const items = [
        { label: 'Dashboard', href: dashboard.url() },
        { label: 'Office Actions', href: officeActionIndex.url() },
        { label: 'Clients', href: clientIndex.url() },
        { label: 'Email templates', href: templateIndex.url() },
    ];

    if (isSupervisor.value) {
        items.push({ label: 'Users', href: staffIndex.url() });
    }

    return items;
});
</script>

<template>
    <div
        class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <header
            class="border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3"
            >
                <Link
                    :href="dashboard.url()"
                    class="flex items-center gap-2 text-sm font-semibold tracking-tight"
                >
                    <AppLogo />
                    {{ page.props.name }}
                </Link>
                <nav class="flex flex-wrap items-center gap-1 text-sm">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-md px-2.5 py-1.5 text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1f1f1c] dark:hover:text-[#EDEDEC]"
                    >
                        {{ link.label }}
                    </Link>
                </nav>
                <div
                    v-if="user"
                    class="ml-auto flex items-center gap-3 text-sm"
                >
                    <span class="text-[#706f6c] dark:text-[#A1A09A]">
                        <span v-if="user.organization"
                            >{{ user.organization.name }} · </span
                        >{{ user.name }}
                    </span>
                    <Link
                        :href="destroy.url()"
                        method="post"
                        as="button"
                        class="text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                    >
                        Log out
                    </Link>
                </div>
            </div>
        </header>
        <main class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6">
            <p
                v-if="success"
                class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
            >
                {{ success }}
            </p>
            <slot />
        </main>
    </div>
</template>
