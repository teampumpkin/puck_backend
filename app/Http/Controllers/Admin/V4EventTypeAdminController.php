<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\V4EventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class V4EventTypeAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => V4EventType::query()
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'active', 'sort_order'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:v4_event_types,name',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $type = V4EventType::create($validated);

        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = V4EventType::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:v4_event_types,name,'.$id,
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $type->update($validated);

        return response()->json(['success' => true, 'data' => $type]);
    }

    public function destroy(int $id): JsonResponse
    {
        V4EventType::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
