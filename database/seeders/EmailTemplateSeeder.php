<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Seed the office-action email drafts.
     */
    public function run(): void
    {
        $this->storeTemplates();
    }

    public function seed(Organization $organization): void
    {
        $this->storeTemplates($organization);
    }

    /**
     * @return list<array{key: string, name: string, subject: string, body: string}>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'oa_report',
                'name' => 'Office action report',
                'subject' => 'Office action report — {{reference}}',
                'body' => <<<'TEXT'
Dear {{client}},

We received a {{oa_type}} (paper {{paper_number}}) mailed on {{mailing_date}} for {{reference}}.

The official deadline is {{current_deadline}}. The original deadline is {{original_deadline}}. Our internal response date is {{internal_response_date}}.

Please let us know how you would like us to proceed, including whether you want us to prepare a response or request an extension.

{{assignee}}
TEXT,
            ],
            [
                'key' => 'extension',
                'name' => 'Extension email',
                'subject' => 'Extension request — {{reference}}',
                'body' => <<<'TEXT'
Dear {{client}},

This note is about the extension for {{reference}} (paper {{paper_number}}, {{oa_type}}).

The request was filed on {{extension_requested_on}}. Its status is {{extension_status}}. The original deadline remains {{original_deadline}}. The extended deadline is {{extended_deadline}}. The deadline we are working to is {{current_deadline}}.

{{assignee}}
TEXT,
            ],
            [
                'key' => 'reminder',
                'name' => 'Reminder',
                'subject' => 'Reminder — {{reference}}',
                'body' => <<<'TEXT'
Dear {{client}},

This is a reminder about {{reference}} (paper {{paper_number}}, {{oa_type}} mailed {{mailing_date}}).

We still need your instructions. The reminder date on our file is {{reminder_date}}. The official deadline is {{current_deadline}} (original deadline {{original_deadline}}).

Please send your instructions when you can.

{{assignee}}
TEXT,
            ],
            [
                'key' => 'filing_report',
                'name' => 'Filing report',
                'subject' => 'Filing report — {{reference}}',
                'body' => <<<'TEXT'
Dear {{client}},

We filed the response to the {{oa_type}} (paper {{paper_number}}) for {{reference}} on {{filing_date}}.

The original deadline was {{original_deadline}}. The extended deadline was {{extended_deadline}}.

{{assignee}}
TEXT,
            ],
        ];
    }

    private function storeTemplates(?Organization $organization = null): void
    {
        foreach ($this->templates() as $template) {
            $lookup = ['key' => $template['key']];

            if ($organization instanceof Organization) {
                $lookup['organization_id'] = $organization->id;
                $template['organization_id'] = $organization->id;
            }

            EmailTemplate::query()->updateOrCreate($lookup, $template);
        }
    }
}
