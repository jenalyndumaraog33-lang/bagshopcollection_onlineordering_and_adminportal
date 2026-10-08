<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Management</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f4f4f4; }
            table { width: 100%; border-collapse: collapse; background: white; }
            th, td { padding: 0.8rem; border-bottom: 1px solid #ddd; text-align: left; }
            .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
            .btn { display: inline-block; padding: 0.6rem 1rem; background: #1f2937; color: white; border-radius: 6px; text-decoration: none; }
            .btn-secondary { background: #4b5563; }
            .btn-danger { background: #b91c1c; }
            .alert { padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 6px; }
            .alert-success { background: #dcfce7; color: #166534; }
            .alert-error { background: #fee2e2; color: #991b1b; }
        </style>
    </head>
    <body>
        <div class="topbar">
            <h1>User Management</h1>
            <div style="display:flex; gap:0.75rem; align-items:center;">
                <a href="{{ route('admin.users.create') }}" class="btn">Add User</a>
                <form action="{{ route('logout') }}" method="POST" style="display:inline; margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                        <td>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary">Edit</a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>
