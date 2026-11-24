<script setup lang="ts">
import { index as officeActionIndex } from '@/actions/App/Http/Controllers/OfficeActionController';
import { show } from '@/actions/App/Http/Controllers/OfficeActionController';
import { reminder } from '@/actions/App/Http/Controllers/OfficeActionWorkflowController';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/dates';
import { buttonSecondaryClass } from '@/lib/ui';
import type { ListOption, OfficeActionSummary } from '@/types/tracker';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    upcoming: OfficeActionSummary[];
    reminders: OfficeActionSummary[];
    lists: Array<ListOption & { count: number }>;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />
        <div>
            <h1 class="text-2xl font-semibold">My office actions</h1>
            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Upcoming deadlines, current status, and reminders due today.
            </p>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="list in lists"
                :key="list.value"
                :href="officeActionIndex.url({ query: { list: list.value } })"
                class="rounded-lg border border-[#e3e3e0] bg-white p-4 hover:border-[#0f3d3e] dark:border-[#3E3E3A] dark:bg-[#161615]"
            >
                <div class="text-2xl font-semibold">{{ list.count }}</div>
                <div class="mt-1 text-sm font-medium">{{ list.label }}</div>
                <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    {{ list.description }}
                </p>
            </Link>
        </section>

        <section
            id="reminders"
            class="rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div
                class="border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
            >
                <h2 class="font-medium">Reminders to send today</h2>
            </div>
            <p
                v-if="reminders.length === 0"
                class="px-4 py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]"
            >
                No reminders are due.
            </p>
            <ul v-else class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                <li
                    v-for="item in reminders"
                    :key="item.id"
                    class="flex flex-wrap items-center gap-3 px-4 py-3"
                >
                    <div class="min-w-0 flex-1">
                        <div class="font-medium">{{ item.reference }}</div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {{ item.client_name }} · reminder
                            {{ formatDate(item.reminder_date) }} · deadline
                            {{ formatDate(item.current_deadline) }}
                        </div>
                    </div>
                    <Link
                        :href="
                            show.url(item.id, { query: { draft: 'reminder' } })
                        "
                        :class="buttonSecondaryClass"
                    >
                        Open and draft
                    </Link>
                    <Link
                        :href="reminder.url(item.id)"
                        method="patch"
                        as="button"
                        :class="buttonSecondaryClass"
                    >
                        Mark sent
                    </Link>
                </li>
            </ul>
        </section>

        <section
            class="rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
            >
                <h2 class="font-medium">Upcoming deadlines</h2>
                <Link
                    :href="officeActionIndex.url({ query: { list: 'mine' } })"
                    class="text-sm text-[#0f3d3e] underline dark:text-[#8fccc8]"
                >
                    My Office Actions
                </Link>
            </div>
            <p
                v-if="upcoming.length === 0"
                class="px-4 py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]"
            >
                Nothing is assigned to you that still needs a response.
            </p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        <tr>
                            <th class="px-4 py-2 font-medium">Matter</th>
                            <th class="px-4 py-2 font-medium">Deadline</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in upcoming"
                            :key="item.id"
                            class="border-t border-[#e3e3e0] dark:border-[#3E3E3A]"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="show.url(item.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ item.reference }}
                                </Link>
                                <div class="text-[#706f6c] dark:text-[#A1A09A]">
                                    {{ item.client_name }} · {{ item.type }} ·
                                    paper {{ item.paper_number }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    :class="item.overdue ? 'text-red-700' : ''"
                                >
                                    {{ formatDate(item.current_deadline) }}
                                </div>
                                <div
                                    class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ item.due_label }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ item.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
