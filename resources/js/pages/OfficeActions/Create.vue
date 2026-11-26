<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/OfficeActionController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { buttonClass, inputClass, labelClass, mutedClass } from '@/lib/ui';
import type { NamedOption, TypeOption } from '@/types/tracker';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    clients: NamedOption[];
    staff: NamedOption[];
    types: TypeOption[];
    canAssign: boolean;
}>();

const page = usePage();
const newClient = ref(props.clients.length === 0);

const form = useForm({
    client_id: '' as string,
    new_client_name: '',
    client_reference: '',
    application_number: '',
    application_name: '',
    hci_reference: '',
    applicant_names: '',
    paper_number: '',
    type: props.types[0]?.value ?? 'SER',
    mailing_date: page.props.today,
    response_period_months: 3,
    original_deadline: '',
    internal_response_date: '',
    reminder_date: '',
    assignee_id: '',
});

function submit() {
    form.transform((data) => ({
        ...data,
        client_id: newClient.value ? null : data.client_id || null,
        new_client_name: newClient.value ? data.new_client_name : null,
        client_reference: data.client_reference || null,
        application_number: data.application_number || null,
        application_name: data.application_name || null,
        hci_reference: data.hci_reference || null,
        assignee_id: props.canAssign ? data.assignee_id || null : null,
        original_deadline: data.original_deadline || null,
        internal_response_date: data.internal_response_date || null,
        reminder_date: data.reminder_date || null,
    })).post(store.url());
}
</script>

<template>
    <AppLayout>
        <Head title="Log an office action" />
        <div>
            <h1 class="text-2xl font-semibold">Log an office action</h1>
            <p :class="mutedClass">
                One task per office action. Leave the official deadline blank to
                calculate it from the mailing date and response period.
            </p>
        </div>

        <form class="flex max-w-2xl flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-3">
                <label
                    v-if="clients.length > 0"
                    class="flex items-center gap-2 text-sm"
                >
                    <input v-model="newClient" type="checkbox" />
                    New client
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label
                        v-if="clients.length > 0 && !newClient"
                        :class="labelClass"
                    >
                        Client name
                        <select v-model="form.client_id" :class="inputClass">
                            <option value="">Choose a client</option>
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="String(client.id)"
                            >
                                {{ client.name }}
                            </option>
                        </select>
                    </label>
                    <label v-else :class="labelClass">
                        Client name
                        <input
                            v-model="form.new_client_name"
                            :class="inputClass"
                            required
                        />
                    </label>
                    <label :class="labelClass">
                        Client reference number
                        <input
                            v-model="form.client_reference"
                            :class="inputClass"
                        />
                    </label>
                </div>
                <FormError :message="form.errors.client_id" />
                <FormError :message="form.errors.new_client_name" />
                <FormError :message="form.errors.client_reference" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label :class="labelClass">
                    Application number
                    <input
                        v-model="form.application_number"
                        :class="inputClass"
                    />
                </label>
                <label :class="labelClass">
                    Application name
                    <input
                        v-model="form.application_name"
                        :class="inputClass"
                    />
                </label>
                <label :class="labelClass">
                    HCI reference
                    <input v-model="form.hci_reference" :class="inputClass" />
                </label>
            </div>
            <FormError :message="form.errors.application_number" />
            <FormError :message="form.errors.application_name" />
            <FormError :message="form.errors.hci_reference" />

            <label :class="labelClass">
                Applicant name(s)
                <textarea
                    v-model="form.applicant_names"
                    rows="3"
                    :class="inputClass"
                    required
                />
                <span :class="mutedClass">
                    Separate each name with a comma. One name does not need a
                    comma.
                </span>
            </label>
            <FormError :message="form.errors.applicant_names" />

            <div class="grid gap-4 sm:grid-cols-2">
                <label :class="labelClass">
                    Paper number
                    <input
                        v-model="form.paper_number"
                        :class="inputClass"
                        required
                    />
                </label>
                <label :class="labelClass">
                    OA type
                    <select v-model="form.type" :class="inputClass" required>
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </label>
            </div>
            <FormError :message="form.errors.paper_number" />
            <FormError :message="form.errors.type" />

            <div class="grid gap-4 sm:grid-cols-2">
                <label :class="labelClass">
                    Mailing date
                    <input
                        v-model="form.mailing_date"
                        type="date"
                        :class="inputClass"
                        required
                    />
                </label>
                <label :class="labelClass">
                    Response period (months)
                    <input
                        v-model.number="form.response_period_months"
                        type="number"
                        min="1"
                        max="12"
                        :class="inputClass"
                        required
                    />
                </label>
                <label :class="labelClass">
                    Official deadline
                    <input
                        v-model="form.original_deadline"
                        type="date"
                        :class="inputClass"
                    />
                </label>
                <label :class="labelClass">
                    Internal response date
                    <input
                        v-model="form.internal_response_date"
                        type="date"
                        :class="inputClass"
                    />
                </label>
                <label :class="labelClass">
                    Reminder date
                    <input
                        v-model="form.reminder_date"
                        type="date"
                        :class="inputClass"
                    />
                </label>
                <label v-if="canAssign" :class="labelClass">
                    Assign to
                    <select v-model="form.assignee_id" :class="inputClass">
                        <option value="">Leave unassigned</option>
                        <option
                            v-for="person in staff"
                            :key="person.id"
                            :value="String(person.id)"
                        >
                            {{ person.name }}
                        </option>
                    </select>
                </label>
            </div>
            <FormError :message="form.errors.mailing_date" />
            <FormError :message="form.errors.response_period_months" />
            <FormError :message="form.errors.original_deadline" />
            <FormError :message="form.errors.assignee_id" />

            <button
                type="submit"
                :class="buttonClass"
                class="self-start"
                :disabled="form.processing"
            >
                Log office action
            </button>
        </form>
    </AppLayout>
</template>
