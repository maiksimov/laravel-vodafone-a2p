<?php
namespace A2PVodafone\Providers;

use A2PVodafone\VodafoneClient;
use Illuminate\Support\ServiceProvider;

class VodafoneServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/vodafone_a2p.php' => config_path('vodafone_a2p.php'),
        ]);
    }

    public function register()
    {
        $this->app->bind(VodafoneClient::class, function () {
            return new VodafoneClient(config('vodafone_a2p.username'), config('vodafone_a2p.password'));
        });
    }
}