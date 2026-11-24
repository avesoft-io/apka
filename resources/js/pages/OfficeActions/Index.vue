<script setup lang="ts">
import { index as exportOfficeActions } from '@/actions/App/Http/Controllers/OfficeActionExportController';
import {
    create,
    index as officeActionIndex,
    show,
} from '@/actions/App/Http/Controllers/OfficeActionController';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/dates';
import { buttonClass, buttonSecondaryClass, inputClass } from '@/lib/ui';
import type {
    ListOption,
    NamedOption,
    OfficeActionSummary,
    Paginator,
} from '@/types/tracker';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    list: string;
    listLabel: string;
    listDescription: string;
    officeActions: Paginator<OfficeActionSummary>;
    filters: {
        client_id: number | null;
        assignee_id: number | null;
    };
    lists: ListOption[];
    clients: NamedOption[];
    staff: NamedOption[];
}>();

const clientId = ref(
    props.filters.client_id ? String(props.filters.client_id) : '',
);
const assigneeId = ref(
    props.filters.assignee_id ? String(props.filters.assignee_id) : '',
);

function applyFilters() {
    router.get(
        officeActionIndex.url({
            query: {
                list: props.list,
                client_id: clientId.value || undefined,
                assignee_id:
                    props.list === 'mine'
                        ? undefined
                        : assigneeId.value || undefined,
            },
        }),
        {},
        { preserveState: true, replace: true },
    );
}

const exportUrl = computed(() =>
    exportOfficeActions.url({
        query: {
            list: props.list,
            client_id: props.filters.client_id ?? undefined,
            assignee_id: props.filters.assignee_id ?? undefined,
        },
    }),
);
</script>

<template>
    <AppLayout>
        <Head :title="listLabel" />
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ listLabel }}</h1>
                <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {{ listDescription }}
                </p>
            </div>
            <div class="flex gap-2">
                <a :href="exportUrl" :class="buttonSecondaryClass">
                    Export Excel
                </a>
                <Link :href="create.url()" :class="buttonClass"
                    >Log an office action</Link
                >
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link
                v-for="item in lists"
                :key="item.value"
                :href="
                    officeActionIndex.url({
                        query: {
                            list: item.value,
                            client_id: filters.client_id ?? undefined,
                            assignee_id: filters.assignee_id ?? undefined,
                        },
                    })
                "
                class="rounded-full px-3 py-1 text-sm"
                :class="
                    item.value === list
                        ? 'bg-[#0f3d3e] text-white'
                        : 'bg-white text-[#706f6c] ring-1 ring-[#e3e3e0] dark:bg-[#161615] dark:text-[#A1A09A] dark:ring-[#3E3E3A]'
                "
            >
                {{ item.label }}
            </Link>
        </div>

        <form
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="applyFilters"
        >
            <label class="text-sm">
                Client
                <select v-model="clientId" :class="inputClass">
                    <option value="">All clients</option>
                    <option
                        v-for="client in clients"
                        :key="client.id"
                        :value="String(client.id)"
                    >
                        {{ client.name }}
                    </option>
                </select>
            </label>
            <label v-if="list !== 'mine'" class="text-sm">
                Staff
                <select v-model="assigneeId" :class="inputClass">
                    <option value="">All staff</option>
                    <option
                        v-for="person in staff"
                        :key="person.id"
                        :value="String(person.id)"
                    >
                        {{ person.name }}
                    </option>
                </select>
            </label>
            <button type="submit" :class="buttonSecondaryClass">Filter</button>
        </form>

        <div
            class="overflow-x-auto rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <p
                v-if="officeActions.data.length === 0"
                class="px-4 py-8 text-sm text-[#706f6c] dark:text-[#A1A09A]"
            >
                No office actions in this list.
            </p>
            <table v-else class="w-full min-w-[48rem] text-left text-sm">
                <thead class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    <tr>
                        <th class="px-4 py-2 font-medium">Matter</th>
                        <th class="px-4 py-2 font-medium">Client</th>
                        <th class="px-4 py-2 font-medium">Deadline</th>
                        <th class="px-4 py-2 font-medium">Assignee</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in officeActions.data"
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
                                {{ item.type }} · paper {{ item.paper_number }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ item.client_name }}</td>
                        <td class="px-4 py-3">
                            <div :class="item.overdue ? 'text-red-700' : ''">
                                {{ formatDate(item.current_deadline) }}
                            </div>
                            <div
                                v-if="item.extension_status === 'confirmed'"
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Original
                                {{ formatDate(item.original_deadline) }} ·
                                extended
                                {{ formatDate(item.extended_deadline) }}
                            </div>
                            <div
                                v-else-if="item.extension_status === 'pending'"
                                class="text-xs text-amber-700"
                            >
                                Extension pending · original
                                {{ formatDate(item.original_deadline) }}
                            </div>
                            <div
                                v-if="item.due_label"
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ item.due_label }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{ item.assignee_name ?? 'Unassigned' }}
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ item.status }}</div>
                            <div
                                v-if="item.next_step"
                                class="max-w-xs text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ item.next_step }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="officeActions.last_page > 1"
            class="flex items-center justify-between text-sm"
        >
            <span class="text-[#706f6c] dark:text-[#A1A09A]">
                Page {{ officeActions.current_page }} of
                {{ officeActions.last_page }}
            </span>
            <div class="flex gap-2">
                <Link
                    v-if="officeActions.prev_page_url"
                    :href="officeActions.prev_page_url"
                    :class="buttonSecondaryClass"
                >
                    Previous
                </Link>
                <Link
                    v-if="officeActions.next_page_url"
                    :href="officeActions.next_page_url"
                    :class="buttonSecondaryClass"
                >
                    Next
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
