<?php
// app/Http/Controllers/V4/V4ShareLinkController.php

namespace App\Http\Controllers\V4;

use App\Contracts\ErrorTrackerInterface;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\V4Event;
use App\Models\V4HockeyListing;
use App\Models\V4PlayerAchievement;
use App\Models\V4PlayerPortfolio;
use App\Models\V4UploadedMedia;
use App\Models\V4User;
use App\Services\EventPayloadBuilder;
use App\Services\HockeyListingPayloadBuilder;
use App\Services\PortfolioPayloadBuilder;
use App\Services\ShareLinkService;
use Exception;
use Illuminate\Database\Eloquent\Model;
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
        private EventPayloadBuilder $eventBuilder,
        private HockeyListingPayloadBuilder $listingBuilder,
    ) {}

    public function sharePortfolio(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $user      = Auth::guard('v4api')->user();
            $portfolio = V4PlayerPortfolio::with('player')->find($portfolioId);

            if (! $portfolio) {
                return response()->json(['success' => false, 'message' => 'Portfolio not found'], 404);
            }

            if (! $this->shareLinks->canViewPortfolio($portfolio, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $result = $this->shareLinks->mint($portfolio, $user);

            // Owner notification wired in Task 5 (only when a non-owner creates the link)

            return response()->json([
                'success' => true,
                'data'    => [
                    'url'       => $result['url'],
                    'is_public' => (bool) $portfolio->is_public,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error minting share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to create share link'], 500);
        }
    }

    public function revokePortfolioShare(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $user      = Auth::guard('v4api')->user();
            $portfolio = V4PlayerPortfolio::with('player')->find($portfolioId);

            if (! $portfolio) {
                return response()->json(['success' => false, 'message' => 'Portfolio not found'], 404);
            }

            if (! $this->shareLinks->canRevokePortfolio($portfolio, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $this->shareLinks->revoke($portfolio, $user);

            return response()->json(['success' => true, 'message' => 'Sharing stopped'], 200);
        } catch (Exception $e) {
            Log::error('Error revoking share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to stop sharing'], 500);
        }
    }

    public function resolveShared(Request $request, string $token): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $link = $this->shareLinks->resolve($token);

            if (! $link) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            $shared = $link->shareable;
            if (
                $user?->id !== $this->shareLinks->ownerId($shared)
                && ($reason = $this->shareLinks->blockReasonFor($shared))
            ) {
                return $this->privateResponse($reason);
            }

            $this->shareLinks->logOpen($token, $request->query('r'), $user?->id);

            // shareable_type is the morph alias ('portfolio'|'event'|'hockey_listing') — never a class name
            return response()->json([
                'success' => true,
                'data'    => array_merge(
                    ['shareable_type' => $link->shareable_type],
                    $this->fullBlock($link->shareable_type, $shared, $user),
                ),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error resolving share token: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to open shared content'], 500);
        }
    }

    public function previewShared(Request $request, string $token): JsonResponse
    {
        try {
            $link = $this->shareLinks->resolve($token);

            if (! $link) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            $shared = $link->shareable;

            if ($reason = $this->shareLinks->blockReasonFor($shared)) {
                return $this->privateResponse($reason);
            }

            // Explicit allowlist for anonymous viewers — never serialize models here.
            // Playable media / contact PII / exact location stay behind auth (see design spec).
            return response()->json([
                'success' => true,
                'data'    => array_merge(
                    ['shareable_type' => $link->shareable_type],
                    $this->previewBlock($link->shareable_type, $shared),
                ),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error building share preview: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to load preview'], 500);
        }
    }

    // Full authed payload block, keyed by morph alias. The response merges this with shareable_type.
    private function fullBlock(string $type, Model $shared, ?V4User $user): array
    {
        return match ($type) {
            'portfolio'      => ['portfolio' => $this->payloadBuilder->build($shared)],
            'event'          => ['event' => $this->eventBuilder->buildFull($shared, $user)],
            'hockey_listing' => ['hockey_listing' => $this->listingBuilder->buildFull($shared)],
            default          => [],
        };
    }

    // Anonymous teaser block, keyed by morph alias.
    private function previewBlock(string $type, Model $shared): array
    {
        return match ($type) {
            'portfolio'      => $this->portfolioPreview($shared),
            'event'          => $this->eventBuilder->buildPreview($shared->loadMissing('media')),
            'hockey_listing' => $this->listingBuilder->buildPreview($shared),
            default          => [],
        };
    }

    // Portfolio teaser — allowlist unchanged from the original inline previewShared logic.
    private function portfolioPreview(V4PlayerPortfolio $portfolio): array
    {
        $portfolio->loadMissing(['subs', 'player']);

        $counts = ['videos' => 0, 'evaluations' => 0, 'achievements' => 0];
        foreach ($portfolio->subs as $sub) {
            match ($sub->subable_type) {
                V4UploadedMedia::class     => $counts['videos']++,
                Evaluation::class          => $counts['evaluations']++,
                V4PlayerAchievement::class => $counts['achievements']++,
                default                    => null,
            };
        }

        return [
            'player'    => [
                'name'       => optional($portfolio->player)->name,
                'avatar_url' => optional($portfolio->player)->profile_photo,
            ],
            'portfolio' => [
                'title'         => $portfolio->title,
                'thumbnail_url' => $portfolio->thumbnail_path ?? null,
            ],
            'counts'    => $counts,
        ];
    }

    // 403 body is a fixed allowlist — no owner name or identifiers on blocked content
    private function privateResponse(string $reason): JsonResponse
    {
        $message = match ($reason) {
            'profile_private'     => "This player's profile is private",
            'portfolio_private'   => 'This portfolio is private',
            'event_cancelled'     => 'This event has been cancelled',
            'event_unavailable'   => 'This event is no longer available',
            'listing_sold'        => 'This item has been sold',
            'listing_unavailable' => 'This listing is no longer available',
            default               => 'This content is unavailable',
        };

        return response()->json(['success' => false, 'message' => $message, 'reason' => $reason], 403);
    }

    public function shareEvent(Request $request, V4Event $event): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            if (! $this->shareLinks->canView($event, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $result = $this->shareLinks->mint($event, $user);

            return response()->json([
                'success' => true,
                'data'    => ['url' => $result['url'], 'is_public' => $event->status === 'published'],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error minting event share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to create share link'], 500);
        }
    }

    public function revokeEventShare(Request $request, V4Event $event): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            if (! $this->shareLinks->canRevoke($event, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $this->shareLinks->revoke($event, $user);

            return response()->json(['success' => true, 'message' => 'Sharing stopped'], 200);
        } catch (Exception $e) {
            Log::error('Error revoking event share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to stop sharing'], 500);
        }
    }

    public function shareListing(Request $request, int $listing): JsonResponse
    {
        try {
            $user   = Auth::guard('v4api')->user();
            $record = V4HockeyListing::find($listing);

            if (! $record) {
                return response()->json(['success' => false, 'message' => 'Listing not found'], 404);
            }

            if (! $this->shareLinks->canView($record, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $result = $this->shareLinks->mint($record, $user);

            return response()->json([
                'success' => true,
                'data'    => ['url' => $result['url'], 'is_public' => $record->status === 'published'],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error minting listing share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to create share link'], 500);
        }
    }

    public function revokeListingShare(Request $request, int $listing): JsonResponse
    {
        try {
            $user   = Auth::guard('v4api')->user();
            $record = V4HockeyListing::find($listing);

            if (! $record) {
                return response()->json(['success' => false, 'message' => 'Listing not found'], 404);
            }

            if (! $this->shareLinks->canRevoke($record, $user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $this->shareLinks->revoke($record, $user);

            return response()->json(['success' => true, 'message' => 'Sharing stopped'], 200);
        } catch (Exception $e) {
            Log::error('Error revoking listing share link: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);

            return response()->json(['success' => false, 'message' => 'Failed to stop sharing'], 500);
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
            Log::error('Error logging share open: '.$e->getMessage());
            $this->errorTracker->captureException($e, ['action' => __METHOD__]);
        }

        return response()->noContent(); // 204 always — no validity oracle
    }
}
