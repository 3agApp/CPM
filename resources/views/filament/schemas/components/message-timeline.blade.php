@php
    $record = $getRecord();
    $messages = $record->messages()->orderBy('created_at')->get();
@endphp

<div class="space-y-3">
    @forelse ($messages as $message)
        <div @class([
            'flex',
            'justify-end' => $message->role === \App\Enums\MessageRole::User,
            'justify-start' => $message->role === \App\Enums\MessageRole::Assistant,
        ])>
            <div @class([
                'max-w-[80%] rounded-lg px-4 py-3',
                'bg-primary-50 dark:bg-primary-950/50' => $message->role === \App\Enums\MessageRole::User,
                'bg-gray-100 dark:bg-white/5' => $message->role === \App\Enums\MessageRole::Assistant,
            ])>
                <div @class([
                    'mb-1 text-xs font-semibold',
                    'text-primary-600 dark:text-primary-400' => $message->role === \App\Enums\MessageRole::User,
                    'text-gray-500 dark:text-gray-400' => $message->role === \App\Enums\MessageRole::Assistant,
                ])>
                    {{ $message->role === \App\Enums\MessageRole::User ? 'You' : 'AI Assistant' }}
                </div>

                <div class="text-sm text-gray-950 dark:text-white whitespace-pre-wrap">{{ $message->content }}</div>

                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ $message->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4">
            No messages yet.
        </div>
    @endforelse
</div>
