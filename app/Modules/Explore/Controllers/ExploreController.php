<?php

declare(strict_types=1);

namespace App\Modules\Explore\Controllers;

use App\Controllers\BaseController;
use App\Modules\Explore\Services\ExploreService;
use CodeIgniter\Exceptions\PageNotFoundException;

final class ExploreController extends BaseController
{
    public function search(): string
    {
        $q = trim((string) $this->request->getGet('q'));
        $results = $this->service()->search($this->tenant(), $q);
        return view('explore/search', ['q' => $q, 'results' => $results]);
    }

    public function calendar(): string
    {
        $month = (string) $this->request->getGet('month');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $events = $this->service()->calendarEvents($this->tenant(), $month);
        return view('explore/calendar', ['month' => $month, 'events' => $events]);
    }
    private function tenant(): int
    {
        $tenantId = service('activeTenantResolver')->forUser((int) auth()->user()->id);

        if ($tenantId === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $tenantId;
    }

    private function service(): ExploreService
    {
        return service('exploreService');
    }
}
