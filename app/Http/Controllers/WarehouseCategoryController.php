<?php

namespace App\Http\Controllers;

use App\Models\WarehouseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseCategoryController extends Controller
{
    /**
     * GET /api/warehouse-categories
     */
    public function index(): JsonResponse
    {
        $categories = WarehouseCategory::withCount('warehouse')->get();
        return response()->json(['categories' => $categories], 200);
    }

    /**
     * POST /api/warehouse-categories
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:warehouse_categories,name',
            'description' => 'nullable|string|max:250',
        ]);

        $category = WarehouseCategory::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * PUT /api/warehouse-categories/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $category = WarehouseCategory::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:50|unique:warehouse_categories,name,' . $category->id,
            'description' => 'nullable|string|max:250',
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/warehouse-categories/{id}
     */
    public function destroy($id): JsonResponse
    {
        $category = WarehouseCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
