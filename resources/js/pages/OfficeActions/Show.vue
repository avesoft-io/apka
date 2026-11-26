<script setup lang="ts">
import { show as showClient } from '@/actions/App/Http/Controllers/ClientController';
import { update as updateAssignment } from '@/actions/App/Http/Controllers/OfficeActionAssignmentController';
import { update as updateOfficeAction } from '@/actions/App/Http/Controllers/OfficeActionController';
import {
    abandon,
    approval,
    checking,
    confirmExtension,
    draft,
    filing,
    filingReport,
    instructions,
    reminder,
    report,
    requestExtension,
    withdrawExtension,
} from '@/actions/App/Http/Controllers/OfficeActionWorkflowController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/dates';
import {
    buttonClass,
    buttonDangerClass,
    buttonSecondaryClass,
    inputClass,
    labelClass,
    mutedClass,
    panelClass,
} from '@/lib/ui';
import type {
    ChecklistStep,
    EmailDraft,
    NamedOption,
    OfficeActionDetail,
    TypeOption,
} from '@/types/tracker';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    officeAction: OfficeActionDetail;
    clients: NamedOption[];
    staff: NamedOption[];
    types: TypeOption[];
    templates: EmailDraft[];
    selectedTemplate: string;
    permissions: {
        update: boolean;
        assign: boolean;
    };
}>();

const page = usePage();
const today = page.props.today;
const id = computed(() => props.officeAction.id);
const open = computed(() => props.officeAction.outcome === 'open');
const canEdit = computed(() => props.permissions.update && open.value);
const unfiled = computed(() => props.officeAction.filing_date === null);

const details = useForm({
    client_id: String(props.officeAction.client_id),
    client_reference: props.officeAction.client_reference ?? '',
    application_number: props.officeAction.application_number ?? '',
    hci_reference: props.officeAction.hci_reference ?? '',
    applicant_names: props.officeAction.applicant_names ?? '',
    paper_number: props.officeAction.paper_number,
    type: props.officeAction.type,
    mailing_date: props.officeAction.mailing_date ?? '',
    response_period_months: props.officeAction.response_period_months,
    original_deadline: props.officeAction.original_deadline ?? '',
    internal_response_date: props.officeAction.internal_response_date ?? '',
    reminder_date: props.officeAction.reminder_date ?? '',
});

const assignment = useForm({
    assignee_id: props.officeAction.assignee_id
        ? String(props.officeAction.assignee_id)
        : '',
});

const reportForm = useForm({
    report_sent_on: props.officeAction.report_sent_on ?? today,
    report_includes_fees: props.officeAction.report_includes_fees,
    report_requests_instructions:
        props.officeAction.report_requests_instructions,
});

const instructionForm = useForm({
    response_instructions: props.officeAction.response_instructions ?? '',
    instructions_received_on:
        props.officeAction.instructions_received_on ?? today,
});

const extensionForm = useForm({
    extension_requested_on: props.officeAction.extension_requested_on ?? today,
});

const confirmForm = useForm({
    extended_deadline: props.officeAction.extended_deadline ?? '',
});

const withdrawForm = useForm({});

const draftForm = useForm({
    response_prepared: props.officeAction.response_prepared,
    amended_claims_prepared: props.officeAction.amended_claims_prepared,
    other_documents_prepared: props.officeAction.other_documents_prepared,
    draft_notes: props.officeAction.draft_notes ?? '',
    draft_prepared_on: props.officeAction.draft_prepared_on ?? today,
});

const checkingForm = useForm({
    checking_status: props.officeAction.checking_status,
});

const approvalForm = useForm({
    client_approval_required: props.officeAction.client_approval_required,
    approved: props.officeAction.client_approval_status === 'approved',
});

const filingForm = useForm({
    filing_date: props.officeAction.filing_date ?? today,
});

const filingReportForm = useForm({
    filing_reported_on: props.officeAction.filing_reported_on ?? today,
});

const abandonForm = useForm({
    instruction: '',
    reason: '',
});

const reminderForm = useForm({
    reminder: '',
});

const selectedKey = ref(props.selectedTemplate);
const subject = ref('');
const body = ref('');
const copyMessage = ref('');

function applyTemplate(key: string) {
    const template = props.templates.find((item) => item.key === key);
    subject.value = template?.subject ?? '';
    body.value = template?.body ?? '';
    copyMessage.value = '';
}

applyTemplate(selectedKey.value);

watch(selectedKey, applyTemplate);

watch(
    () => props.officeAction.id,
    () => {
        selectedKey.value = props.selectedTemplate;
        applyTemplate(props.selectedTemplate);
    },
);

function stepOf(key: string): ChecklistStep | undefined {
    return props.officeAction.checklist.find((step) => step.key === key);
}

function showForm(key: string): boolean {
    const state = stepOf(key)?.state;

    return (
        canEdit.value &&
        (state === 'current' || state === 'done' || state === 'attention')
    );
}

function dotClass(state: ChecklistStep['state']): string {
    if (state === 'done') {
        return 'bg-emerald-600';
    }

    if (state === 'current') {
        return 'bg-[#0f3d3e]';
    }

    if (state === 'attention') {
        return 'bg-amber-500';
    }

    if (state === 'skipped') {
        return 'bg-transparent ring-1 ring-[#e3e3e0] dark:ring-[#3E3E3A]';
    }

    return 'bg-[#e3e3e0] dark:bg-[#3E3E3A]';
}

function saveDetails() {
    details
        .transform((data) => ({
            ...data,
            client_id: data.client_id || null,
            client_reference: data.client_reference || null,
            application_number: data.application_number || null,
            hci_reference: data.hci_reference || null,
            original_deadline: data.original_deadline || null,
            internal_response_date: data.internal_response_date || null,
            reminder_date: data.reminder_date || null,
        }))
        .patch(updateOfficeAction.url(id.value));
}

function saveAssignment() {
    assignment
        .transform((data) => ({
            assignee_id: data.assignee_id || null,
        }))
        .patch(updateAssignment.url(id.value));
}

function saveReport() {
    reportForm.patch(report.url(id.value));
}

function saveInstructions() {
    instructionForm.patch(instructions.url(id.value));
}

function saveExtensionRequest() {
    extensionForm.post(requestExtension.url(id.value));
}

function saveExtensionConfirm() {
    confirmForm.patch(confirmExtension.url(id.value));
}

function saveExtensionWithdraw() {
    withdrawForm.delete(withdrawExtension.url(id.value));
}

function saveDraft() {
    draftForm
        .transform((data) => ({
            ...data,
            draft_notes: data.draft_notes || null,
        }))
        .patch(draft.url(id.value));
}

function setChecking(status: string) {
    checkingForm.checking_status = status;
    checkingForm.patch(checking.url(id.value));
}

function saveApproval() {
    approvalForm.patch(approval.url(id.value));
}

function enableApproval() {
    approvalForm.client_approval_required = true;
    approvalForm.approved = false;
    saveApproval();
}

function saveFiling() {
    filingForm.patch(filing.url(id.value));
}

function saveFilingReport() {
    filingReportForm.patch(filingReport.url(id.value));
}

function saveAbandon() {
    abandonForm.post(abandon.url(id.value));
}

function markReminderSent() {
    reminderForm.transform(() => ({})).patch(reminder.url(id.value));
}

async function copyDraft() {
    const text = `Subject: ${subject.value}\n\n${body.value}`;

    try {
        await navigator.clipboard.writeText(text);
        copyMessage.value = 'Copied. Paste it into your email.';
    } catch {
        copyMessage.value =
            'Clipboard is unavailable. Select the draft and copy it.';
    }
}

const checkingOptions = [
    { value: 'for_checking', label: 'For checking' },
    { value: 'for_revision', label: 'For revision' },
    { value: 'checked', label: 'Checked' },
];
</script>

<template>
    <AppLayout>
        <Head :title="officeAction.reference" />

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold">
                    {{ officeAction.reference }}
                </h1>
                <p :class="mutedClass">
                    <Link
                        :href="showClient.url(officeAction.client_id)"
                        class="underline"
                    >
                        {{ officeAction.client_name }}
                    </Link>
                    · {{ officeAction.type_label }} · paper
                    {{ officeAction.paper_number }}
                </p>
                <p v-if="officeAction.applicant_names" :class="mutedClass">
                    Applicants: {{ officeAction.applicant_names }}
                </p>
                <p v-if="officeAction.next_step" class="text-sm">
                    {{ officeAction.next_step }}
                </p>
            </div>
            <div class="flex flex-col items-start gap-1 text-sm">
                <span class="rounded-full bg-[#0f3d3e] px-3 py-1 text-white">
                    {{ officeAction.status }}
                </span>
                <span
                    v-if="officeAction.due_label"
                    :class="officeAction.overdue ? 'text-red-700' : mutedClass"
                >
                    {{ officeAction.due_label }}
                </span>
            </div>
        </div>

        <div
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]"
        >
            <section class="flex flex-col gap-3">
                <h2 class="font-medium">Checklist</h2>
                <ol class="flex flex-col gap-3">
                    <li
                        v-for="step in officeAction.checklist"
                        :key="step.key"
                        :class="panelClass"
                        class="flex flex-col gap-3"
                    >
                        <div class="flex gap-3">
                            <span
                                class="mt-1 size-2.5 shrink-0 rounded-full"
                                :class="dotClass(step.state)"
                            />
                            <div class="flex flex-col gap-1">
                                <div class="font-medium">{{ step.label }}</div>
                                <p :class="mutedClass">{{ step.detail }}</p>
                            </div>
                        </div>

                        <blockquote
                            v-if="
                                step.key === 'await_instructions' &&
                                officeAction.response_instructions
                            "
                            class="border-l-2 border-[#e3e3e0] pl-3 text-sm whitespace-pre-wrap dark:border-[#3E3E3A]"
                        >
                            {{ officeAction.response_instructions }}
                        </blockquote>

                        <form
                            v-if="
                                step.key === 'receive_assign' &&
                                showForm(step.key) &&
                                permissions.assign
                            "
                            class="flex flex-wrap items-end gap-3"
                            @submit.prevent="saveAssignment"
                        >
                            <label :class="labelClass" class="min-w-48 grow">
                                Assignee
                                <select
                                    v-model="assignment.assignee_id"
                                    :class="inputClass"
                                >
                                    <option value="">Unassigned</option>
                                    <option
                                        v-for="person in staff"
                                        :key="person.id"
                                        :value="String(person.id)"
                                    >
                                        {{ person.name }}
                                    </option>
                                </select>
                            </label>
                            <button
                                type="submit"
                                :class="buttonClass"
                                :disabled="assignment.processing"
                            >
                                Save assignment
                            </button>
                            <FormError
                                :message="assignment.errors.assignee_id"
                            />
                        </form>

                        <form
                            v-if="
                                step.key === 'report_client' &&
                                showForm(step.key)
                            "
                            class="flex flex-col gap-3"
                            @submit.prevent="saveReport"
                        >
                            <label :class="labelClass">
                                Sent on
                                <input
                                    v-model="reportForm.report_sent_on"
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="reportForm.report_includes_fees"
                                    type="checkbox"
                                />
                                Fees included
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="
                                        reportForm.report_requests_instructions
                                    "
                                    type="checkbox"
                                />
                                Instructions requested
                            </label>
                            <FormError
                                :message="reportForm.errors.report_sent_on"
                            />
                            <button
                                type="submit"
                                :class="buttonClass"
                                class="self-start"
                                :disabled="reportForm.processing"
                            >
                                Record report
                            </button>
                        </form>

                        <form
                            v-if="
                                step.key === 'await_instructions' &&
                                showForm(step.key)
                            "
                            class="flex flex-col gap-3"
                            @submit.prevent="saveInstructions"
                        >
                            <label :class="labelClass">
                                Instructions for this response
                                <textarea
                                    v-model="
                                        instructionForm.response_instructions
                                    "
                                    rows="4"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <label :class="labelClass">
                                Received on
                                <input
                                    v-model="
                                        instructionForm.instructions_received_on
                                    "
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <FormError
                                :message="
                                    instructionForm.errors.response_instructions
                                "
                            />
                            <button
                                type="submit"
                                :class="buttonClass"
                                class="self-start"
                                :disabled="instructionForm.processing"
                            >
                                Record instructions
                            </button>
                        </form>

                        <form
                            v-if="step.key === 'draft' && showForm(step.key)"
                            class="flex flex-col gap-3"
                            @submit.prevent="saveDraft"
                        >
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="draftForm.response_prepared"
                                    type="checkbox"
                                />
                                Response prepared
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="draftForm.amended_claims_prepared"
                                    type="checkbox"
                                />
                                Amended claims prepared
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="draftForm.other_documents_prepared"
                                    type="checkbox"
                                />
                                Other documents prepared
                            </label>
                            <label :class="labelClass">
                                Notes
                                <textarea
                                    v-model="draftForm.draft_notes"
                                    rows="3"
                                    :class="inputClass"
                                />
                            </label>
                            <label :class="labelClass">
                                Prepared on
                                <input
                                    v-model="draftForm.draft_prepared_on"
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <FormError
                                :message="draftForm.errors.response_prepared"
                            />
                            <FormError
                                :message="draftForm.errors.draft_prepared_on"
                            />
                            <button
                                type="submit"
                                :class="buttonClass"
                                class="self-start"
                                :disabled="draftForm.processing"
                            >
                                Record draft
                            </button>
                        </form>

                        <div
                            v-if="step.key === 'checking' && showForm(step.key)"
                            class="flex flex-col gap-3"
                        >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="option in checkingOptions"
                                    :key="option.value"
                                    type="button"
                                    :class="
                                        officeAction.checking_status ===
                                        option.value
                                            ? buttonClass
                                            : buttonSecondaryClass
                                    "
                                    :disabled="checkingForm.processing"
                                    @click="setChecking(option.value)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                            <FormError
                                :message="checkingForm.errors.checking_status"
                            />
                            <button
                                v-if="!officeAction.client_approval_required"
                                type="button"
                                :class="buttonSecondaryClass"
                                class="self-start"
                                :disabled="approvalForm.processing"
                                @click="enableApproval"
                            >
                                Track client approval
                            </button>
                        </div>

                        <form
                            v-if="
                                step.key === 'client_approval' &&
                                showForm(step.key)
                            "
                            class="flex flex-col gap-3"
                            @submit.prevent="saveApproval"
                        >
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="
                                        approvalForm.client_approval_required
                                    "
                                    type="checkbox"
                                />
                                Client must approve this draft
                            </label>
                            <label
                                v-if="approvalForm.client_approval_required"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="approvalForm.approved"
                                    type="checkbox"
                                />
                                Client approved the draft
                            </label>
                            <FormError
                                :message="
                                    approvalForm.errors.client_approval_required
                                "
                            />
                            <button
                                type="submit"
                                :class="buttonClass"
                                class="self-start"
                                :disabled="approvalForm.processing"
                            >
                                Save approval
                            </button>
                        </form>

                        <div
                            v-if="step.key === 'file' && showForm(step.key)"
                            class="flex flex-col gap-3"
                        >
                            <ul
                                v-if="officeAction.filing_blockers.length > 0"
                                class="flex flex-col gap-1 text-sm text-amber-800 dark:text-amber-200"
                            >
                                <li
                                    v-for="blocker in officeAction.filing_blockers"
                                    :key="blocker"
                                >
                                    {{ blocker }}
                                </li>
                            </ul>
                            <form
                                v-else
                                class="flex flex-wrap items-end gap-3"
                                @submit.prevent="saveFiling"
                            >
                                <label :class="labelClass">
                                    Filing date
                                    <input
                                        v-model="filingForm.filing_date"
                                        type="date"
                                        :class="inputClass"
                                        required
                                    />
                                </label>
                                <button
                                    type="submit"
                                    :class="buttonClass"
                                    :disabled="filingForm.processing"
                                >
                                    Record filing
                                </button>
                                <FormError
                                    :message="filingForm.errors.filing_date"
                                />
                            </form>
                        </div>

                        <form
                            v-if="
                                step.key === 'report_filing' &&
                                showForm(step.key)
                            "
                            class="flex flex-wrap items-end gap-3"
                            @submit.prevent="saveFilingReport"
                        >
                            <label :class="labelClass">
                                Report sent on
                                <input
                                    v-model="
                                        filingReportForm.filing_reported_on
                                    "
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <button
                                type="submit"
                                :class="buttonClass"
                                :disabled="filingReportForm.processing"
                            >
                                Mark completed
                            </button>
                            <FormError
                                :message="
                                    filingReportForm.errors.filing_reported_on
                                "
                            />
                        </form>
                    </li>
                </ol>

                <form
                    v-if="canEdit && unfiled"
                    :class="panelClass"
                    class="flex flex-col gap-3"
                    @submit.prevent="saveAbandon"
                >
                    <h2 class="font-medium">Close as abandoned</h2>
                    <p :class="mutedClass">
                        Use this when the client instructs you to abandon the
                        application. Later response steps are skipped.
                    </p>
                    <label :class="labelClass">
                        Client instruction
                        <textarea
                            v-model="abandonForm.instruction"
                            rows="3"
                            :class="inputClass"
                            required
                        />
                    </label>
                    <label :class="labelClass">
                        Reason for closing
                        <textarea
                            v-model="abandonForm.reason"
                            rows="2"
                            :class="inputClass"
                            required
                        />
                    </label>
                    <FormError :message="abandonForm.errors.instruction" />
                    <FormError :message="abandonForm.errors.reason" />
                    <button
                        type="submit"
                        :class="buttonDangerClass"
                        class="self-start"
                        :disabled="abandonForm.processing"
                    >
                        Record instruction and close
                    </button>
                </form>
            </section>

            <div class="flex flex-col gap-4">
                <section :class="panelClass" class="flex flex-col gap-3">
                    <h2 class="font-medium">Deadlines</h2>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt :class="mutedClass">Original deadline</dt>
                            <dd>
                                {{ formatDate(officeAction.original_deadline) }}
                            </dd>
                        </div>
                        <div>
                            <dt :class="mutedClass">Current deadline</dt>
                            <dd
                                :class="
                                    officeAction.overdue ? 'text-red-700' : ''
                                "
                            >
                                {{ formatDate(officeAction.current_deadline) }}
                            </dd>
                        </div>
                        <div>
                            <dt :class="mutedClass">Extension</dt>
                            <dd>{{ officeAction.extension_label }}</dd>
                        </div>
                        <div>
                            <dt :class="mutedClass">Extended deadline</dt>
                            <dd>
                                {{ formatDate(officeAction.extended_deadline) }}
                            </dd>
                        </div>
                        <div>
                            <dt :class="mutedClass">Internal response date</dt>
                            <dd>
                                {{
                                    formatDate(
                                        officeAction.internal_response_date,
                                    )
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt :class="mutedClass">Reminder date</dt>
                            <dd>
                                {{ formatDate(officeAction.reminder_date) }}
                            </dd>
                        </div>
                    </dl>

                    <form
                        v-if="
                            canEdit &&
                            unfiled &&
                            officeAction.extension_status !== 'pending'
                        "
                        class="flex flex-wrap items-end gap-3"
                        @submit.prevent="saveExtensionRequest"
                    >
                        <label :class="labelClass">
                            Extension requested on
                            <input
                                v-model="extensionForm.extension_requested_on"
                                type="date"
                                :class="inputClass"
                                required
                            />
                        </label>
                        <button
                            type="submit"
                            :class="buttonSecondaryClass"
                            :disabled="extensionForm.processing"
                        >
                            {{
                                officeAction.extension_status === 'confirmed'
                                    ? 'Request another extension'
                                    : 'Record extension request'
                            }}
                        </button>
                        <FormError
                            :message="
                                extensionForm.errors.extension_requested_on
                            "
                        />
                    </form>

                    <div
                        v-if="
                            canEdit &&
                            officeAction.extension_status === 'pending'
                        "
                        class="flex flex-col gap-3"
                    >
                        <form
                            class="flex flex-wrap items-end gap-3"
                            @submit.prevent="saveExtensionConfirm"
                        >
                            <label :class="labelClass">
                                Extended deadline
                                <input
                                    v-model="confirmForm.extended_deadline"
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </label>
                            <button
                                type="submit"
                                :class="buttonClass"
                                :disabled="confirmForm.processing"
                            >
                                Confirm extension
                            </button>
                            <FormError
                                :message="confirmForm.errors.extended_deadline"
                            />
                        </form>
                        <button
                            type="button"
                            :class="buttonSecondaryClass"
                            class="self-start"
                            :disabled="withdrawForm.processing"
                            @click="saveExtensionWithdraw"
                        >
                            Withdraw request
                        </button>
                    </div>

                    <button
                        v-if="canEdit && officeAction.reminder_date"
                        type="button"
                        :class="buttonSecondaryClass"
                        class="self-start"
                        :disabled="reminderForm.processing"
                        @click="markReminderSent"
                    >
                        Mark reminder sent
                    </button>
                    <FormError :message="reminderForm.errors.reminder" />
                </section>

                <section :class="panelClass" class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-medium">Standing instructions</h2>
                        <Link
                            :href="showClient.url(officeAction.client_id)"
                            class="text-sm underline"
                        >
                            Manage
                        </Link>
                    </div>
                    <p
                        v-if="officeAction.standing_instructions.length === 0"
                        :class="mutedClass"
                    >
                        This client has no standing instructions.
                    </p>
                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="instruction in officeAction.standing_instructions"
                            :key="instruction.id"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ instruction.body }}
                            </p>
                            <p :class="mutedClass">
                                {{ instruction.author ?? 'Someone' }}
                                <span v-if="instruction.created_at">
                                    · {{ instruction.created_at }}
                                </span>
                            </p>
                        </li>
                    </ul>
                </section>

                <section :class="panelClass" class="flex flex-col gap-3">
                    <h2 class="font-medium">Email draft</h2>
                    <label :class="labelClass">
                        Template
                        <select v-model="selectedKey" :class="inputClass">
                            <option
                                v-for="template in templates"
                                :key="template.key"
                                :value="template.key"
                            >
                                {{ template.name }}
                            </option>
                        </select>
                    </label>
                    <label :class="labelClass">
                        Subject
                        <input v-model="subject" :class="inputClass" />
                    </label>
                    <label :class="labelClass">
                        Body
                        <textarea
                            v-model="body"
                            rows="10"
                            :class="inputClass"
                        />
                    </label>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            :class="buttonClass"
                            @click="copyDraft"
                        >
                            Copy draft
                        </button>
                        <span v-if="copyMessage" :class="mutedClass">
                            {{ copyMessage }}
                        </span>
                    </div>
                </section>

                <form
                    v-if="permissions.update"
                    :class="panelClass"
                    class="flex flex-col gap-3"
                    @submit.prevent="saveDetails"
                >
                    <h2 class="font-medium">Matter details</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label :class="labelClass">
                            Client name
                            <select
                                v-model="details.client_id"
                                :class="inputClass"
                                required
                            >
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="String(client.id)"
                                >
                                    {{ client.name }}
                                </option>
                            </select>
                        </label>
                        <label :class="labelClass">
                            Client reference number
                            <input
                                v-model="details.client_reference"
                                :class="inputClass"
                            />
                        </label>
                    </div>
                    <FormError :message="details.errors.client_reference" />
                    <label :class="labelClass">
                        Application number
                        <input
                            v-model="details.application_number"
                            :class="inputClass"
                        />
                    </label>
                    <label :class="labelClass">
                        HCI reference
                        <input
                            v-model="details.hci_reference"
                            :class="inputClass"
                        />
                    </label>
                    <label :class="labelClass">
                        Applicant name(s)
                        <textarea
                            v-model="details.applicant_names"
                            rows="3"
                            :class="inputClass"
                            required
                        />
                        <span :class="mutedClass">
                            Separate each name with a comma. One name does not
                            need a comma.
                        </span>
                    </label>
                    <FormError :message="details.errors.applicant_names" />
                    <label :class="labelClass">
                        Paper number
                        <input
                            v-model="details.paper_number"
                            :class="inputClass"
                            required
                        />
                    </label>
                    <label :class="labelClass">
                        OA type
                        <select v-model="details.type" :class="inputClass">
                            <option
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </label>
                    <label :class="labelClass">
                        Mailing date
                        <input
                            v-model="details.mailing_date"
                            type="date"
                            :class="inputClass"
                            required
                        />
                    </label>
                    <label :class="labelClass">
                        Response period (months)
                        <input
                            v-model.number="details.response_period_months"
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
                            v-model="details.original_deadline"
                            type="date"
                            :class="inputClass"
                        />
                    </label>
                    <p :class="mutedClass">
                        Leave the official deadline blank to calculate it from
                        the mailing date and response period. An extended
                        deadline stays visible separately.
                    </p>
                    <label :class="labelClass">
                        Internal response date
                        <input
                            v-model="details.internal_response_date"
                            type="date"
                            :class="inputClass"
                        />
                    </label>
                    <label :class="labelClass">
                        Reminder date
                        <input
                            v-model="details.reminder_date"
                            type="date"
                            :class="inputClass"
                        />
                    </label>
                    <FormError :message="details.errors.application_number" />
                    <FormError :message="details.errors.hci_reference" />
                    <FormError :message="details.errors.original_deadline" />
                    <button
                        type="submit"
                        :class="buttonClass"
                        class="self-start"
                        :disabled="details.processing"
                    >
                        Save details
                    </button>
                </form>

                <section :class="panelClass" class="flex flex-col gap-3">
                    <h2 class="font-medium">History</h2>
                    <p
                        v-if="officeAction.history.length === 0"
                        :class="mutedClass"
                    >
                        No changes have been recorded.
                    </p>
                    <ol v-else class="flex flex-col gap-4">
                        <li
                            v-for="item in officeAction.history"
                            :key="item.id"
                            class="flex flex-col gap-1"
                        >
                            <div class="text-sm">
                                <span class="font-medium">{{ item.user }}</span>
                                {{ item.event }}
                                <span :class="mutedClass"
                                    >· {{ item.when }}</span
                                >
                            </div>
                            <ul class="flex flex-col gap-1 text-sm">
                                <li
                                    v-for="(entry, index) in item.entries"
                                    :key="`${item.id}-${index}`"
                                >
                                    <span class="font-medium">{{
                                        entry.field
                                    }}</span>
                                    :
                                    <span :class="mutedClass">{{
                                        entry.before
                                    }}</span>
                                    → {{ entry.after }}
                                </li>
                            </ul>
                        </li>
                    </ol>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
