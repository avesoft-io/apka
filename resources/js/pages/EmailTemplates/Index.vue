<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/EmailTemplateController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    buttonClass,
    inputClass,
    labelClass,
    mutedClass,
    panelClass,
} from '@/lib/ui';
import { Head, useForm } from '@inertiajs/vue3';

type Template = {
    id: number;
    key: string;
    name: string;
    subject: string;
    body: string;
};

const props = defineProps<{
    templates: Template[];
    canEdit: boolean;
}>();

const forms = props.templates.map((template) =>
    useForm({
        subject: template.subject,
        body: template.body,
    }),
);

function save(index: number) {
    const template = props.templates[index];

    if (!template) {
        return;
    }

    forms[index]?.patch(update.url(template.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Email templates" />
        <div>
            <h1 class="text-2xl font-semibold">Email templates</h1>
            <p :class="mutedClass">
                Placeholders such as &#123;&#123;client&#125;&#125;,
                &#123;&#123;reference&#125;&#125;,
                &#123;&#123;current_deadline&#125;&#125;, and
                &#123;&#123;filing_date&#125;&#125; are filled in on the office
                action. Edit the draft there before copying it into email.
            </p>
        </div>

        <form
            v-for="(template, index) in templates"
            :key="template.id"
            :class="panelClass"
            class="flex flex-col gap-3"
            @submit.prevent="save(index)"
        >
            <h2 class="font-medium">{{ template.name }}</h2>
            <label :class="labelClass">
                Subject
                <input
                    v-model="forms[index]!.subject"
                    :class="inputClass"
                    :readonly="!canEdit"
                    required
                />
            </label>
            <label :class="labelClass">
                Body
                <textarea
                    v-model="forms[index]!.body"
                    rows="8"
                    :class="inputClass"
                    :readonly="!canEdit"
                    required
                />
            </label>
            <FormError :message="forms[index]?.errors.subject" />
            <FormError :message="forms[index]?.errors.body" />
            <button
                v-if="canEdit"
                type="submit"
                :class="buttonClass"
                class="self-start"
                :disabled="forms[index]?.processing"
            >
                Save template
            </button>
        </form>
    </AppLayout>
</template>
