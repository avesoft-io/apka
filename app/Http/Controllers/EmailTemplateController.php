<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', EmailTemplate::class);

        $user = $request->user();

        return Inertia::render('EmailTemplates/Index', [
            'templates' => EmailTemplate::query()->orderBy('id')->get(['id', 'key', 'name', 'subject', 'body']),
            'canEdit' => $user instanceof User && $user->isSupervisor(),
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update([
            'subject' => $request->string('subject')->toString(),
            'body' => $request->string('body')->toString(),
        ]);

        return redirect()
            ->route('email-templates.index')
            ->with('success', 'Template saved.');
    }
}
