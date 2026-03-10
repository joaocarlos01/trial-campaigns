<?php

namespace App\Providers;

use App\Repositories\CampaignRepository;
use App\Repositories\ContactListRepository;
use App\Repositories\ContactRepository;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\ContactListRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(ContactListRepositoryInterface::class, ContactListRepository::class);
        $this->app->bind(CampaignRepositoryInterface::class, CampaignRepository::class);
    }
}
