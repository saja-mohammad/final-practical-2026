<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceRequestController
{
    public function index(Request $r)
    {
        $query = MaintenanceRequest::with(['customer', 'technician']);

        if ($r->filled('search')) {
            $search = $r->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($r->filled('status')) {
            $query->where('status', $r->status);
        }

        if ($r->filled('priority')) {
            $query->where('priority', $r->priority);
        }

        if ($r->filled('technician_id')) {
            $query->where('technician_id', $r->technician_id);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        return view('requests.index', compact('requests'));
    }

    public function create()
    {
        return view('requests.create', [
            'customers' => Customer::orderBy('name')->get(),
            'technicians' => User::where('role', 'technician')->orderBy('name')->get()
        ]);
    }

    public function store(Request $r)
    {
        $validated = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'technician')],
            'title' => 'required|min:5|max:100',
            'description' => 'required|min:10',
            'priority' => 'required|in:low,medium,high',
            'requested_at' => 'required|date',
        ]);

        $validated['status'] = 'pending';

        MaintenanceRequest::create($validated);

        return redirect()->route('requests.index')->with('success', 'Request created.');
    }

    public function show(MaintenanceRequest $request)
    {
        $request->load(['customer', 'technician', 'rating']);
        return view('requests.show', ['m' => $request]);
    }

    public function edit(MaintenanceRequest $request)
    {
        $this->authorizeAccess($request);

        return view('requests.edit', [
            'maintenanceRequest' => $request,
            'customers' => Customer::orderBy('name')->get(),
            'technicians' => User::where('role', 'technician')->orderBy('name')->get()
        ]);
    }

    public function update(Request $r, MaintenanceRequest $request)
    {
        $this->authorizeAccess($request);

        $validated = $r->validate([
            'title' => 'required|min:5|max:100',
            'description' => 'required|min:10',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'customer_id' => 'required|exists:customers,id',
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'technician')],
            'requested_at' => 'required|date'
        ]);

        $request->update($validated);

        return redirect()->route('requests.show', $request)->with('success', 'Request updated.');
    }

    public function destroy(MaintenanceRequest $request)
    {
        $this->authorizeAccess($request);

        $request->delete();

        return redirect()->route('requests.index')->with('success', 'Request deleted.');
    }

    private function authorizeAccess(MaintenanceRequest $request): void
    {
        $user = auth()->user();

if ($user->role === 'admin') {
                return;
        }

        if ($request->technician_id !== $user->id) {
            abort(403, 'You are not authorized to modify this request.');
        }
    }
}