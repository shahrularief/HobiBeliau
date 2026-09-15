<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Account not found. Register this email first.');
        return 1;
    }
    $user->forceFill(['is_admin' => true])->save();
    $this->info('Administrator access granted.');
})->purpose('Grant admin access to an existing account');
