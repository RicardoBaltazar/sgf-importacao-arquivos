<?php

namespace App\Observers;

use App\Models\FinancialStatistic;
use Illuminate\Support\Facades\Cache;

class FinancialStatisticObserver
{
    public function created(FinancialStatistic $financialStatistic): void
    {
        $this->clearCache($financialStatistic);
    }

    public function updated(FinancialStatistic $financialStatistic): void
    {
        $this->clearCache($financialStatistic);
    }

    private function clearCache(FinancialStatistic $financialStatistic): void
    {
        $cacheKey = "financial_statistics_user_{$financialStatistic->user_id}";
        Cache::forget($cacheKey);
    }
}
