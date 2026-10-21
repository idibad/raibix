<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $platformName = config('app.name', 'LMS Cloud OS');
            try {
                $centralConn = config('tenancy.database.central_connection', config('database.default'));
                if (\Illuminate\Support\Facades\Schema::connection($centralConn)->hasTable('saas_settings')) {
                    $dbName = \Illuminate\Support\Facades\DB::connection($centralConn)->table('saas_settings')->value('platform_name');
                    if (!empty($dbName)) $platformName = $dbName;
                }
            } catch (\Exception $e) {}
        @endphp
        <title inertia>{{ $platformName }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
