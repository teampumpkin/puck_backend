<?php
// app/Http/Controllers/V4/V4ShareLinkController.php

namespace App\Http\Controllers\V4;

use App\Contracts\ErrorTrackerInterface;
use App\Http\Controllers\Controller;
use App\Models\V4PlayerPortfolio;
use App\Services\PortfolioPayloadBuilder;
use App\Services\ShareLinkService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class V4ShareLinkController extends Controller
{
    public function __construct(
        private ErrorTrackerInterface $errorTracker,
        private ShareLinkService $shareLinks,
        private PortfolioPayloadBuilder $payloadBuilder,
    ) {
    }

    public function sharePortfolio(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $portfolio = V4PlayerPortfolio::with('player')->find($portfolioId);

            if (!$portfolio) {
                return response()->json(['success' => false, 'message' => 'Portfolio not found'], 404);
            }

            if (!$this->shareLinks->canViewPortfolio($portfolio, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $result = $this->shareLinks->mint($portfolio, $user);

            // Owner notification wired in Task 5 (only when a non-owner creates the link)

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $result['url'],
                    'is_public' => (bool) $portfolio->is_public,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error minting share link: ' . $e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to create share link'], 500);
        }
    }

    public function revokePortfolioShare(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $portfolio = V4PlayerPortfolio::with('player')->find($portfolioId);

            if (!$portfolio) {
                return response()->json(['success' => false, 'message' => 'Portfolio not found'], 404);
            }

            if (!$this->shareLinks->canRevokePortfolio($portfolio, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $this->shareLinks->revoke($portfolio, $user);

            return response()->json(['success' => true, 'message' => 'Sharing stopped'], 200);
        } catch (Exception $e) {
            Log::error('Error revoking share link: ' . $e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to stop sharing'], 500);
        }
    }

    public function resolveShared(Request $request, string $token): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $link = $this->shareLinks->resolve($token);

            if (!$link) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            $this->shareLinks->logOpen($token, $request->query('r'), $user?->id);

            // shareable_type is the morph alias ('portfolio') — never a class name
            return response()->json([
                'success' => true,
                'data' => [
                    'shareable_type' => $link->shareable_type,
                    'portfolio' => $this->payloadBuilder->build($link->shareable),
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error resolving share token: ' . $e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to open shared content'], 500);
        }
    }

    public function logOpen(Request $request, string $token): Response
    {
        try {
            $userAgent = strtolower($request->userAgent() ?? '');
            foreach (['bot', 'crawl', 'spider', 'preview', 'facebookexternalhit', 'whatsapp', 'telegram', 'slack'] as $bot) {
                if (str_contains($userAgent, $bot)) {
                    return response()->noContent();
                }
            }

            $this->shareLinks->logOpen($token, $request->input('r'), null);
        } catch (Exception $e) {
            Log::error('Error logging share open: ' . $e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);
        }

        return response()->noContent(); // 204 always — no validity oracle
    }
}
