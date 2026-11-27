@extends('layouts.app')

@section('heading', $title)

@section('content')
    <div class="card">
        <h1 class="mb-4 text-2xl font-bold">{{ $title }}</h1>
        <dl class="grid gap-4 md:grid-cols-2">
            @foreach($item->getAttributes() as $key => $value)
                <div>
                    <dt class="text-xs uppercase text-slate-500">{{ str_replace('_', ' ', $key) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                </div>
            @endforeach
        </dl>
        @if($item instanceof \App\Models\Client)
            <a class="btn primary mt-5" href="{{ route('documents.index', ['client_id' => $item->id]) }}">Manage documents</a>
        @endif
    </div>
@endsection
