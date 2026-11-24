<?php

namespace App;

enum OfficeActionList: string
{
    case Mine = 'mine';
    case Upcoming = 'upcoming';
    case FollowUps = 'follow-ups';
    case WaitingInstructions = 'waiting-instructions';
    case ForChecking = 'for-checking';
    case ReadyToFile = 'ready-to-file';
    case PendingReporting = 'pending-reporting';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Mine => 'My Office Actions',
            self::Upcoming => 'Upcoming deadlines',
            self::FollowUps => 'Client follow-ups',
            self::WaitingInstructions => 'Waiting for instructions',
            self::ForChecking => 'Drafts for checking',
            self::ReadyToFile => 'Ready to file',
            self::PendingReporting => 'Pending reporting',
            self::All => 'All office actions',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Mine => 'Office actions assigned to you.',
            self::Upcoming => 'Open responses with an official deadline in the next 30 days, including overdue items.',
            self::FollowUps => 'Reminder dates that are due and have not been marked sent.',
            self::WaitingInstructions => 'Reported to the client, still waiting for instructions.',
            self::ForChecking => 'Drafts marked for checking or sent back for revision.',
            self::ReadyToFile => 'Checks and approvals are done, and the response has not been filed.',
            self::PendingReporting => 'Filed, and the filing report has not been sent.',
            self::All => 'Every office action, including completed and abandoned matters.',
        };
    }
}
