<?php

use App\Enums\Role;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;

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

    $response->assertRedirect();

    expect($user->fresh()->organizations()->whereKey($organization)->exists())->toBeTrue();
    expect($invitation->fresh()->isAccepted())->toBeTrue();
});

it('redirects to register for new user invitation', function () {
    $invitation = Invitation::factory()->create([
        'email' => 'newuser@example.com',
    ]);

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.register'));
    expect(session('pending_invitation_token'))->toBe($invitation->token);
});

it('rejects expired invitation', function () {
    $invitation = Invitation::factory()->expired()->create();

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
});

it('rejects already accepted invitation', function () {
    $invitation = Invitation::factory()->accepted()->create();

    $response = $this->get(route('invitation.accept', ['token' => $invitation->token]));

    $response->assertRedirect();
});

it('returns 404 for invalid token', function () {
    $response = $this->get(route('invitation.accept', ['token' => 'invalid-token']));

    $response->assertNotFound();
});
