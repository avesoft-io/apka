<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignOfficeActionRequest;
use App\Models\OfficeAction;
use App\OfficeActionWorkflow;
use Illuminate\Http\RedirectResponse;

class OfficeActionAssignmentController extends Controller
{
    public function update(
        AssignOfficeActionRequest $request,
        OfficeAction $officeAction,
        OfficeActionWorkflow $workflow,
    ): RedirectResponse {
        $workflow->assign($officeAction, $this->actor($request), $request->assigneeId());

        return redirect()
            ->route('office-actions.show', $officeAction)
            ->with('success', 'Assignment saved.');
    }
}
