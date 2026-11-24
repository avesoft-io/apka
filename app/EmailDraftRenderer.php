<?php

namespace App;

use App\Models\EmailTemplate;
use App\Models\OfficeAction;
use Carbon\CarbonInterface;

class EmailDraftRenderer
{
    /**
     * @return array{key: string, name: string, subject: string, body: string}
     */
    public function render(EmailTemplate $template, OfficeAction $officeAction): array
    {
        return [
            'key' => $template->key,
            'name' => $template->name,
            'subject' => $this->fill($template->subject, $officeAction),
            'body' => $this->fill($template->body, $officeAction),
        ];
    }

    public function fill(string $template, OfficeAction $officeAction): string
    {
        $officeAction->loadMissing(['client', 'assignee']);

        $reference = $officeAction->referenceLabel();

        $replacements = [
            '{{client}}' => $officeAction->client->name,
            '{{application_number}}' => $this->text($officeAction->application_number),
            '{{hci_reference}}' => $this->text($officeAction->hci_reference),
            '{{reference}}' => $reference,
            '{{paper_number}}' => $officeAction->paper_number,
            '{{oa_type}}' => $officeAction->type->label(),
            '{{mailing_date}}' => $this->date($officeAction->mailing_date),
            '{{response_period}}' => $officeAction->response_period_months.' months',
            '{{original_deadline}}' => $this->date($officeAction->original_deadline),
            '{{extended_deadline}}' => $this->date($officeAction->extended_deadline),
            '{{current_deadline}}' => $this->date($officeAction->currentDeadline()),
            '{{internal_response_date}}' => $this->date($officeAction->internal_response_date),
            '{{reminder_date}}' => $this->date($officeAction->reminder_date),
            '{{assignee}}' => $this->text($officeAction->assignee?->name),
            '{{extension_status}}' => $officeAction->extension_status->label(),
            '{{extension_requested_on}}' => $this->date($officeAction->extension_requested_on),
            '{{filing_date}}' => $this->date($officeAction->filing_date),
        ];

        return strtr($template, $replacements);
    }

    private function text(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'not set';
        }

        return $value;
    }

    private function date(?CarbonInterface $value): string
    {
        if ($value === null) {
            return 'not set';
        }

        return $value->format('j M Y');
    }
}
