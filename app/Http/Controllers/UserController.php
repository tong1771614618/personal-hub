<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('id')
            ->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role' => 'required|in:admin,member',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json($user->only('id', 'name', 'email', 'role'), 201);
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => 'required|in:admin,member']);
        // 不允许把自己降级为非 admin（防止无 admin）
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            abort(422, 'Cannot demote yourself');
        }
        $user->update($data);
        return response()->json($user->only('id', 'name', 'email', 'role'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'Cannot delete yourself');
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }
}
