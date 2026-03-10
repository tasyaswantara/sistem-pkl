<?php

namespace App\Services;

use App\Mail\NewUserCredentialsMail;
use App\Enums\StatusPKL;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AdminUserService
{
    /**
     * @param array{role?:string,search?:string,jurusan_id?:string,kelas?:string,grade?:string} $filters
     */
    public function getUsers(array $filters): LengthAwarePaginator
    {
        $role = $filters['role'] ?? null;
        $search = $filters['search'] ?? null;
        $jurusanId = $filters['jurusan_id'] ?? null;
        $kelas = $filters['kelas'] ?? null;
        $grade = $filters['grade'] ?? null;

        return User::with([
            'roles',
            'siswa.jurusan',
            'gurupembimbing.jurusan',
            'gurupembimbing.penempatanPkl.siswa.user',
            'industri',
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
    }

    public function getJurusanOptions(): Collection
    {
        return Jurusan::orderBy('nama')->get();
    }

    public function getKelasOptions(): Collection
    {
        return Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
    }

    public function getPrefillRole(?string $rawRole): ?string
    {
        if ($rawRole && strtolower($rawRole) !== 'semua pengguna') {
            $normalized = strtolower($rawRole);
            $map = [
                'siswa' => 'siswa',
                'guru pembimbing' => 'guru pembimbing',
                'perwakilan industri' => 'perwakilan industri',
                'admin' => 'admin',
            ];

            return $map[$normalized] ?? null;
        }

        return null;
    }

    public function getStoreRules(string $role): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required',
        ];

        switch ($role) {
            case 'siswa':
                $rules = array_merge($rules, [
                    'nis' => 'required|string|unique:siswa,nis',
                    'jurusan_id' => 'required|exists:jurusan,id',
                    'kelas' => 'required|string|max:50',
                    'nilai_akademik' => 'required|integer|min:0',
                    'perangkat' => 'required|integer|min:1|max:5',
                    'status_pkl' => [
                        'required',
                        Rule::in(array_map(
                            static fn (StatusPKL $status) => $status->value,
                            StatusPKL::cases()
                        )),
                    ],
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
                    'latitude' => 'required|numeric|between:-90,90',
                    'longitude' => 'required|numeric|between:-180,180',
                    'geofence_radius_m' => 'required|integer|min:20|max:5000',
                    'grade' => 'required|in:A,B,C',
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;
        }

        return $rules;
    }

    public function getUpdateRules(string $role, User $user): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
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
                    'status_pkl' => [
                        'required',
                        Rule::in(array_map(
                            static fn (StatusPKL $status) => $status->value,
                            StatusPKL::cases()
                        )),
                    ],
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
                    'latitude' => 'required|numeric|between:-90,90',
                    'longitude' => 'required|numeric|between:-180,180',
                    'geofence_radius_m' => 'required|integer|min:20|max:5000',
                    'grade' => 'required|in:A,B,C',
                    'jurusan_id' => 'required|exists:jurusan,id',
                ]);
                break;
        }

        return $rules;
    }

    public function createUser(array $data): User
    {
        $plainPassword = $data['password'];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($plainPassword),
        ]);

        $user->assignRole($data['role']);

        switch ($data['role']) {
            case 'siswa':
                $user->siswa()->create([
                    'nis' => $data['nis'],
                    'jurusan_id' => $data['jurusan_id'],
                    'kelas' => $data['kelas'],
                    'nilai_akademik' => $data['nilai_akademik'],
                    'perangkat' => $data['perangkat'],
                    'status_pkl' => $data['status_pkl'],
                    'tahun_ajaran' => $data['tahun_ajaran'],
                ]);
                break;

            case 'guru pembimbing':
                $user->gurupembimbing()->create([
                    'nip' => $data['nip'],
                    'jurusan_id' => $data['jurusan_id'],
                ]);
                break;

            case 'perwakilan industri':
                $user->industri()->create([
                    'nama_industri' => $data['nama_industri'],
                    'kapasitas' => $data['kapasitas'],
                    'alamat' => $data['alamat'],
                    'latitude' => round((float) $data['latitude'], 7),
                    'longitude' => round((float) $data['longitude'], 7),
                    'geofence_radius_m' => (int) $data['geofence_radius_m'],
                    'grade' => $data['grade'],
                    'jurusan_id' => $data['jurusan_id'],
                ]);
                break;
        }

        try {
            Mail::to($user->email)->send(
                new NewUserCredentialsMail($user->name, $user->email, $plainPassword)
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email kredensial user baru', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return $user;
    }

    public function updateUser(User $user, array $data, string $role): void
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => !empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        if ($role) {
            $user->syncRoles([$role]);
        }

        switch ($role) {
            case 'siswa':
                $user->siswa()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => $data['nis'],
                        'jurusan_id' => $data['jurusan_id'],
                        'kelas' => $data['kelas'],
                        'nilai_akademik' => $data['nilai_akademik'],
                        'perangkat' => $data['perangkat'],
                        'status_pkl' => $data['status_pkl'],
                        'tahun_ajaran' => $data['tahun_ajaran'],
                    ]
                );
                break;

            case 'guru pembimbing':
                $user->gurupembimbing()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nip' => $data['nip'],
                        'jurusan_id' => $data['jurusan_id'],
                    ]
                );
                break;

            case 'perwakilan industri':
                $user->industri()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_industri' => $data['nama_industri'],
                        'kapasitas' => $data['kapasitas'],
                        'alamat' => $data['alamat'],
                        'latitude' => round((float) $data['latitude'], 7),
                        'longitude' => round((float) $data['longitude'], 7),
                        'geofence_radius_m' => (int) $data['geofence_radius_m'],
                        'grade' => $data['grade'],
                        'jurusan_id' => $data['jurusan_id'],
                    ]
                );
                break;
        }
    }

    public function ensurePengajuanIndustri(Industri $industri): string
    {
        if (!$industri->status_pengajuan) {
            $industri->update([
                'status_pengajuan' => 'menunggu',
                'pengajuan_dikirim_at' => now(),
            ]);
        }

        return $industri->status_pengajuan;
    }
}
