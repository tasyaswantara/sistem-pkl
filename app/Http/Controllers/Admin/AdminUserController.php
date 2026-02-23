<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Industri;
use App\Services\AdminUserService;
use App\Models\Jurusan;

class AdminUserController extends Controller
{
    public function index(Request $request, AdminUserService $service)
    {
        $filters = [
            'role' => $request->role,
            'search' => $request->search,
            'jurusan_id' => $request->jurusan_id,
            'kelas' => $request->kelas,
            'grade' => $request->grade,
        ];

        $users = $service->getUsers($filters);
        $jurusanOptions = $service->getJurusanOptions();
        $kelasOptions = $service->getKelasOptions();

        return view('admin.data-pengguna.data-pengguna', compact('users', 'jurusanOptions', 'kelasOptions'));
    }



    public function create(Request $request, AdminUserService $service)
    {
        $roles = Role::all();
        $rawRole = $request->input('role');
        $prefillRole = $service->getPrefillRole($rawRole);
        $jurusan = $service->getJurusanOptions();

        return view('admin.users.create', compact('roles', 'jurusan', 'prefillRole'));
    }

    public function store(Request $request, AdminUserService $service)
    {
        $rules = $service->getStoreRules($request->role);
        $validated = $request->validate($rules);

        $service->createUser($validated);

        return redirect()
            ->route('admin.data-pengguna')
            ->with('success', __('admin_user.success.tambah'));
    }


    public function show(User $user)
    {
        $roles = $user->roles;
        return view('admin.users.show', compact('user', 'roles'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $assignedRoles = $user->roles->pluck('id')->toArray();
        $jurusan = Jurusan::all();
        $roleName = $user->roles->first()->name ?? 'admin';
        return view('admin.users.edit', compact('user', 'roles', 'assignedRoles', 'jurusan', 'roleName'));
    }

    public function update(Request $request, User $user, AdminUserService $service)
    {
        $role = $request->role ?? ($user->roles->first()->name ?? 'admin');
        $rules = $service->getUpdateRules($role, $user);
        $validated = $request->validate($rules);

        $service->updateUser($user, $validated, $role);

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', __('admin_user.success.ubah'));
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', __('admin_user.success.hapus'));
    }

    public function kirimPengajuanIndustri(Industri $industri, AdminUserService $service)
    {
        $status = $service->ensurePengajuanIndustri($industri);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => $status,
                'label' => ucfirst($status),
            ]);
        }

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', __('admin_user.success.pengajuan'));
    }
}
