<?php

use App\Enums\ConversationStatus;
use App\Models\Brand;
use App\Models\DocumentConversation;
use App\Models\User;

it('belongs to a brand', function () {
    $conversation = DocumentConversation::factory()->create();

    expect($conversation->brand)->toBeInstanceOf(Brand::class);
});

it('belongs to a user', function () {
    $conversation = DocumentConversation::factory()->create();

    expect($conversation->user)->toBeInstanceOf(User::class);
});

it('has correct status helpers', function () {
    $pending = DocumentConversation::factory()->create();
    $processing = DocumentConversation::factory()->processing()->create();
    $needsContext = DocumentConversation::factory()->needsContext()->create();
    $completed = DocumentConversation::factory()->completed()->create();
    $failed = DocumentConversation::factory()->failed()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($processing->isProcessing())->toBeTrue()
        ->and($needsContext->needsContext())->toBeTrue()
        ->and($completed->isCompleted())->toBeTrue()
        ->and($failed->isFailed())->toBeTrue();
});

it('casts status to enum', function () {
    $conversation = DocumentConversation::factory()->create();

    expect($conversation->status)->toBeInstanceOf(ConversationStatus::class);
});

it('uses uuid as primary key', function () {
    $conversation = DocumentConversation::factory()->create();

    expect($conversation->id)->toBeString()
        ->and(strlen($conversation->id))->toBe(36);
});

it('cascades delete when brand is deleted', function () {
    $brand = Brand::factory()->create();
    DocumentConversation::factory()->count(3)->create(['brand_id' => $brand->id]);

    expect(DocumentConversation::where('brand_id', $brand->id)->count())->toBe(3);

    $brand->delete();

    expect(DocumentConversation::where('brand_id', $brand->id)->count())->toBe(0);
});

it('brand has document conversations relationship', function () {
    $brand = Brand::factory()->create();
    DocumentConversation::factory()->count(2)->create(['brand_id' => $brand->id]);

    expect($brand->documentConversations)->toHaveCount(2);
});
