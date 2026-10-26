@php
    $user = $user ?? null;
    $active = old('is_active', $user ? ($user->is_active ? '1' : '0') : '1');
@endphp
<form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if($user) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" required class="form-control" value="{{ old('name', $user?->name) }}"></div>
        <div class="col-md-3"><label class="form-label">Username</label><input name="username" required class="form-control" value="{{ old('username', $user?->username) }}"></div>
        <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $user?->phone) }}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" required class="form-control" value="{{ old('email', $user?->email) }}"></div>
        <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
                <option value="">Select role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->role_id) === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Password {{ $user ? '(leave blank to keep)' : '' }}</label>
            <input type="password" name="password" class="form-control" {{ $user ? '' : 'required' }}>
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-control" {{ $user ? '' : 'required' }}>
        </div>
        <div class="col-12">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(in_array((string) $active, ['1'], true))>
                <label class="form-check-label">Active</label>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Save user</button>
        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancel</a>
    </div>
</form>
