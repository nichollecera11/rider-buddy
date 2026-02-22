<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\SQLiteConnection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // STANDBY FOR LAT LNG LOGIC SQLITE ERROR
        // I-check kon SQLite ba ang gigamit nga connection
        if (config('database.default') === 'sqlite') {
            /** @var \PDO $connection */
            $connection = DB::connection()->getPdo();

            // I-check kon ang method exists ba gyud para dili mag-error ang IntelliSense
            if (method_exists($connection, 'sqliteCreateFunction')) {
                $connection->sqliteCreateFunction('acos', 'acos', 1);
                $connection->sqliteCreateFunction('cos', 'cos', 1);
                $connection->sqliteCreateFunction('sin', 'sin', 1);
                $connection->sqliteCreateFunction('radians', 'deg2rad', 1);
            }
        }
    }
}
