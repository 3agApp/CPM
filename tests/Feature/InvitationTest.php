<?php

use App\Enums\Role;
use App\Filament\Pages\Auth\Register as RegisterPage;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('can create an invitation', function () {
    $invitation = Invitation::factory()->create();

    expect($invitation)->toBeInstanceOf(Invitation::class)
        ->and($invitation->email)->not->toBeEmpty()
        ->and($invitation->token)->not->toBeEmpty()
        ->and($invitation->role)->toBe(Role::Member);
});

it('detects pending invitations', function () {
    $invitation = Invitation::factory()->create();

    expect($invitation->isPending())->toBeTrue()
        ->and($invitation->isAccepted())->toBeFalse()
        ->and($invitation->isExpired())->toBeFalse();
});

it('detects expired invitations', function () {
    $invitation = Invitation::factory()->expired()->create();

    expect($invitation->isExpired())->toBeTrue()
        ->and($invitation->isPending())->toBeFalse();
});

it('detects accepted invitations', function () {
    $invitation = Invitation::factory()->accepted()->create();

    expect($invitation->isAccepted())->toBeTrue()
        ->and($invitation->isPending())->toBeFalse();
});

it('has organization relationship', function () {
    $invitation = Invitation::factory()->create();

    expect($invitation->organization)->toBeInstanceOf(Organization::class);
});

it('has inviter relationship', function () {
    $invitation = Invitation::factory()->create();

    expect($invitation->inviter)->toBeInstanceOf(User::class);
});

it('accepts invitation for existing user', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $user->email,
        'role' => Role::Admin,
    ]);

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'Invitation accepted.');

    expect($user->fresh()->organizations()->whereKey($organization)->exists())->toBeTrue();
    expect($invitation->fresh()->isAccepted())->toBeTrue();
});

it('redirects authenticated invited users to their tenant dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $user->email,
    ]);

    $response = $this->actingAs($user)->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.pages.dashboard', ['tenant' => $organization->slug]));
    $response->assertSessionHas('filament.notifications.0.title', 'Invitation accepted.');
});

it('redirects to register for new user invitation', function () {
    $invitation = Invitation::factory()->create([
        'email' => 'newuser@example.com',
    ]);

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.register'));
    $response->assertSessionHas('filament.notifications.0.title', 'Please create an account to join the organization.');
    expect(session('pending_invitation_token'))->toBe($invitation->token);
});

it('rejects expired invitation', function () {
    $invitation = Invitation::factory()->expired()->create();

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'This invitation has expired. Please request a new one.');
});

it('rejects already accepted invitation', function () {
    $invitation = Invitation::factory()->accepted()->create();

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'This invitation has already been accepted.');
});

it('does not accept an invitation while signed in as a different user', function () {
    $organization = Organization::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $currentUser = User::factory()->create(['email' => 'current@example.com']);
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $invitedUser->email,
    ]);

    $response = $this->actingAs($currentUser)->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'Sign in with the invited account to accept this invitation.');

    expect($invitation->fresh()->isAccepted())->toBeFalse();
    expect($invitedUser->fresh()->organizations()->whereKey($organization)->exists())->toBeFalse();
});

it('accepts a pending invitation after matching registration', function () {
    Filament::setCurrentPanel('dashboard');

    $organization = Organization::factory()->create();
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'newuser@example.com',
    ]);

    session(['pending_invitation_token' => $invitation->token]);

    Livewire::test(RegisterPage::class)
        ->set('data.name', 'New User')
        ->set('data.email', $invitation->email)
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register')
        ->assertRedirect(route('filament.dashboard.pages.dashboard', ['tenant' => $organization->slug]));

    $user = User::where('email', $invitation->email)->first();

    expect($user)->not->toBeNull()
        ->and($user->organizations()->whereKey($organization)->exists())->toBeTrue()
        ->and($invitation->fresh()->isAccepted())->toBeTrue()
        ->and(session()->has('pending_invitation_token'))->toBeFalse();
});

it('keeps the pending invitation when registration email does not match', function () {
    Filament::setCurrentPanel('dashboard');

    $invitation = Invitation::factory()->create([
        'email' => 'invited@example.com',
    ]);

    session(['pending_invitation_token' => $invitation->token]);

    Livewire::test(RegisterPage::class)
        ->set('data.name', 'Wrong User')
        ->set('data.email', 'wrong@example.com')
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register');

    expect($invitation->fresh()->isAccepted())->toBeFalse()
        ->and(session('pending_invitation_token'))->toBe($invitation->token)
        ->and(User::where('email', 'wrong@example.com')->exists())->toBeTrue();
});

it('returns 404 for invalid token', function () {
    $response = $this->get(route('invitation.accept', ['token' => 'invalid-token']));

    $response->assertNotFound();
});
