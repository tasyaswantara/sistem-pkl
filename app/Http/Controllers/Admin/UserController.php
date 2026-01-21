<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\Industri;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->role;
        $search = $request->search;
        $jurusanId = $request->jurusan_id;
        $kelas = $request->kelas;
        $grade = $request->grade;

        $users = User::with([
            'roles',
            'siswa.jurusan',
            'gurupembimbing.jurusan',
            'gurupembimbing.penempatanPkl.siswa.user',
            'industri'
        ])
            ->when($role && $role !== 'Semua Pengguna', function ($q) use ($role) {
                $q->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', strtolower($role));
                });
            })
            ->when($jurusanId && $role === 'Siswa', function ($q) use ($jurusanId) {
                $q->whereHas('siswa', function ($sq) use ($jurusanId) {
                    $sq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($jurusanId && $role === 'Perwakilan Industri', function ($q) use ($jurusanId) {
                $q->whereHas('industri', function ($iq) use ($jurusanId) {
                    $iq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($kelas && $role === 'Siswa', function ($q) use ($kelas) {
                $q->whereHas('siswa', function ($sq) use ($kelas) {
                    $sq->where('kelas', $kelas);
                });
            })
            ->when($grade && $role === 'Perwakilan Industri', function ($q) use ($grade) {
                $q->whereHas('industri', function ($iq) use ($grade) {
                    $iq->where('grade', $grade);
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

        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $kelasOptions = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        return view('admin.data-pengguna', compact('users', 'jurusanOptions', 'kelasOptions'));
    }



    public function create(Request $request)
    {
        $roles = Role::all();
        $jurusan = Jurusan::all();
        $rawRole = $request->input('role');
        $prefillRole = null;

        if ($rawRole && strtolower($rawRole) !== 'semua pengguna') {
            $normalized = strtolower($rawRole);
            $map = [
                'siswa' => 'siswa',
                'guru pembimbing' => 'guru pembimbing',
                'perwakilan industri' => 'perwakilan industri',
                'admin' => 'admin',
            ];
            $prefillRole = $map[$normalized] ?? null;
        }

        return view('admin.users.create', compact('roles', 'jurusan', 'prefillRole'));
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
                    'nama_industri' => 'required|string|max:255|unique:industri,nama_industri',
                    'kapasitas' => 'required|integer|min:1',
                    'alamat' => 'required|string',
                    'grade' => 'required|in:A,B,C',
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
                    'grade' => $request->grade,
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
        $jurusan = Jurusan::all();
        $roleName = $user->roles->first()->name ?? 'admin';
        return view('admin.users.edit', compact('user', 'roles', 'assignedRoles', 'jurusan', 'roleName'));
    }

    public function update(Request $request, User $user)
    {
        $role = $request->role ?? ($user->roles->first()->name ?? 'admin');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required',
        ];

        switch ($role) {
            case 'siswa':
                $siswaId = optional($user->siswa)->id;
                $rules = array_merge($rules, [
                    'nis' => [
                        'required',
                        'string',
                        Rule::unique('siswa', 'nis')->ignore($siswaId),
                    ],
                    'jurusan_id' => 'required|exists:jurusan,id',
                    'kelas' => 'required|string|max:50',
                    'nilai_akademik' => 'required|integer|min:0',
                    'perangkat' => 'required|integer|min:1|max:5',
                    'status_pkl' => 'required|in:belum,berjalan,selesai',
                    'tahun_ajaran' => 'required|string|max:20',
                ]);
                break;

            case 'guru pembimbing':
                $guruId = optional($user->gurupembimbing)->id;
                $rules = array_merge($rules, [
                    'nip' => [
                        'required',
                        'string',
                        Rule::unique('guru_pembimbing', 'nip')->ignore($guruId),
                    ],
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;

            case 'perwakilan industri':
                $rules = array_merge($rules, [
                    'nama_industri' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('industri', 'nama_industri')->ignore($user->industri?->id),
                    ],
                    'kapasitas' => 'required|integer|min:1',
                    'alamat' => 'required|string',
                    'grade' => 'required|in:A,B,C',
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;
        }

        $request->validate($rules);

        // Update user details
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        if ($role) {
            $user->syncRoles([$role]);
        }

        switch ($role) {
            case 'siswa':
                $user->siswa()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => $request->nis,
                        'jurusan_id' => $request->jurusan_id,
                        'kelas' => $request->kelas,
                        'nilai_akademik' => $request->nilai_akademik,
                        'perangkat' => $request->perangkat,
                        'status_pkl' => $request->status_pkl,
                        'tahun_ajaran' => $request->tahun_ajaran,
                    ]
                );
                break;

            case 'guru pembimbing':
                $user->gurupembimbing()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nip' => $request->nip,
                        'jurusan_id' => $request->jurusan_id,
                    ]
                );
                break;

            case 'perwakilan industri':
                $user->industri()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_industri' => $request->nama_industri,
                        'kapasitas' => $request->kapasitas,
                        'alamat' => $request->alamat,
                        'grade' => $request->grade,
                        'jurusan_id' => $request->jurusan_id,
                    ]
                );
                break;
        }

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', 'User berhasil diperbarui');
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', 'User deleted successfully.');
    }

    public function kirimPengajuanIndustri(Industri $industri)
    {
        if (!$industri->status_pengajuan) {
            $industri->update([
                'status_pengajuan' => 'menunggu',
                'pengajuan_dikirim_at' => now(),
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json([
                'status' => $industri->status_pengajuan,
                'label' => ucfirst($industri->status_pengajuan),
            ]);
        }

        return redirect()
            ->route('admin.data-pengguna', request()->query())
            ->with('success', 'Pengajuan berhasil dikirim ke industri.');
    }
}
