<?php
// app/Services/ShareLinkService.php

namespace App\Services;

use App\Models\V4PlayerPortfolio;
use App\Models\V4ShareLink;
use App\Models\V4ShareLinkLog;
use App\Models\V4User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ShareLinkService
{
    public function canViewPortfolio(V4PlayerPortfolio $portfolio, V4User $user): bool
    {
        return $portfolio->player_id === $user->id || (bool) $portfolio->is_public;
    }

    /**
     * Owner can flip the portfolio or their whole profile private after a link
     * was minted — active tokens must then stop resolving for viewers.
     * Returns null when viewable, else 'profile_private' | 'portfolio_private'.
     */
    public function blockReason(V4PlayerPortfolio $portfolio): ?string
    {
        if (optional($portfolio->player)->enable_private_account) {
            return 'profile_private';
        }

        return $portfolio->is_public ? null : 'portfolio_private';
    }

    public function canRevokePortfolio(V4PlayerPortfolio $portfolio, V4User $user): bool
    {
        if ($portfolio->player_id === $user->id) {
            return true;
        }

        return optional($portfolio->player)->parent_id === $user->id;
    }

    /**
     * @return array{url: string, token: string, ref_code: string, was_created: bool}
     * @throws \Illuminate\Database\QueryException if the retry insert also collides (probability ~0)
     */
    public function mint(Model $shareable, V4User $user): array
    {
        $link = $shareable->morphMany(V4ShareLink::class, 'shareable')->active()->first();
        $wasCreated = false;

        if (!$link) {
            try {
                $link = V4ShareLink::create([
                    'token' => Str::random(32),
                    'shareable_type' => $shareable->getMorphClass(),
                    'shareable_id' => $shareable->getKey(),
                    'created_by' => $user->id,
                ]);
                $wasCreated = true;
            } catch (QueryException $e) {
                // 23505 = unique violation: concurrent mint won (either index), or the
                // astronomically unlikely token collision. Re-select; retry token once.
                if ($e->getCode() !== '23505') {
                    throw $e;
                }
                $link = $shareable->morphMany(V4ShareLink::class, 'shareable')->active()->first();
                if (!$link) {
                    // Token collision (not a concurrent-mint race); retry once — a second collision propagates by design.
                    $link = V4ShareLink::create([
                        'token' => Str::random(32),
                        'shareable_type' => $shareable->getMorphClass(),
                        'shareable_id' => $shareable->getKey(),
                        'created_by' => $user->id,
                    ]);
                    $wasCreated = true;
                }
            }

            if ($wasCreated) {
                $this->log($link, $user->id, 'created');
            }
        }

        $refCode = Str::random(8);
        $this->log($link, $user->id, 'shared', $refCode);

        return [
            'url' => config('services.share_link.base_url') . "/s/{$link->token}?r={$refCode}",
            'token' => $link->token,
            'ref_code' => $refCode,
            'was_created' => $wasCreated,
        ];
    }

    public function revoke(Model $shareable, V4User $user): bool
    {
        $link = $shareable->morphMany(V4ShareLink::class, 'shareable')->active()->first();
        if (!$link) {
            return false;
        }

        $link->update(['revoked_at' => now(), 'revoked_by' => $user->id]);
        $this->log($link, $user->id, 'revoked');

        return true;
    }

    public function resolve(string $token): ?V4ShareLink
    {
        $link = V4ShareLink::active()->where('token', $token)->first();
        if (!$link) {
            return null;
        }

        // morphTo respects SoftDeletes: trashed or hard-deleted target resolves to null
        if (!$link->shareable) {
            return null;
        }

        return $link;
    }

    public function logOpen(string $token, mixed $refCode, ?int $userId): void
    {
        $link = V4ShareLink::where('token', $token)->first();
        if (!$link) {
            return; // silent: no validity oracle
        }

        // ponytail: non-string (e.g. array from ?r[]=x) treated as absent — covers both callers
        if (!is_string($refCode) || !preg_match('/^[A-Za-z0-9]{8}$/', $refCode)) {
            $refCode = null;
        }

        $this->log($link, $userId, 'opened', $refCode);
    }

    private function log(V4ShareLink $link, ?int $userId, string $action, ?string $refCode = null): void
    {
        V4ShareLinkLog::create([
            'share_link_id' => $link->id,
            'user_id' => $userId,
            'action' => $action,
            'ref_code' => $refCode,
            'created_at' => now(),
        ]);
    }
}
