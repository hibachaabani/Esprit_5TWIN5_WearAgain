@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container section narrow">
    <div class="notif-page-tete">
        <h1 class="h2" style="margin:0">Notifications</h1>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.tout-lire') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Tout marquer comme lu</button>
            </form>
        @endif
    </div>

    <div class="notif-page-liste">
        @include('notifications._liste')
    </div>

    {{ $notifications->links('pagination.numbers') }}
</div>
@endsection
