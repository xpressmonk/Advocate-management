@extends('layouts.app')

@section('heading', 'Users & roles')

@section('content')
    <div class="max-w-6xl">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Users & roles</h1>
            <a class="btn border border-slate-300 bg-white" href="{{ route('roles.index') }}">Manage roles</a>
        </div>

        <div class="card mb-5">
            <h2 class="mb-3 font-bold">Create user</h2>
            <form method="POST" action="{{ route('users.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input class="field" name="name" placeholder="Full name" maxlength="255" required>
                <input class="field" type="email" name="email" placeholder="Email" maxlength="255" required>
                <input class="field" type="password" name="password" placeholder="Password (minimum 8)" minlength="8" required>
                <select class="field" name="role_id" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <button class="btn primary sm:col-span-2 lg:col-span-4" type="submit">Create user</button>
            </form>
        </div>

        <div class="card overflow-x-auto">
            <table class="table w-full text-sm">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Password</th><th>Save</th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <form method="POST" action="{{ route('users.update', $user) }}">
                                @csrf @method('PUT')
                                <td><input class="field min-w-40" name="name" value="{{ $user->name }}" required></td>
                                <td><input class="field min-w-52" type="email" name="email" value="{{ $user->email }}" required></td>
                                <td>
                                    <select class="field min-w-32" name="role" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="field min-w-40" type="password" name="password" placeholder="Leave unchanged"></td>
                                <td class="whitespace-nowrap">
                                    <button class="text-teal-700" type="submit">Save</button>
                            </form>
                            @if(auth()->id() !== $user->id)
                                <form class="inline" method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600" type="submit">Delete</button>
                                </form>
                            @endif
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
