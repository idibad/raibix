<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryIssue;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display Inventory items, categories, and issues.
     */
    public function index()
    {
        $categories = InventoryCategory::all();
        $items = InventoryItem::with('categoryRelation')->get();
        $issues = InventoryIssue::with('item', 'user')->latest()->get();
        $staff = User::whereIn('user_type', ['teacher', 'admin', 'staff', 'registrar', 'accountant'])->get();

        return Inertia::render('Tenant/Admin/ERP/Inventory/Index', [
            'categories' => $categories,
            'items' => $items,
            'issues' => $issues,
            'staff' => $staff,
        ]);
    }

    /**
     * Store new inventory category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        InventoryCategory::create($validated);

        return redirect()->back()->with('success', 'Inventory category created successfully.');
    }

    /**
     * Store new inventory item.
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category_id' => 'required|exists:inventory_categories,id',
            'quantity' => 'required|integer|min:0',
            'room_location' => 'nullable|string|max:255',
            'condition' => 'required|string|in:good,maintenance_required,broken',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $category = InventoryCategory::findOrFail($validated['category_id']);
        
        InventoryItem::create([
            'item_name' => $validated['item_name'],
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => $validated['quantity'],
            'room_location' => $validated['room_location'],
            'condition' => $validated['condition'],
            'unit_cost' => $validated['unit_cost'],
        ]);

        return redirect()->back()->with('success', 'Inventory item added successfully.');
    }

    /**
     * Issue an item to a staff member.
     */
    public function issueItem(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'issue_date' => 'required|date',
        ]);

        $item = InventoryItem::findOrFail($validated['item_id']);

        if ($item->quantity < $validated['quantity']) {
            return redirect()->back()->with('error', 'Insufficient item quantity in stock.');
        }

        DB::beginTransaction();
        try {
            InventoryIssue::create([
                'item_id' => $item->id,
                'user_id' => $validated['user_id'],
                'quantity' => $validated['quantity'],
                'issue_date' => $validated['issue_date'],
                'status' => 'issued',
            ]);

            $item->decrement('quantity', $validated['quantity']);

            DB::commit();
            return redirect()->back()->with('success', 'Inventory item issued successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to issue item: ' . $e->getMessage());
        }
    }

    /**
     * Process item return or loss status.
     */
    public function updateIssueStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:returned,lost',
            'return_date' => 'nullable|date|required_if:status,returned',
        ]);

        $issue = InventoryIssue::findOrFail($id);
        if ($issue->status !== 'issued') {
            return redirect()->back()->with('error', 'This issue has already been resolved.');
        }

        $item = InventoryItem::findOrFail($issue->item_id);

        DB::beginTransaction();
        try {
            $issue->update([
                'status' => $validated['status'],
                'return_date' => $validated['status'] === 'returned' ? $validated['return_date'] : null,
            ]);

            if ($validated['status'] === 'returned') {
                $item->increment('quantity', $issue->quantity);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Inventory issue status updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update issue status: ' . $e->getMessage());
        }
    }
}
