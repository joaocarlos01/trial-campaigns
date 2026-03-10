<?php

namespace App\Console;

use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        //$schedule->call(function () {
        //    $campaigns = Campaign::where('scheduled_at', '<=', now())->get();
//
        //    foreach ($campaigns as $campaign) {
        //        app(CampaignService::class)->dispatch($campaign);
        //    }
        //})->everyMinute();
        $schedule->call(function () {
            Campaign::where('status', 'draft')
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now())
                    ->chunkById(50, function ($campaigns) {
                        foreach ($campaigns as $campaign) {
                            app(CampaignService::class)->dispatch($campaign);
                        }
                    });
        })->everyMinute()->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
