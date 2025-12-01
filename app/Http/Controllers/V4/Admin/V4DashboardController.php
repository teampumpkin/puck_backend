<?php

namespace App\Http\Controllers\V4\Admin;



use App\Http\Controllers\Controller;
use App\Models\V4User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;


class V4DashboardController extends Controller
{
    public function getUserDistribution(Request $request): JsonResponse
    {

        $roleConfig = config('user_roles');

        // Get counts grouped by role (only 1 query)
        $counts = V4User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        // Build response
        $response = collect($roleConfig)->map(function ($data, $role) use ($counts) {
            return [
                'type'  => $data['label'],
                'count' => $counts[$role] ?? 0,
                'color' => $data['color'],
            ];
        })->values();

        return response()->json($response);
    }
}
