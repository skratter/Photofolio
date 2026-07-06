<?php

namespace App\Actions;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildViewHistoryAction
{
    /**
     * Buckets a viewable's recorded views into a non-overlapping,
     * decreasing-resolution timeline: the last 7 days individually, the
     * remaining months of the current year, prior years, and a grand total.
     *
     * @return array{daily: Collection<int, array{label: string, count: int}>, monthly: Collection<int, array{label: string, count: int}>, yearly: Collection<int, array{label: string, count: int}>, total: int}
     */
    public function execute(Viewable $viewable): array
    {
        $viewedAt = View::query()
            ->where('viewable_type', $viewable->getMorphClass())
            ->where('viewable_id', $viewable->getKey())
            ->pluck('viewed_at')
            ->map(fn (string $value): Carbon => Carbon::parse($value));

        $now = Carbon::now();
        $dailyCutoff = $now->clone()->subDays(6)->startOfDay();
        $yearCutoff = $now->clone()->startOfYear();

        $daily = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($now, $viewedAt) {
                $date = $now->clone()->subDays($daysAgo)->startOfDay();

                return [
                    'label' => $date->format('d.m.Y'),
                    'count' => $viewedAt->filter(fn (Carbon $viewedAt) => $viewedAt->isSameDay($date))->count(),
                ];
            });

        $monthly = $viewedAt
            ->filter(fn (Carbon $viewedAt) => $viewedAt->lt($dailyCutoff) && $viewedAt->gte($yearCutoff))
            ->groupBy(fn (Carbon $viewedAt) => $viewedAt->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn (Collection $group, string $key) => [
                'label' => Carbon::createFromFormat('Y-m', $key)->translatedFormat('F Y'),
                'count' => $group->count(),
            ])
            ->values();

        $yearly = $viewedAt
            ->filter(fn (Carbon $viewedAt) => $viewedAt->lt($yearCutoff))
            ->groupBy(fn (Carbon $viewedAt) => $viewedAt->format('Y'))
            ->sortKeysDesc()
            ->map(fn (Collection $group, string $key) => [
                'label' => $key,
                'count' => $group->count(),
            ])
            ->values();

        return [
            'daily' => $daily,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'total' => $viewedAt->count(),
        ];
    }
}
