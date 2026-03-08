<?php

use App\Enums\ConversationStatus;
use App\Models\DocumentConversation;
use App\Models\Supplier;
use App\Models\User;

it('belongs to a supplier', function () {
    $conversation = DocumentConversation::factory()->create();

    expect($conversation->supplier)->toBeInstanceOf(Supplier::class);
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

it('cascades delete when supplier is deleted', function () {
    $supplier = Supplier::factory()->create();
    DocumentConversation::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    expect(DocumentConversation::where('supplier_id', $supplier->id)->count())->toBe(3);

    $supplier->delete();

    expect(DocumentConversation::where('supplier_id', $supplier->id)->count())->toBe(0);
});

it('supplier has document conversations relationship', function () {
    $supplier = Supplier::factory()->create();
    DocumentConversation::factory()->count(2)->create(['supplier_id' => $supplier->id]);

    expect($supplier->documentConversations)->toHaveCount(2);
});
