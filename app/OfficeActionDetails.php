<?php

namespace App;

use InvalidArgumentException;

final class OfficeActionDetails
{
    public function __construct(
        public ?int $clientId,
        public ?string $newClientName,
        public ?string $applicationNumber,
        public ?string $hciReference,
        public string $paperNumber,
        public string $type,
        public string $mailingDate,
        public int $responsePeriodMonths,
        public ?string $originalDeadline,
        public ?string $internalResponseDate,
        public ?string $reminderDate,
        public ?int $assigneeId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            clientId: self::nullableInt($data['client_id'] ?? null),
            newClientName: self::nullableString($data['new_client_name'] ?? null),
            applicationNumber: self::nullableString($data['application_number'] ?? null),
            hciReference: self::nullableString($data['hci_reference'] ?? null),
            paperNumber: self::requiredString($data['paper_number'] ?? null, 'paper_number'),
            type: self::requiredString($data['type'] ?? null, 'type'),
            mailingDate: self::requiredString($data['mailing_date'] ?? null, 'mailing_date'),
            responsePeriodMonths: (int) ($data['response_period_months'] ?? 0),
            originalDeadline: self::nullableString($data['original_deadline'] ?? null),
            internalResponseDate: self::nullableString($data['internal_response_date'] ?? null),
            reminderDate: self::nullableString($data['reminder_date'] ?? null),
            assigneeId: self::nullableInt($data['assignee_id'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private static function requiredString(mixed $value, string $field): string
    {
        $string = self::nullableString(is_scalar($value) ? (string) $value : null);

        if ($string === null) {
            throw new InvalidArgumentException($field.' is required.');
        }

        return $string;
    }
}
