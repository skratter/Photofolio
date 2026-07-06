<?php

namespace App\Actions;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Support\Collection;

class BuildViewOriginsAction
{
    /**
     * @return array{referrers: Collection<int, array{label: string, count: int}>, userAgents: Collection<int, array{label: string, count: int}>}
     */
    public function execute(Viewable $viewable): array
    {
        $views = View::query()
            ->where('viewable_type', $viewable->getMorphClass())
            ->where('viewable_id', $viewable->getKey())
            ->get(['referrer', 'user_agent']);

        $referrers = $views
            ->map(function (View $view) {
                $referrer = $view->getAttribute('referrer');

                return $this->referrerLabel(is_string($referrer) ? $referrer : null);
            })
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->values();

        $userAgents = $views
            ->map(function (View $view) {
                $userAgent = $view->getAttribute('user_agent');

                return is_string($userAgent) && $userAgent !== '' ? $userAgent : 'Unbekannt';
            })
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->values();

        return [
            'referrers' => $referrers,
            'userAgents' => $userAgents,
        ];
    }

    private function referrerLabel(?string $referrer): string
    {
        if ($referrer === null || $referrer === '') {
            return 'Direkt aufgerufen';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $referrer;
    }
}
