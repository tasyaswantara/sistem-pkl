<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Jurusan;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->role;
        $search = $request->search;

        $users = User::with([
            'roles',
            'siswa.jurusan',
            'gurupembimbing.jurusan',
            'industri'
        ])
            ->when($role && $role !== 'Semua Pengguna', function ($q) use ($role) {
                $q->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', strtolower($role));
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            })
            ->paginate(10)
            ->withQueryString();

        return view('admin.data-pengguna', compact('users'));
    }



    public function create()
    {
        $roles = Role::all();
        $jurusan = Jurusan::all();
        return view('admin.users.create', compact('roles', 'jurusan'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'required',
        ];

        switch ($request->role) {
            case 'siswa':
                $rules = array_merge($rules, [
                    'nis' => 'required|string|unique:siswa,nis',
                    'jurusan_id' => 'required|exists:jurusan,id',
                    'kelas' => 'required|string|max:50',
                    'nilai_akademik' => 'required|integer|min:0',
                    'perangkat' => 'required|integer|min:1|max:5',
                    'status_pkl' => 'required|in:belum,berjalan,selesai',
                    'tahun_ajaran' => 'required|string|max:20',
                ]);
                break;

            case 'guru pembimbing':
                $rules = array_merge($rules, [
                    'nip' => 'required|string|unique:guru_pembimbing,nip',
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;

            case 'perwakilan industri':
                $rules = array_merge($rules, [
                    'nama_industri' => 'required|string|max:255',
                    'kapasitas' => 'required|integer|min:1',
                    'alamat' => 'required|string',
                    'reputasi' => 'required|integer|min:0',
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;
        }

        $request->validate($rules);

        // 1. CREATE USER
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // 2. ASSIGN ROLE (SPATIE)
        $user->assignRole($request->role);

        // 3. SIMPAN DATA BERDASARKAN ROLE
        switch ($request->role) {

            case 'siswa':
                $user->siswa()->create([
                    'nis' => $request->nis,
                    'jurusan_id' => $request->jurusan_id,
                    'kelas' => $request->kelas,
                    'nilai_akademik' => $request->nilai_akademik,
                    'perangkat' => $request->perangkat,
                    'status_pkl' => $request->status_pkl,
                    'tahun_ajaran' => $request->tahun_ajaran,
                ]);
                break;

            case 'guru pembimbing':
                $user->gurupembimbing()->create([
                    'nip' => $request->nip,
                    'jurusan_id' => $request->jurusan_id,
                ]);
                break;

            case 'perwakilan industri':
                $user->industri()->create([
                    'nama_industri' => $request->nama_industri,
                    'kapasitas' => $request->kapasitas,
                    'alamat' => $request->alamat,
                    'reputasi' => $request->reputasi,
                    'jurusan_id' => $request->jurusan_id,
                ]);
                break;

            case 'admin':
                // tidak ada tabel turunan
                break;
        }

        return redirect()
            ->route('admin.data-pengguna')
            ->with('success', 'User berhasil ditambahkan');
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
        return view('admin.users.edit', compact('user', 'roles', 'assignedRoles'));
    }

    public function update(Request $request, User $user)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id', // Validate role IDs
        ]);

        // Update user details
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        // Map role IDs to role names
        $roleIds = $request->roles;
        $validRoleNames = Role::whereIn('id', $roleIds)->pluck('name')->toArray();

        // Sync roles by name
        $user->syncRoles($validRoleNames);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
