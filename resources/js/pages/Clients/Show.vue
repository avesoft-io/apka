<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/ClientController';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/ClientInstructionController';
import { index as officeActionIndex } from '@/actions/App/Http/Controllers/OfficeActionController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    buttonClass,
    buttonDangerClass,
    buttonSecondaryClass,
    inputClass,
    labelClass,
    mutedClass,
    panelClass,
} from '@/lib/ui';
import type { HistoryItem, StandingInstruction } from '@/types/tracker';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    client: {
        id: number;
        name: string;
        code: string | null;
    };
    instructions: StandingInstruction[];
    history: HistoryItem[];
}>();

const details = useForm({
    name: props.client.name,
    code: props.client.code ?? '',
});

const instruction = useForm({
    body: '',
});

function saveClient() {
    details
        .transform((data) => ({
            name: data.name,
            code: data.code || null,
        }))
        .patch(update.url(props.client.id));
}

function addInstruction() {
    instruction.post(store.url(props.client.id), {
        onSuccess: () => instruction.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="client.name" />
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ client.name }}</h1>
                <p v-if="client.code" :class="mutedClass">{{ client.code }}</p>
            </div>
            <Link
                :href="
                    officeActionIndex.url({
                        query: { list: 'all', client_id: client.id },
                    })
                "
                :class="buttonSecondaryClass"
            >
                Office actions
            </Link>
        </div>

        <form
            :class="panelClass"
            class="flex max-w-xl flex-col gap-3"
            @submit.prevent="saveClient"
        >
            <h2 class="font-medium">Client details</h2>
            <label :class="labelClass">
                Name
                <input v-model="details.name" :class="inputClass" required />
            </label>
            <label :class="labelClass">
                Code
                <input v-model="details.code" :class="inputClass" />
            </label>
            <FormError :message="details.errors.name" />
            <button
                type="submit"
                :class="buttonClass"
                class="self-start"
                :disabled="details.processing"
            >
                Save client
            </button>
        </form>

        <section class="flex flex-col gap-3">
            <h2 class="font-medium">Standing instructions</h2>
            <p :class="mutedClass">
                These apply to every office action for this client. Add
                response-specific instructions on the office action itself.
            </p>
            <ul v-if="instructions.length > 0" class="flex flex-col gap-3">
                <li
                    v-for="item in instructions"
                    :key="item.id"
                    :class="panelClass"
                    class="flex flex-col gap-2"
                >
                    <p class="text-sm whitespace-pre-wrap">{{ item.body }}</p>
                    <div class="flex items-center justify-between gap-3">
                        <span :class="mutedClass">
                            {{ item.author ?? 'Someone' }}
                            <span v-if="item.created_at">
                                · {{ item.created_at }}
                            </span>
                        </span>
                        <Link
                            :href="
                                destroy.url({
                                    client: client.id,
                                    instruction: item.id,
                                })
                            "
                            method="delete"
                            as="button"
                            :class="buttonDangerClass"
                        >
                            Remove
                        </Link>
                    </div>
                </li>
            </ul>
            <form
                :class="panelClass"
                class="flex flex-col gap-3"
                @submit.prevent="addInstruction"
            >
                <label :class="labelClass">
                    New standing instruction
                    <textarea
                        v-model="instruction.body"
                        rows="3"
                        :class="inputClass"
                        required
                    />
                </label>
                <FormError :message="instruction.errors.body" />
                <button
                    type="submit"
                    :class="buttonClass"
                    class="self-start"
                    :disabled="instruction.processing"
                >
                    Add instruction
                </button>
            </form>
        </section>

        <section :class="panelClass" class="flex flex-col gap-3">
            <h2 class="font-medium">History</h2>
            <p v-if="history.length === 0" :class="mutedClass">
                No changes have been recorded.
            </p>
            <ol v-else class="flex flex-col gap-4">
                <li
                    v-for="item in history"
                    :key="item.id"
                    class="flex flex-col gap-1"
                >
                    <div class="text-sm">
                        <span class="font-medium">{{ item.user }}</span>
                        {{ item.event }}
                        <span :class="mutedClass">· {{ item.when }}</span>
                    </div>
                    <ul class="flex flex-col gap-1 text-sm">
                        <li
                            v-for="(entry, index) in item.entries"
                            :key="`${item.id}-${index}`"
                        >
                            <span class="font-medium">{{ entry.field }}</span>
                            :
                            <span :class="mutedClass">{{ entry.before }}</span>
                            → {{ entry.after }}
                        </li>
                    </ul>
                </li>
            </ol>
        </section>
    </AppLayout>
</template>
