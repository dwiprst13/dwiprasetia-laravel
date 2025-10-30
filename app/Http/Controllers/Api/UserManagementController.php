<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            if (in_array($role, ['admin', 'user'], true)) {
                $query->where('role', $role);
            }
        }

        $perPage = (int) min(max($request->integer('per_page', 15) ?: 15, 1), 100);

        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        return UserResource::make($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (array_key_exists('role', $data)) {
            if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'Anda tidak dapat menghapus hak admin dari akun yang sedang aktif.',
                ]);
            }

            if ($user->role === 'admin' && $data['role'] === 'user') {
                $remainingAdmins = User::where('role', 'admin')->count();
                if ($remainingAdmins <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'Minimal harus ada satu admin yang aktif.',
                    ]);
                }
            }
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $user->save();

        return UserResource::make($user->fresh());
    }
}
