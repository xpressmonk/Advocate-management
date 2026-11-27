@extends('layouts.app')

@section('heading', 'Roles')

@section('content')
    <div class="max-w-6xl">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Roles</h1>
            <a class="btn border border-slate-300 bg-white" href="{{ route('users.index') }}">Manage users</a>
        </div>

        <div class="card mb-5">
            <h2 class="mb-3 font-bold">Create role</h2>
            <form method="POST" action="{{ route('roles.store') }}" class="space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <input class="field" name="name" placeholder="Role name" maxlength="100" required>
                    <input class="field" name="slug" placeholder="slug_name" maxlength="100" pattern="[a-z0-9_-]+" required>
                </div>
                <textarea class="field" name="description" rows="2" maxlength="1000" placeholder="Description"></textarea>
                <div>
                    <p class="mb-2 text-sm font-semibold">Permissions</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($permissionOptions as $permission => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="btn primary" type="submit">Create role</button>
            </form>
        </div>

        <div class="space-y-4">
            @foreach($roles as $role)
                <div class="card">
                    <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-sm">Name<input class="field mt-1" name="name" value="{{ $role->name }}" maxlength="100" required></label>
                            <label class="text-sm">Slug<input class="field mt-1" name="slug" value="{{ $role->slug }}" maxlength="100" pattern="[a-z0-9_-]+" @readonly($role->system) required></label>
                        </div>
                        <label class="block text-sm">Description<textarea class="field mt-1" name="description" rows="2" maxlength="1000">{{ $role->description }}</textarea></label>
                        <div>
                            <p class="mb-2 text-sm font-semibold">Permissions</p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach($permissionOptions as $permission => $label)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $role->permissions ?: [], true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-sm text-slate-600">{{ $role->users_count }} assigned user(s){{ $role->system ? ' · Built-in role' : '' }}</span>
                            <div class="flex gap-3">
                                <button class="btn border border-slate-300 bg-white" type="submit">Save role</button>
                    </form>
                                @unless($role->system)
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                        @csrf @method('DELETE')
                                        <button class="btn text-red-600" type="submit">Delete role</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $roles->links() }}</div>
    </div>
@endsection
