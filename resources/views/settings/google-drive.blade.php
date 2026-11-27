@extends('layouts.app')
@section('heading', 'Google Drive settings')
@section('content')
<div class="max-w-3xl">
    <section class="card">
        <h1 class="text-2xl font-bold">Google Drive integration</h1>
        <p class="mt-2 text-sm text-slate-600">Connect the firm's central Google account. Case folders and case documents will be created in this account.</p>
        @if($connection && $connection->root_folder_id)
            <p class="mt-5 rounded bg-emerald-50 p-3 text-emerald-800">Google Drive is connected.</p>
            <a class="mt-4 inline-flex text-teal-700 underline" target="_blank" rel="noopener noreferrer"
               href="https://drive.google.com/drive/folders/{{ $connection->root_folder_id }}">Open Advocate Management root folder</a>
        @else
            <p class="mt-5 rounded bg-amber-50 p-3 text-amber-800">Google Drive is not connected yet.</p>
        @endif
        <a class="btn primary mt-5" href="{{ route('settings.google-drive.connect') }}">
            {{ $connection ? 'Reconnect Google Drive' : 'Connect central Google account' }}
        </a>
    </section>
    <section class="card mt-5">
        <h2 class="font-bold">Setup checklist</h2>
        <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm text-slate-600">
            <li>Enable Google Drive API in Google Cloud Console.</li>
            <li>Create an OAuth Web application credential.</li>
            <li>Register the exact callback URL shown in your .env file.</li>
            <li>Place the downloaded OAuth JSON at <code>storage/app/private/google/oauth-client.json</code>.</li>
            <li>Keep this account's refresh token and APP_KEY backed up securely.</li>
        </ol>
    </section>
</div>
@endsection
