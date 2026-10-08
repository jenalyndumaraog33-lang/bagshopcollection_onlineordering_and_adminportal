<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit User</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f4f4f4; }
            form { background: white; padding: 1.5rem; max-width: 520px; border-radius: 8px; }
            .field { margin-bottom: 1rem; }
            label { display: block; margin-bottom: 0.4rem; font-weight: bold; }
            input, select { width: 100%; padding: 0.7rem; box-sizing: border-box; }
            .btn { display: inline-block; padding: 0.7rem 1rem; background: #111827; color: white; border:none; border-radius: 6px; cursor: pointer; text-decoration: none; }
            .alert { color: #991b1b; background: #fee2e2; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <h1>Edit User</h1>

        @if ($errors->any())
            <div class="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="field">
                <label for="password">New Password</label>
                <input id="password" type="password" name="password">
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation">
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="customer" {{ old('role', $user->role) === 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator / Staff</option>
                </select>
            </div>

            <button type="submit" class="btn">Update User</button>
            <a href="{{ route('admin.users.index') }}" style="margin-left: 0.75rem;">Back to list</a>
        </form>
    </body>
</html>
