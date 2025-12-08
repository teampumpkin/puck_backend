<?php

namespace App\Http\Controllers\V4\Admin;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use App\Constants\MarketplaceTypes;
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
            'prevStart'    => now()->copy()->subMonth()->startOfMonth(),
            'prevEnd'      => now()->copy()->subMonth()->endOfMonth(),
        ];
    }

    private function percentChange(int $current, int $previous): float
    {
        return $previous > 0
            ? (($current - $previous) / $previous) * 100
            : ($current > 0 ? 100 : 0);
    }

    private function percentDisplay(float $value): string
    {
        return round($value, 2) . ' %';
    }

    private function changeType(int $current, int $previous): string
    {
        return $current > $previous
            ? 'positive'
            : ($current < $previous ? 'negative' : 'neutral');
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

            return collect($roleConfig)
                ->map(fn($cfg, $role) => [
                    'type'  => $cfg['label'],
                    'count' => $counts[$role] ?? 0,
                    'color' => $cfg['color'],
                ])
                ->values();
        });

        return response()->json($data);
    }

    public function getTotalUsers(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_total_users_summary', function () {
            $dates = $this->monthRanges();

            $current = V4User::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4User::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();

            $percent = $this->percentChange($current, $previous);

            return [
                'value'      => $current,
                'change'     => $this->percentDisplay($percent),
                'changeType' => $this->changeType($current, $previous),
            ];
        });

        return response()->json($data);
    }

    public function getReportMetricTotalUsers(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_report_total_users', function () {
            $dates = $this->monthRanges();

            $current = V4User::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4User::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();
            $change = $this->percentChange($current, $previous);

            return [
                'metric'   => "Total Users",
                'current'  => $current,
                'previous' => $previous,
                'change'   => round($change, 2),
            ];
        });

        return response()->json($data);
    }

    public function getPendingEvaluations(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_pending_evaluations_summary', function () {
            $dates = $this->monthRanges();

            $current = EvaluationSubmission::pending()->whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = EvaluationSubmission::pending()->whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();

            $percent = $this->percentChange($current, $previous);

            return [
                'value'      => $current,
                'change'     => $this->percentDisplay($percent),
                'changeType' => $this->changeType($current, $previous),
            ];
        });

        return response()->json($data);
    }

    public function getReportMetricPendingEvaluations(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_report_pending_evaluations', function () {
            $dates = $this->monthRanges();

            $current = EvaluationSubmission::pending()->whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = EvaluationSubmission::pending()->whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();
            $change = $this->percentChange($current, $previous);

            return [
                'metric'   => "Pending Evaluations",
                'current'  => $current,
                'previous' => $previous,
                'change'   => round($change, 2),
            ];
        });

        return response()->json($data);
    }

    public function getReportMetricGrowth(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_metric_growth_chart', function () {

            // Last 12 months including current
            $months = collect(range(0, 11))
                ->map(fn($i) => now()->subMonths($i)->startOfMonth())
                ->reverse()
                ->values();

            return $months->map(function ($month) {

                $start = $month->copy()->startOfMonth();
                $end   = $month->copy()->endOfMonth();

                // Users for the month
                $users = V4User::whereBetween('created_at', [$start, $end])->count();

                // Evaluations for the month
                $evaluations = EvaluationSubmission::whereBetween('created_at', [$start, $end])->count();

                // Events for the month (if Event model exists)
                $events = 0;
                return [
                    "month"       => $month->format("M"),
                    "users"       => $users,
                    "evaluations" => $evaluations,
                    "events"      => $events,
                ];
            });
        });

        return response()->json($data);
    }


    public function getReportMetricEvaluationTypes(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_evaluation_types', function () {

            $typeMapping = [
                MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION => [
                    'name'  => "Video Evaluation",
                    'color' => "#F59E0B",
                ],
                MarketplaceTypes::CONSULTATION_VIDEO_CALL => [
                    'name'  => "1-on-1 Consultation",
                    'color' => "#06B6D4",
                ],
                MarketplaceTypes::MENTORSHIP_PROGRAM => [
                    'name'  => "12-Week Mentorship",
                    'color' => "#8B5CF6",
                ],
                MarketplaceTypes::PROFESSIONAL_HOCKEY_PORTFOLIO => [
                    'name'  => "Hockey Portfolio",
                    'color' => "#10B981",
                ],
            ];

            $results = [];

            foreach ($typeMapping as $marketplaceType => $display) {

                // Count DB rows where the marketplace item matches the type
                $count = EvaluationSubmission::whereHas(
                    'paymentRequest.inAppPurchase.marketplaceItem',
                    function ($q) use ($marketplaceType) {
                        $q->where('type', $marketplaceType);
                    }
                )->count();

                $results[] = [
                    'name'  => $display['name'],
                    'value' => $count,
                    'color' => $display['color'],
                ];
            }

            return $results;
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

    public function getReportMetricActiveEvents(Request $request): JsonResponse
    {
        return response()->json([
            'metric'   => "Active Events",
            'current'  => 0,
            'previous' => 0,
            'change'   => 0,
        ]);
    }

    public function getSocialPosts(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_social_posts_summary', function () {
            $dates = $this->monthRanges();

            $current = V4Post::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4Post::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();
            $percent = $this->percentChange($current, $previous);

            return [
                'value'      => $current,
                'change'     => $this->percentDisplay($percent),
                'changeType' => $this->changeType($current, $previous),
            ];
        });

        return response()->json($data);
    }

    public function getReportMetricSocialPosts(Request $request): JsonResponse
    {
        $data = $this->cache('dashboard_report_social_posts', function () {
            $dates = $this->monthRanges();

            $current = V4Post::whereBetween('created_at', [$dates['currentStart'], $dates['currentEnd']])->count();
            $previous = V4Post::whereBetween('created_at', [$dates['prevStart'], $dates['prevEnd']])->count();
            $change = $this->percentChange($current, $previous);

            return [
                'metric'   => "Social Posts",
                'current'  => $current,
                'previous' => $previous,
                'change'   => round($change, 2),
            ];
        });

        return response()->json($data);
    }

    public function getRecentActivity(Request $request): JsonResponse
    {


        $response = [
            // [
            //     "id" => 1,
            //     "type" => "evaluation",
            //     "user" => "Connor McDavid Jr.",
            //     "action" => "submitted skating evaluation",
            //     "status" => "pending",
            //     "time" => "2 hours ago"
            // ],
            // [
            //     "id" => 2,
            //     "type" => "event",
            //     "user" => "Emma Thompson",
            //     "action" => "registered for Summer Elite Camp",
            //     "status" => "approved",
            //     "time" => "4 hours ago"
            // ]
        ];

        return response()->json($response);
    }
}
