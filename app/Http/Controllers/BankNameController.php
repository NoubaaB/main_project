<?php

namespace App\Http\Controllers;

use App\Models\BankName;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class BankNameController extends Controller
{
    use BroadcastsModelChanges;

    public function index()
    {
        $bank_names = BankName::orderBy('name')->get();
        return response()->json(['bank_names' => $bank_names]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bank_names,name',
        ]);

        $bankName = BankName::create($validated);
        $this->broadcastChange('BankName', 'created', $bankName->id);

        return response()->json(['bank_name' => $bankName], 201);
    }

    public function update(Request $request, BankName $bankName)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bank_names,name,' . $bankName->id,
        ]);

        $bankName->update($validated);
        $this->broadcastChange('BankName', 'updated', $bankName->id);

        return response()->json(['bank_name' => $bankName]);
    }

    public function destroy(BankName $bankName)
    {
        $bankName->delete();
        $this->broadcastChange('BankName', 'deleted', $bankName->id);

        return response()->json(['message' => 'Deleted']);
    }
}
