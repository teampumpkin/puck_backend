<?php

namespace App\Http\Controllers\V4\Admin;



use App\Http\Controllers\Controller;
use App\Models\V4User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;


class V4DashboardController extends Controller
{
    public function getUserDistribution(Request $request): JsonResponse
    {
        $roleConfig = config('user_roles');

        // Cache for 5 minutes (300 seconds)
        $counts = Cache::remember('user_role_counts', 300, function () {
            return V4User::query()
                ->select('role', DB::raw('COUNT(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role');
        });

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

    public function getTotalUsers(Request $request): JsonResponse
    {
        // Total users now
        $totalUsers = V4User::count();

        // Total users at the end of last month
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $lastMonthTotal = V4User::where('created_at', '<=', $lastMonthEnd)->count();

        // Calculate percentage change
        if ($lastMonthTotal == 0) {
            // Avoid division by zero
            $changePercent = $totalUsers > 0 ? 100 : 0;
        } else {
            $changePercent = (($totalUsers - $lastMonthTotal) / $lastMonthTotal) * 100;
        }

        // Determine change type
        if ($changePercent > 0) {
            $changeType = 'positive';
        } elseif ($changePercent < 0) {
            $changeType = 'negative';
        } else {
            $changeType = 'neutral';
        }

        return response()->json([
            'value' => $totalUsers,
            'change' => round($changePercent, 2) . " %", // Rounded to 2 decimal places
            'changeType' => $changeType,
        ]);
    }
}
