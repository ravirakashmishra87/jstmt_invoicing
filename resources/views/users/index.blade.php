@extends('layouts.app')
@section('title', 'Users')
@section('page', 'Users')
@section('subtitle', 'Access control')

@section('content')
<div class="list-hero">
    <div class="kicker">Administration</div>
    <h1>Users</h1>
    <p>Manage logins, roles, and active status for the invoicing team.</p>
</div>

<div class="list-toolbar">
    <div></div>
    @if(auth()->user()->hasPermission('users.create'))
        <a class="btn btn-primary" href="{{ route('users.create') }}"><i class="bi bi-plus-lg"></i> New user</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>User list</h2>
        <span class="text-muted small">{{ $users->total() }} record{{ $users->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><div class="title">{{ $user->name }}</div></td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->name ?? '—' }}</td>
                        <td>
                            <span class="status-pill {{ $user->is_active ? 'on' : 'off' }}">
                                <i class="bi bi-{{ $user->is_active ? 'check-circle' : 'slash-circle' }}"></i>
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @php
                                $extra = null;
                                if (auth()->user()->hasPermission('users.edit') && $user->id !== auth()->id()) {
                                    if ($user->is_active) {
                                        $extra = '<form method="POST" action="'.route('users.deactivate', $user).'">'.csrf_field().'<button class="icon-btn danger" type="submit" title="Deactivate" aria-label="Deactivate"><i class="bi bi-person-dash"></i></button></form>';
                                    } else {
                                        $extra = '<form method="POST" action="'.route('users.activate', $user).'">'.csrf_field().'<button class="icon-btn" type="submit" title="Activate" aria-label="Activate"><i class="bi bi-person-check"></i></button></form>';
                                    }
                                }
                            @endphp
                            @include('partials.row-actions', [
                                'viewUrl' => null,
                                'editUrl' => auth()->user()->hasPermission('users.edit') ? route('users.edit', $user) : null,
                                'deleteUrl' => (auth()->user()->hasPermission('users.delete') && $user->id !== auth()->id()) ? route('users.destroy', $user) : null,
                                'deleteConfirm' => 'Delete this user?',
                                'extra' => $extra,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="list-empty"><i class="bi bi-people"></i>No users found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($users->hasPages())
    <div class="mt-3">{{ $users->links() }}</div>
@endif
@endsection
