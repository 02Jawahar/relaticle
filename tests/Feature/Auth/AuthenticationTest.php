<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;

mutates(Login::class);

test('login screen can be rendered', function () {
    $response = $this->get(url()->getAppUrl('login'));

    $response->assertStatus(200);
});

test('login screen offers no social sign-in or self-service registration', function () {
    $response = $this->get(url()->getAppUrl('login'));

    $response->assertStatus(200)
        ->assertDontSee('Continue with Google')
        ->assertDontSee('Continue with GitHub')
        ->assertDontSee(__('filament-panels::auth/pages/login.actions.register.label'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    livewire(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(url()->getAppUrl((string) $team->slug));

    $this->assertAuthenticated();
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    livewire(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});
