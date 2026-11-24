<?php

namespace App\Http\Controllers;

use App\ActivityLogger;
use App\Http\Requests\StoreClientInstructionRequest;
use App\Models\Client;
use App\Models\ClientInstruction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientInstructionController extends Controller
{
    public function store(StoreClientInstructionRequest $request, Client $client, ActivityLogger $activities): RedirectResponse
    {
        $instruction = $client->instructions()->create([
            'body' => $request->string('body')->toString(),
            'created_by_id' => $this->actor($request)->id,
        ]);

        $activities->created($instruction, $this->actor($request), $client->id, ['body']);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Standing instruction added.');
    }

    public function destroy(Request $request, Client $client, ClientInstruction $instruction, ActivityLogger $activities): RedirectResponse
    {
        if ($instruction->client_id !== $client->id) {
            abort(404);
        }

        Gate::authorize('delete', $instruction);

        $activities->deleted($instruction, $this->actor($request), $instruction->body, $client->id);
        $instruction->delete();

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Standing instruction removed.');
    }
}
