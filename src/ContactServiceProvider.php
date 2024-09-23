<?php

namespace Pjet\Runjet;

use Illuminate\Support\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{


    public function boot(){

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__. '/views', 'pjet');
      //  $this->loadMigrationsFrom(__DIR__.'/database/migrations');

    }

    public function register()
    {
        
    }
}