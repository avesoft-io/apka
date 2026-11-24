<?php

namespace App\Http\Controllers;

use App\ActivityLogger;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientInstruction;
use App\OfficeActionPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Client::class);

        $clients = Client::query()
            ->withCount(['officeActions', 'instructions'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::query()->create([
            'organization_id' => $this->actor($request)->organization_id,
            'name' => $request->string('name')->toString(),
            'code' => $request->filled('code') ? $request->string('code')->toString() : null,
        ]);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client added.');
    }

    public function show(Client $client, OfficeActionPresenter $presenter): Response
    {
        Gate::authorize('view', $client);

        $client->load(['instructions.author']);

        $activities = Activity::query()
            ->with('user')
            ->forClient($client)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return Inertia::render('Clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
            ],
            'instructions' => $client->instructions
                ->sortBy('id')
                ->values()
                ->map(fn (ClientInstruction $instruction): array => [
                    'id' => $instruction->id,
                    'body' => $instruction->body,
                    'author' => $instruction->author?->name,
                    'created_at' => $instruction->created_at?->format('j M Y'),
                ])
                ->all(),
            'history' => $presenter->history($activities),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, ActivityLogger $activities): RedirectResponse
    {
        $client->fill([
            'name' => $request->string('name')->toString(),
            'code' => $request->filled('code') ? $request->string('code')->toString() : null,
        ]);

        $activities->updated($client, $this->actor($request), $client->id);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client saved.');
    }
}
