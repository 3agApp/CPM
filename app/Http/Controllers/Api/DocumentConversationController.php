<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProvideContextRequest;
use App\Http\Requests\Api\UploadDocumentRequest;
use App\Http\Resources\DocumentConversationResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DocumentConversationController extends Controller
{
    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $file = $request->file('document');
        $storedPath = $file->store('documents');

        $conversation = DocumentConversation::create([
            'supplier_id' => $request->validated('supplier_id'),
            'user_id' => $request->user()->id,
            'status' => ConversationStatus::Pending,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
        ]);

        if ($request->validated('notes')) {
            $conversation->messages()->create([
                'role' => MessageRole::User,
                'content' => $request->validated('notes'),
            ]);
        }

        ProcessDocumentJob::dispatch($conversation);

        return response()->json([
            'data' => new DocumentConversationResource($conversation),
        ], 201);
    }

    public function show(DocumentConversation $conversation): DocumentConversationResource
    {
        return new DocumentConversationResource($conversation->load('messages'));
    }

    public function provideContext(ProvideContextRequest $request, DocumentConversation $conversation): JsonResponse
    {
        if (! $conversation->needsContext() && ! $conversation->isCompleted()) {
            return response()->json([
                'message' => 'Messages can only be sent when the conversation needs context or is completed.',
            ], 422);
        }

        $conversation->messages()->create([
            'role' => MessageRole::User,
            'content' => $request->validated('context'),
        ]);

        $conversation->update([
            'ai_question' => null,
            'status' => ConversationStatus::Pending,
        ]);

        ProcessDocumentJob::dispatch($conversation);

        return response()->json([
            'data' => new DocumentConversationResource($conversation->fresh()->load('messages')),
        ]);
    }

    public function download(DocumentConversation $conversation): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $conversation->isCompleted() || ! $conversation->output_path) {
            return response()->json([
                'message' => 'Output is not available yet.',
            ], 404);
        }

        return Storage::download($conversation->output_path, 'output.csv');
    }
}
