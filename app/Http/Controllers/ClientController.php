<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\PlafondService;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Display a listing of clients.
     */
    public function index()
    {
        $clients = Client::withCount(['shippings', 'productions'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->usage_count = $client->shippings_count + $client->productions_count;
                return $client;
            });

        return response()->json(['clients' => $clients]);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2|unique:clients,name',
            'phone' => 'nullable|string|max:20',
            'ice' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:50',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:200',
            'postal_code' => 'nullable|string|max:10',
            'website' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:200',
            'active_status' => 'nullable|boolean',
            'enable_plafond' => 'nullable|boolean',
            'plafond_amount' => 'nullable|numeric|min:0',
            'block_on_plafond' => 'nullable|boolean',
            'plafond_mode' => 'nullable|string|in:monthly,yearly',
            'plafond_blocking_scope' => 'nullable|string|in:past_unpaid,current_month_only',
        ]);

        $client = Client::create($validated);
        $this->broadcastChange('Client', 'created', $client->id, $client->toArray());

        return response()->json(['client' => $client], 201);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        $client->loadCount(['shippings', 'productions']);
        $client->usage_count = $client->shippings_count + $client->productions_count;
        return response()->json(['client' => $client]);
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2|unique:clients,name,' . $client->id,
            'phone' => 'nullable|string|max:20',
            'ice' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:50',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:200',
            'postal_code' => 'nullable|string|max:10',
            'website' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:200',
            'active_status' => 'nullable|boolean',
            'enable_plafond' => 'nullable|boolean',
            'plafond_amount' => 'nullable|numeric|min:0',
            'block_on_plafond' => 'nullable|boolean',
            'plafond_mode' => 'nullable|string|in:monthly,yearly',
            'plafond_blocking_scope' => 'nullable|string|in:past_unpaid,current_month_only',
        ]);

        $client->update($validated);
        $this->broadcastChange('Client', 'updated', $client->id, $client->toArray());

        return response()->json(['client' => $client]);
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Client $client)
    {
        $clientId = $client->id;
        $client->delete();
        $this->broadcastChange('Client', 'deleted', $clientId);

        return response()->json(['message' => 'Client deleted successfully']);
    }

    /**
     * Check client plafond status for a given date and new amount.
     */
    public function checkPlafond(Request $request, Client $client, PlafondService $service)
    {
        $date = $request->input('date');
        $amount = (float) $request->input('amount', 0);

        $result = $service->checkClientPlafond($client, $date, $amount);

        return response()->json($result);
    }
}
