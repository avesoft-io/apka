<?php

namespace App;

use App\Models\OfficeAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OfficeActionLister
{
    /**
     * @return Builder<OfficeAction>
     */
    public function query(OfficeActionList $list, User $user, ?int $clientId, ?int $assigneeId): Builder
    {
        $query = OfficeAction::query()->with(['client', 'assignee']);

        match ($list) {
            OfficeActionList::Mine => $query->open()->assignedTo($user)->orderByCurrentDeadline(),
            OfficeActionList::Upcoming => $query->upcoming()->orderByCurrentDeadline(),
            OfficeActionList::FollowUps => $query->followUps()->orderByCurrentDeadline(),
            OfficeActionList::WaitingInstructions => $query->waitingForInstructions()->orderByCurrentDeadline(),
            OfficeActionList::ForChecking => $query->forChecking()->latest('updated_at')->orderBy('id'),
            OfficeActionList::ReadyToFile => $query->readyToFile()->orderByCurrentDeadline(),
            OfficeActionList::PendingReporting => $query->pendingReporting()->orderBy('filing_date')->orderBy('id'),
            OfficeActionList::All => $query->orderByCurrentDeadline(),
        };

        if ($clientId !== null) {
            $query->where('client_id', $clientId);
        }

        if ($list !== OfficeActionList::Mine && $assigneeId !== null) {
            $query->where('assignee_id', $assigneeId);
        }

        return $query;
    }

    public function count(OfficeActionList $list, User $user): int
    {
        return $this->query($list, $user, null, null)->count();
    }
}
