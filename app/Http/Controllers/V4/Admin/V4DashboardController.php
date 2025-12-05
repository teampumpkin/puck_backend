<?php

namespace App\Http\Controllers\V4\Admin;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use App\Models\EvaluationSubmission;
use App\Models\V4Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class V4DashboardController extends Controller
{
    private function monthRanges(): array
    {
        return [
            'currentStart' => now()->startOfMonth(),
            'currentEnd'   => now()->endOfMonth(),
            'prevStart'    => now()->subMonth()->startOfMonth(),
            'prevEnd'      => now()->subMonth()->endOfMonth(),
        ];
    }

    private function calculateChange(int $current, int $previous): array
    {
        $changePercent = $previous > 0
            ? (($current - $previous) / $previous) * 100
            : ($current > 0 ? 100 : 0);

        $changeType = $current > $previous ? 'positive'
            : ($current < $previous ? 'negative' : 'neutral');

        return [
            'percent' => round($changePercent, 2) . ' %',
            'type' => $changeType,
        ];
    }

    private function cache(string $key, \Closure $callback)
    {
        return Cache::remember($key, 300, $callback);
    }

    public function getUserDistribution(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_user_distribution', function () {
            $roleConfig = config('user_roles');

            $counts = V4User::select('role', DB::raw('COUNT(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role');

            return collect($roleConfig)->map(function ($cfg, $role) use ($counts) {
                return [
                    'type'  => $cfg['label'],
                    'count' => $counts[$role] ?? 0,
                    'color' => $cfg['color'],
                ];
            })->values();
        });

        return response()->json($data);
    }

    public function getTotalUsers(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_total_users', function () {
            $dates = $this->monthRanges();

            $current = V4User::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4User::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();

            $change = $this->calculateChange($current, $previous);

            return [
                'value' => $current,
                'change' => $change['percent'],
                'changeType' => $change['type'],
            ];
        });

        return response()->json($data);
    }

    public function getPendingEvaluations(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_pending_evaluations', function () {
            $dates = $this->monthRanges();

            $current = EvaluationSubmission::where('status', 'pending')
                ->whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])
                ->count();

            $previous = EvaluationSubmission::where('status', 'pending')
                ->whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])
                ->count();

            $change = $this->calculateChange($current, $previous);

            return [
                'value' => $current,
                'change' => $change['percent'],
                'changeType' => $change['type'],
            ];
        });

        return response()->json($data);
    }

    public function getActiveEvents(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_active_events', function () {
            return [
                'value' => 0,
                'change' => '0 %',
                'changeType' => 'neutral',
            ];
        });

        return response()->json($data);
    }

    public function getSocialPosts(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_social_posts', function () {
            $dates = $this->monthRanges();

            $current = V4Post::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4Post::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();

            $change = $this->calculateChange($current, $previous);

            return [
                'value' => $current,
                'change' => $change['percent'],
                'changeType' => $change['type'],
            ];
        });

        return response()->json($data);
    }
}
