export type OfficeActionSummary = {
    id: number;
    client_id: number;
    client_name: string;
    client_reference: string | null;
    application_number: string | null;
    application_name: string | null;
    hci_reference: string | null;
    applicant_names: string | null;
    reference: string;
    paper_number: string;
    type: string;
    type_label: string;
    mailing_date: string | null;
    response_period_months: number;
    original_deadline: string | null;
    extended_deadline: string | null;
    current_deadline: string | null;
    extension_status: string;
    extension_label: string;
    internal_response_date: string | null;
    reminder_date: string | null;
    assignee_id: number | null;
    assignee_name: string | null;
    status: string;
    next_step: string | null;
    outcome: string;
    due_label: string | null;
    overdue: boolean;
    checking_status: string;
};

export type ChecklistStep = {
    key: string;
    label: string;
    detail: string;
    state: 'done' | 'current' | 'upcoming' | 'skipped' | 'attention';
};

export type HistoryEntry = {
    field: string;
    before: string;
    after: string;
};

export type HistoryItem = {
    id: number;
    event: string;
    user: string;
    when: string;
    entries: HistoryEntry[];
};

export type StandingInstruction = {
    id: number;
    body: string;
    author: string | null;
    created_at: string | null;
};

export type EmailDraft = {
    key: string;
    name: string;
    subject: string;
    body: string;
};

export type OfficeActionDetail = OfficeActionSummary & {
    report_sent_on: string | null;
    report_includes_fees: boolean;
    report_requests_instructions: boolean;
    response_instructions: string | null;
    instructions_received_on: string | null;
    extension_requested_on: string | null;
    draft_notes: string | null;
    response_prepared: boolean;
    amended_claims_prepared: boolean;
    other_documents_prepared: boolean;
    draft_prepared_on: string | null;
    client_approval_required: boolean;
    client_approval_status: string;
    filing_date: string | null;
    filing_reported_on: string | null;
    abandon_reason: string | null;
    checklist: ChecklistStep[];
    filing_blockers: string[];
    standing_instructions: StandingInstruction[];
    history: HistoryItem[];
};

export type NamedOption = {
    id: number;
    name: string;
};

export type TypeOption = {
    value: string;
    label: string;
};

export type ListOption = {
    value: string;
    label: string;
    description: string;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
