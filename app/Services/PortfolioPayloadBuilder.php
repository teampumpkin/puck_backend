<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\V4PlayerAchievement;
use App\Models\V4PlayerPortfolio;
use App\Models\V4UploadedMedia;

class PortfolioPayloadBuilder
{
    /**
     * The exact payload shape the app's HockeyPortfolio.fromJson expects.
     * Moved verbatim from V4EvaluationController::getPlayerHockeyPortfolio.
     */
    public function build(V4PlayerPortfolio $portfolio): array
    {
        $portfolio->loadMissing(['subs.subable', 'player']);

        $evaluations = [];
        $achievements = [];
        $media = [];

        foreach ($portfolio->subs as $sub) {
            if (!$sub->subable)
                continue;

            switch ($sub->subable_type) {
                case Evaluation::class:
                    $eval = $sub->subable;
                    $eval->loadMissing(['submission.paymentRequest.inAppPurchase.marketplaceItem']);

                    $inApp = $eval->submission->paymentRequest?->inAppPurchase ?? null;
                    $marketItem = $inApp->marketplaceItem ?? null;

                    $evaluations[] = [
                        'id' => $eval->id,
                        'submission_id' => $eval->submission_id,
                        'assignment_id' => $eval->assignment_id,
                        'evaluator_id' => $eval->evaluator_id,
                        'notes' => $eval->notes ?? null,
                        'status' => $eval->status,
                        'created_at' => optional($eval->created_at)->toISOString(),
                        'updated_at' => optional($eval->updated_at)->toISOString(),
                        'marketplace_title' => $inApp->title ?? "",
                        'marketplace_type' => $marketItem->type ?? "",
                        'in_app_purchase_sku' => $inApp->sku ?? "",
                    ];
                    break;

                case V4PlayerAchievement::class:
                    $achievements[] = [
                        'id' => $sub->subable->id,
                        'title' => $sub->subable->title ?? "",
                        'file_path' => $sub->subable->file_path ?? "",
                        'details' => $sub->subable->details ?? null,
                        'description' => $sub->subable->description ?? null,
                    ];
                    break;

                case V4UploadedMedia::class:
                    $media[] = [
                        'id' => $sub->subable->id,
                        'file_path' => $sub->subable->file_path ?? "",
                    ];
                    break;
            }
        }

        return [
            'id' => $portfolio->id,
            'player_id' => $portfolio->player_id,
            'player_name' => optional($portfolio->player)->name,
            'title' => $portfolio->title,
            'description' => $portfolio->description,
            'meta' => $portfolio->meta,
            'thumbnail_path' => $portfolio->thumbnail_path ?? $portfolio->thumbnail ?? null,
            'is_public' => (bool) $portfolio->is_public,
            'created_at' => optional($portfolio->created_at)->toISOString(),
            'updated_at' => optional($portfolio->updated_at)->toISOString(),
            'evaluations' => $evaluations,
            'achievements' => $achievements,
            'videos' => $media,
        ];
    }
}
