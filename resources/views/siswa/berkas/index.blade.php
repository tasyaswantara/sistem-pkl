@section('title', 'Berkas Siswa')

<x-admin-layout>
    <div x-data="berkasForm()">
        <div class="mb-8 animate-fade-up">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Berkas Siswa</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Berkas Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Simpan tautan Google Drive yang sudah bisa diakses oleh industri.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 animate-fade-up">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 animate-fade-up">
            <div class="font-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('siswa.berkas.update') }}" class="bg-white rounded-lg border border-gray-200 p-6 animate-fade-up" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    @php
                    $bpjsUrl = $siswa->bpjs_link
                        ? (\Illuminate\Support\Str::startsWith($siswa->bpjs_link, ['http://', 'https://'])
                            ? $siswa->bpjs_link
                            : Storage::url($siswa->bpjs_link))
                        : null;
                    @endphp
                    <label class="block text-xs font-medium text-gray-600 mb-1">Upload BPJS (JPG/PNG, max 10MB)</label>
                    <input
                        type="file"
                        name="bpjs_file"
                        accept="image/png,image/jpeg"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                    @if ($bpjsUrl)
                    <a href="{{ $bpjsUrl }}" target="_blank" class="mt-2 inline-flex text-xs text-emerald-700 hover:underline">
                        Lihat BPJS saat ini
                    </a>
                    @endif
                </div>
                <div>
                    @php
                    $kartuUrl = $siswa->kartu_pelajar_link
                        ? (\Illuminate\Support\Str::startsWith($siswa->kartu_pelajar_link, ['http://', 'https://'])
                            ? $siswa->kartu_pelajar_link
                            : Storage::url($siswa->kartu_pelajar_link))
                        : null;
                    @endphp
                    <label class="block text-xs font-medium text-gray-600 mb-1">Upload Kartu Pelajar (JPG/PNG, max 10MB)</label>
                    <input
                        type="file"
                        name="kartu_pelajar_file"
                        accept="image/png,image/jpeg"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                    @if ($kartuUrl)
                    <a href="{{ $kartuUrl }}" target="_blank" class="mt-2 inline-flex text-xs text-emerald-700 hover:underline">
                        Lihat kartu pelajar saat ini
                    </a>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-xs font-medium text-gray-600 mb-2">Link CV</label>
                <input
                    type="url"
                    name="cv_link"
                    value="{{ old('cv_link', $siswa->cv_link) }}"
                    placeholder="https://drive.google.com/..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-medium text-gray-600">Link Portofolio (boleh lebih dari satu)</label>
                    <button type="button"
                        class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        @click="addPortofolio()">
                        Tambah Link
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(link, index) in portofolioLinks" :key="`portofolio-${index}`">
                        <div class="flex items-center gap-2">
                            <input type="url" name="portofolio_links[]"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                                x-model="portofolioLinks[index]"
                                placeholder="https://drive.google.com/...">
                            <button type="button"
                                class="px-2.5 py-2 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50"
                                @click="removePortofolio(index)">
                                Hapus
                            </button>
                        </div>
                    </template>
                    <div x-show="portofolioLinks.length === 0" class="text-xs text-gray-400 italic">
                        Belum ada link portofolio.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                    Simpan Berkas
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>

<script>
    function berkasForm() {
        return {
            portofolioLinks: @js(old('portofolio_links', $siswa->portofolio_links ?? [])),
            addPortofolio() {
                this.portofolioLinks.push('');
            },
            removePortofolio(index) {
                this.portofolioLinks.splice(index, 1);
            },
        };
    }
</script>
