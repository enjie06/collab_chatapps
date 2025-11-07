@extends('layouts.app')

@section('content')
<style>
.chat-list {
    max-width: 600px;
    margin: 30px auto;
}

.chat-item {
    padding: 12px 16px;
    margin-bottom: 10px;
    background: #F8F6FF;
    border-radius: 8px;
    border: 1px solid #ddd;
    cursor: pointer;
}
.chat-item:hover {
    background: #EEDCFF;
}
</style>

<h2 style="text-align:center; margin-top: 20px;">Percakapan Anda</h2>

<div class="chat-list">
    @forelse($conversations as $conversation)
        <a href="{{ route('chat.show', $conversation->id) }}" style="text-decoration:none; color:inherit;">
            <div class="chat-item">
                <strong>{{ $conversation->title ?? 'Chat Tanpa Judul' }}</strong><br>
                <small>{{ $conversation->users->pluck('name')->join(', ') }}</small>
            </div>
        </a>
    @empty
        <p style="text-align:center; margin-top:20px;">Belum ada percakapan 😿</p>
    @endforelse
</div>

@endsection
