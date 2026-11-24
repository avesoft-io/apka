<script setup lang="ts">
import { show, store } from '@/actions/App/Http/Controllers/ClientController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    buttonClass,
    inputClass,
    labelClass,
    mutedClass,
    panelClass,
} from '@/lib/ui';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    clients: Array<{
        id: number;
        name: string;
        code: string | null;
        office_actions_count: number;
        instructions_count: number;
    }>;
}>();

const form = useForm({
    name: '',
    code: '',
});

function submit() {
    form.post(store.url(), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Clients" />
        <div>
            <h1 class="text-2xl font-semibold">Clients</h1>
            <p :class="mutedClass">
                Standing instructions live with the client and appear on every
                office action.
            </p>
        </div>

        <form
            :class="panelClass"
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="submit"
        >
            <label :class="labelClass" class="min-w-56 grow">
                Name
                <input v-model="form.name" :class="inputClass" required />
            </label>
            <label :class="labelClass" class="w-40">
                Code
                <input v-model="form.code" :class="inputClass" />
            </label>
            <button
                type="submit"
                :class="buttonClass"
                :disabled="form.processing"
            >
                Add client
            </button>
            <FormError :message="form.errors.name" />
            <FormError :message="form.errors.code" />
        </form>

        <div :class="panelClass" class="overflow-x-auto p-0">
            <p v-if="clients.length === 0" :class="mutedClass" class="p-4">
                No clients yet.
            </p>
            <table v-else class="w-full text-left text-sm">
                <thead :class="mutedClass">
                    <tr>
                        <th class="px-4 py-2 font-medium">Client</th>
                        <th class="px-4 py-2 font-medium">Office actions</th>
                        <th class="px-4 py-2 font-medium">
                            Standing instructions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="client in clients"
                        :key="client.id"
                        class="border-t border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show.url(client.id)"
                                class="font-medium hover:underline"
                            >
                                {{ client.name }}
                            </Link>
                            <div v-if="client.code" :class="mutedClass">
                                {{ client.code }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{ client.office_actions_count }}
                        </td>
                        <td class="px-4 py-3">
                            {{ client.instructions_count }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
