<x-app-layout>
    <x-slot:title>Transparansi Kalkulasi</x-slot:title>

    @php
        $calcSteps = session('calculation_steps', []);
        $sawSteps = $calcSteps['saw'] ?? [];
        $arasSteps = $calcSteps['aras'] ?? [];
        $edasSteps = $calcSteps['edas'] ?? [];
        $ahpSteps = $calcSteps['ahp'] ?? null;

        $criteria = session('spk_criteria', []);

        // Buat lookup nama kriteria dari index
        $criteriaNames = [];
        foreach ($criteria as $c) {
            $criteriaNames[$c['index']] = $c['name'];
        }
    @endphp

    <div class="max-w-6xl mx-auto mt-2 space-y-6 pb-20" x-data="{ openStep: 1 }">

        {{-- Header --}}
        <div class="px-8 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">Transparansi Kalkulasi</h2>
                <p class="text-gray-500 mt-2">Audit jejak matematis untuk setiap metode yang digunakan.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('spk.results') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50 shadow-sm transition">
                    ← Kembali ke Hasil
                </a>
                <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak
                </button>
            </div>
        </div>

        {{-- ============================================================
             LANGKAH 1 — PEMBOBOTAN (AHP / Manual / BWM)
        ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mx-8">
            <button @click="openStep = (openStep === 1 ? 0 : 1)"
                class="w-full p-6 text-left flex justify-between items-center hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Langkah 1</span>
                        <h3 class="text-xl font-bold text-gray-800">Pembobotan Kriteria ({{ strtoupper($method) }})</h3>
                    </div>
                </div>
                <svg class="w-5 h-5 transition-transform" :class="openStep === 1 ? 'rotate-180' : ''" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openStep === 1" x-cloak x-transition class="p-8 border-t border-gray-50 space-y-6">

                @if ($method === 'ahp' && $ahpSteps)
                    {{-- Rumus AHP --}}
                    <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100 text-sm text-indigo-800 space-y-1">
                        <p class="font-bold mb-2">Metode Eigenvector (AHP)</p>
                        <p>1. Jumlahkan setiap kolom matriks perbandingan berpasangan</p>
                        <p>2. Normalisasi: <code class="bg-white px-1 rounded">ā_ij = a_ij / Σa_j</code></p>
                        <p>3. Bobot = rata-rata baris: <code class="bg-white px-1 rounded">w_i = Σā_ij / n</code></p>
                        <p>4. Konsistensi: <code class="bg-white px-1 rounded">CR = CI / RI ≤ 0.1</code></p>
                    </div>

                    {{-- Matriks asli --}}
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Matriks Perbandingan
                            Berpasangan</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Kriteria</th>
                                        @foreach (array_keys($ahpSteps['matrix']) as $col)
                                            <th class="p-3">{{ $col }}</th>
                                        @endforeach
                                        <th class="p-3 bg-indigo-50 text-indigo-700">Jumlah Kolom</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($ahpSteps['matrix'] as $rowName => $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $rowName }}</td>
                                            @foreach ($row as $val)
                                                <td class="p-3 font-mono text-xs">{{ $val }}</td>
                                            @endforeach
                                            <td class="p-3 font-mono text-xs font-bold text-indigo-600 bg-indigo-50">
                                                {{ round($ahpSteps['column_sums'][$rowName] ?? 0, 4) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Matriks ternormalisasi + bobot --}}
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Matriks Ternormalisasi
                            & Eigenvector</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Kriteria</th>
                                        @foreach (array_keys($ahpSteps['normalized_matrix']) as $col)
                                            <th class="p-3">{{ $col }}</th>
                                        @endforeach
                                        <th class="p-3 bg-indigo-50 text-indigo-700">Bobot (w)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($ahpSteps['normalized_matrix'] as $rowName => $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $rowName }}</td>
                                            @foreach ($row as $val)
                                                <td class="p-3 font-mono text-xs text-gray-500">{{ $val }}</td>
                                            @endforeach
                                            <td class="p-3 font-mono text-xs font-bold text-indigo-700 bg-indigo-50">
                                                {{ $ahpSteps['weights'][$rowName] ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Consistency check --}}
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Uji Konsistensi</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-4">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Kriteria</th>
                                        <th class="p-3">Bobot (w)</th>
                                        <th class="p-3">Weighted Sum</th>
                                        <th class="p-3">λ_i = WS / w</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach (array_keys($ahpSteps['weights']) as $name)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $name }}</td>
                                            <td class="p-3 font-mono text-xs">{{ $ahpSteps['weights'][$name] }}</td>
                                            <td class="p-3 font-mono text-xs">
                                                {{ $ahpSteps['weighted_sum'][$name] ?? '-' }}</td>
                                            <td class="p-3 font-mono text-xs">
                                                {{ $ahpSteps['lambda_values'][$name] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <div class="p-3 bg-white border border-gray-200 rounded-xl">
                                <span class="block text-xs text-gray-400 mb-1">λ max</span>
                                <span
                                    class="font-bold font-mono text-gray-800">{{ $ahpSteps['lambda_max'] ?? '-' }}</span>
                            </div>
                            <div class="p-3 bg-white border border-gray-200 rounded-xl">
                                <span class="block text-xs text-gray-400 mb-1">CI</span>
                                <span class="font-bold font-mono text-gray-800">{{ $ahpSteps['ci'] ?? '-' }}</span>
                            </div>
                            <div class="p-3 bg-white border border-gray-200 rounded-xl">
                                <span class="block text-xs text-gray-400 mb-1">RI
                                    (n={{ $ahpSteps['n'] ?? '?' }})</span>
                                <span class="font-bold font-mono text-gray-800">{{ $ahpSteps['ri'] ?? '-' }}</span>
                            </div>
                            <div
                                class="p-3 rounded-xl border font-bold
                                {{ $ahpSteps['consistent'] ?? false ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' }}">
                                <span class="block text-xs mb-1">CR</span>
                                <span class="font-mono">{{ $ahpSteps['cr'] ?? '-' }}</span>
                                <span
                                    class="block text-xs mt-1">{{ $ahpSteps['consistent'] ?? false ? '✓ Konsisten' : '✗ Tidak Konsisten' }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Manual / BWM: tampilkan bobot saja --}}
                    <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100 text-sm text-indigo-700">
                        Bobot diinput secara <strong>{{ strtoupper($method) }}</strong>. Total bobot harus = 1.
                    </div>
                @endif

                {{-- Tabel bobot final --}}
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Bobot Final Kriteria</p>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-4 text-left">Kriteria</th>
                                    <th class="p-4 text-center">Tipe</th>
                                    <th class="p-4 text-center">Bobot (w_j)</th>
                                    <th class="p-4 text-center">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($weights as $name => $val)
                                    @php $type = collect($criteria)->firstWhere('name', $name)['type'] ?? '-'; @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 font-semibold text-gray-700">{{ $name }}</td>
                                        <td class="p-4 text-center">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs font-bold
                                                {{ $type === 'benefit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ strtoupper($type) }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-center font-mono font-bold text-indigo-700">
                                            {{ number_format($val, 4) }}</td>
                                        <td class="p-4 text-center font-mono text-gray-500">
                                            {{ number_format($val * 100, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             LANGKAH 2 — SAW
        ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mx-8">
            <button @click="openStep = (openStep === 2 ? 0 : 2)"
                class="w-full p-6 text-left flex justify-between items-center hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-blue-500 uppercase tracking-widest">Langkah 2</span>
                        <h3 class="text-xl font-bold text-gray-800">SAW — Simple Additive Weighting</h3>
                    </div>
                </div>
                <svg class="w-5 h-5 transition-transform" :class="openStep === 2 ? 'rotate-180' : ''" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openStep === 2" x-cloak x-transition class="p-8 border-t border-gray-50 space-y-6">

                <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 text-sm text-blue-800 space-y-1">
                    <p class="font-bold mb-1">Rumus Normalisasi</p>
                    <p>Benefit: <code class="bg-white px-1 rounded">r_ij = x_ij / max(x_j)</code></p>
                    <p>Cost: <code class="bg-white px-1 rounded">r_ij = min(x_j) / x_ij</code></p>
                    <p class="mt-1">Skor akhir: <code class="bg-white px-1 rounded">V_i = Σ (w_j × r_ij)</code></p>
                </div>

                {{-- Data asli + max/min --}}
                @if (!empty($sawSteps['original']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Data Asli & Nilai
                            Referensi</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        @foreach ($sawSteps['criteria'] as $idx)
                                            <th class="p-3">
                                                {{ $criteriaNames[$idx] ?? 'K-' . $idx }}
                                                <span class="block font-normal text-gray-400">
                                                    {{ ($sawSteps['mapping'][$idx] ?? '') === 'benefit' ? '↑ Benefit' : '↓ Cost' }}
                                                </span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($sawSteps['original'] as $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $row['name'] }}</td>
                                            @foreach ($sawSteps['criteria'] as $idx)
                                                <td class="p-3 font-mono">{{ $row['values'][$idx] ?? '-' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr class="bg-green-50 font-bold text-green-700 border-t-2 border-green-100">
                                        <td class="p-3 text-left text-xs">Max</td>
                                        @foreach ($sawSteps['criteria'] as $idx)
                                            <td class="p-3 font-mono text-xs">{{ $sawSteps['max_val'][$idx] ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                    <tr class="bg-red-50 font-bold text-red-600">
                                        <td class="p-3 text-left text-xs">Min</td>
                                        @foreach ($sawSteps['criteria'] as $idx)
                                            <td class="p-3 font-mono text-xs">{{ $sawSteps['min_val'][$idx] ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Matriks normalisasi --}}
                @if (!empty($sawSteps['norm_matrix']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Matriks Normalisasi
                            (r_ij)</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        @foreach ($sawSteps['criteria'] as $idx)
                                            <th class="p-3">{{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($sawSteps['norm_matrix'] as $k => $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $sawSteps['original'][$k]['name'] ?? 'Alt-' . $loop->iteration }}
                                            </td>
                                            @foreach ($sawSteps['criteria'] as $idx)
                                                <td class="p-3 font-mono text-blue-700">
                                                    {{ number_format((float) ($row[$idx] ?? 0), 4) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Hasil akhir SAW --}}
                @if (!empty($sawSteps['results']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Skor Akhir & Ranking
                            SAW</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        @foreach ($sawSteps['criteria'] as $idx)
                                            <th class="p-3 text-xs">w·r {{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                        @endforeach
                                        <th class="p-3 bg-blue-50 text-blue-700">V_i (Skor)</th>
                                        <th class="p-3">Rank</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($sawSteps['results'] as $row)
                                        <tr
                                            class="hover:bg-gray-50 {{ ($row['rank'] ?? 99) <= 3 ? 'bg-amber-50/50' : '' }}">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $row['name'] }}</td>
                                            @foreach ($sawSteps['criteria'] as $idx)
                                                <td class="p-3 font-mono text-xs text-gray-500">
                                                    {{ number_format($row['weighted'][$idx] ?? 0, 4) }}
                                                </td>
                                            @endforeach
                                            <td class="p-3 font-mono font-bold text-blue-700 bg-blue-50">
                                                {{ number_format($row['score'] ?? 0, 4) }}
                                            </td>
                                            <td class="p-3 font-black text-gray-700">#{{ $row['rank'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================
             LANGKAH 3 — ARAS
        ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mx-8">
            <button @click="openStep = (openStep === 3 ? 0 : 3)"
                class="w-full p-6 text-left flex justify-between items-center hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-amber-500 uppercase tracking-widest">Langkah 3</span>
                        <h3 class="text-xl font-bold text-gray-800">ARAS — Additive Ratio Assessment</h3>
                    </div>
                </div>
                <svg class="w-5 h-5 transition-transform" :class="openStep === 3 ? 'rotate-180' : ''" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openStep === 3" x-cloak x-transition class="p-8 border-t border-gray-50 space-y-6">

                {{-- Rumus --}}
                <div class="p-4 bg-amber-50 rounded-xl border border-amber-100 text-sm text-amber-800 space-y-1">
                    <p class="font-bold mb-1">Rumus ARAS</p>
                    <p>1. Tentukan A0: Benefit → max, Cost → min</p>
                    <p>2. Transformasi Cost: <code class="bg-white px-1 rounded">1/x_ij</code></p>
                    <p>3. Normalisasi: <code class="bg-white px-1 rounded">x̄_ij = x_ij / Σx_j</code></p>
                    <p>4. Weighted: <code class="bg-white px-1 rounded">S_i = Σ(w_j × x̄_ij)</code></p>
                    <p>5. Utilitas: <code class="bg-white px-1 rounded">K_i = S_i / S_0</code></p>
                </div>

                {{-- Tabel 1: A0 + Data Asli --}}
                @if (!empty($arasSteps['all_rows']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                            Nilai Optimum A0 & Data Asli
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        @foreach ($arasSteps['criteria'] as $idx)
                                            <th class="p-3">{{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($arasSteps['all_rows'] as $row)
                                        <tr
                                            class="hover:bg-gray-50 {{ $row['name'] === 'A0 (Optimal)' ? 'bg-amber-50' : '' }}">
                                            <td
                                                class="p-3 text-left font-semibold
                                            {{ $row['name'] === 'A0 (Optimal)' ? 'text-amber-700' : 'text-gray-700' }}">
                                                {{ $row['name'] }}
                                            </td>
                                            @foreach ($arasSteps['criteria'] as $idx)
                                                <td
                                                    class="p-3 font-mono {{ $row['name'] === 'A0 (Optimal)' ? 'text-amber-700 font-bold' : 'text-gray-600' }}">
                                                    {{ $row['values'][$idx] ?? '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    {{-- Baris Σ kolom (sum_col = setelah transformasi) --}}
                                    <tr class="bg-gray-50 border-t-2 border-gray-200 text-xs font-bold text-gray-500">
                                        <td class="p-3 text-left">Σ Kolom (transformed)</td>
                                        @foreach ($arasSteps['criteria'] as $idx)
                                            <td class="p-3 font-mono">
                                                {{ round($arasSteps['sum_col'][$idx] ?? 0, 4) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Tabel 2: Matriks Ternormalisasi --}}
                {{-- ✅ FIX: loop langsung dari norm_matrix (key = nama string) --}}
                @if (!empty($arasSteps['norm_matrix']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                            Matriks Ternormalisasi (x̄_ij)
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        @foreach ($arasSteps['criteria'] as $idx)
                                            <th class="p-3">{{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($arasSteps['norm_matrix'] as $name => $row)
                                        <tr
                                            class="hover:bg-gray-50 {{ $name === 'A0 (Optimal)' ? 'bg-amber-50' : '' }}">
                                            <td
                                                class="p-3 text-left font-semibold
                                            {{ $name === 'A0 (Optimal)' ? 'text-amber-700' : 'text-gray-700' }}">
                                                {{ $name }}
                                            </td>
                                            @foreach ($arasSteps['criteria'] as $idx)
                                                {{-- ✅ Akses $row[$idx] langsung, bukan via $arasSteps['norm_matrix'][$k][$idx] --}}
                                                <td class="p-3 font-mono text-amber-700">
                                                    {{ number_format($row[$idx] ?? 0, 4) }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Tabel 3: S_i & K_i --}}
                @if (!empty($arasSteps['si_values']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                            Skor S_i & Degree of Utility K_i
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        <th class="p-3">S_i</th>
                                        <th class="p-3 bg-amber-50 text-amber-700">K_i = S_i / S_0</th>
                                        <th class="p-3">Rank</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @php $s0 = $arasSteps['si_values']['A0 (Optimal)'] ?? 1; @endphp

                                    {{-- Baris A0 dulu --}}
                                    <tr class="bg-amber-50 font-bold">
                                        <td class="p-3 text-left text-amber-700 bg-amber-100">A0 (Optimal)</td>
                                        <td class="p-3 font-mono text-xs text-amber-700">
                                            {{ number_format($s0, 6) }}
                                        </td>
                                        <td class="p-3 font-mono font-bold text-amber-700 bg-amber-50">1.000000</td>
                                        <td class="p-3 text-amber-500">—</td>
                                    </tr>

                                    {{-- Alternatif dari results (sudah terurut) --}}
                                    @foreach ($arasSteps['results'] as $row)
                                        <tr
                                            class="hover:bg-gray-50 {{ ($row['rank'] ?? 99) <= 3 ? 'bg-amber-50/40' : '' }}">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $row['name'] }}
                                            </td>
                                            <td class="p-3 font-mono text-xs">
                                                {{ number_format($row['si'] ?? 0, 6) }}
                                            </td>
                                            <td class="p-3 font-mono font-bold text-amber-700 bg-amber-50">
                                                {{ number_format($row['score'] ?? 0, 6) }}
                                            </td>
                                            <td class="p-3 font-black text-gray-700">#{{ $row['rank'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="mt-3 inline-flex items-center gap-3 px-4 py-2 bg-amber-50 rounded-xl border border-amber-100 text-sm">
                            <span class="font-semibold text-amber-700">S₀ (A0 Optimal) =</span>
                            <span class="font-mono font-bold text-amber-900">{{ number_format($s0, 6) }}</span>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- ============================================================
             LANGKAH 4 — EDAS
        ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mx-8">
            <button @click="openStep = (openStep === 4 ? 0 : 4)"
                class="w-full p-6 text-left flex justify-between items-center hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 text-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-purple-500 uppercase tracking-widest">Langkah 4</span>
                        <h3 class="text-xl font-bold text-gray-800">EDAS — Evaluation based on Distance from Average
                        </h3>
                    </div>
                </div>
                <svg class="w-5 h-5 transition-transform" :class="openStep === 4 ? 'rotate-180' : ''" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openStep === 4" x-cloak x-transition class="p-8 border-t border-gray-50 space-y-6">

                <div class="p-4 bg-purple-50 rounded-xl border border-purple-100 text-sm text-purple-800 space-y-1">
                    <p class="font-bold mb-1">Rumus EDAS</p>
                    <p>1. AV_j = Σx_ij / n</p>
                    <p>2. PDA benefit: <code class="bg-white px-1 rounded">max(0, x_ij − AV_j) / AV_j</code></p>
                    <p>3. NDA benefit: <code class="bg-white px-1 rounded">max(0, AV_j − x_ij) / AV_j</code></p>
                    <p>4. SP = Σ(w × PDA), SN = Σ(w × NDA)</p>
                    <p>5. NSP = SP/max(SP), NSN = 1 − SN/max(SN)</p>
                    <p>6. AS = 0.5 × (NSP + NSN)</p>
                </div>

                {{-- Average Solution --}}
                @if (!empty($edasSteps['average_solution']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Average Solution
                            (AV_j)</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Kriteria</th>
                                        <th class="p-3">Tipe</th>
                                        <th class="p-3 bg-purple-50 text-purple-700">AV_j</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($edasSteps['criteria'] as $idx)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $criteriaNames[$idx] ?? 'K-' . $idx }}
                                            </td>
                                            <td class="p-3">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-xs font-bold
                                            {{ ($edasSteps['mapping'][$idx] ?? '') === 'benefit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    {{ strtoupper($edasSteps['mapping'][$idx] ?? '-') }}
                                                </span>
                                            </td>
                                            <td class="p-3 font-mono font-bold text-purple-700 bg-purple-50">
                                                {{ number_format($edasSteps['average_solution'][$idx] ?? 0, 4) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- PDA & NDA matrix side by side --}}
                @if (!empty($edasSteps['pda_matrix']))
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                        {{-- PDA --}}
                        <div>
                            <p class="text-xs font-bold text-green-600 uppercase tracking-widest mb-2">
                                PDA Matrix (Positive Distance)
                            </p>
                            <p class="text-xs text-gray-400 mb-2">Seberapa jauh alternatif melampaui rata-rata</p>
                            <div class="overflow-x-auto rounded-xl border border-green-100 bg-white">
                                <table class="w-full text-xs text-center">
                                    <thead class="bg-green-50">
                                        <tr>
                                            <th class="p-2 text-left">Alternatif</th>
                                            @foreach ($edasSteps['criteria'] as $idx)
                                                <th class="p-2">{{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($edasSteps['pda_matrix'] as $name => $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="p-2 text-left font-semibold text-gray-700 bg-gray-50">
                                                    {{ $name }}</td>
                                                @foreach ($edasSteps['criteria'] as $idx)
                                                    <td class="p-2 font-mono text-green-700">
                                                        {{ number_format($row[$idx] ?? 0, 4) }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- NDA --}}
                        <div>
                            <p class="text-xs font-bold text-red-500 uppercase tracking-widest mb-2">
                                NDA Matrix (Negative Distance)
                            </p>
                            <p class="text-xs text-gray-400 mb-2">Seberapa jauh alternatif di bawah rata-rata</p>
                            <div class="overflow-x-auto rounded-xl border border-red-100 bg-white">
                                <table class="w-full text-xs text-center">
                                    <thead class="bg-red-50">
                                        <tr>
                                            <th class="p-2 text-left">Alternatif</th>
                                            @foreach ($edasSteps['criteria'] as $idx)
                                                <th class="p-2">{{ $criteriaNames[$idx] ?? 'K-' . $idx }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($edasSteps['nda_matrix'] as $name => $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="p-2 text-left font-semibold text-gray-700 bg-gray-50">
                                                    {{ $name }}</td>
                                                @foreach ($edasSteps['criteria'] as $idx)
                                                    <td class="p-2 font-mono text-red-600">
                                                        {{ number_format($row[$idx] ?? 0, 4) }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- SP & SN --}}
                @if (!empty($edasSteps['weighted_sums']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Weighted Sum SP & SN
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        <th class="p-3 text-green-700">SP_i</th>
                                        <th class="p-3 text-red-600">SN_i</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($edasSteps['weighted_sums']['sp'] as $name => $sp)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $name }}</td>
                                            <td class="p-3 font-mono font-bold text-green-700">
                                                {{ number_format($sp, 6) }}</td>
                                            <td class="p-3 font-mono font-bold text-red-600">
                                                {{ number_format($edasSteps['weighted_sums']['sn'][$name] ?? 0, 6) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50 border-t-2 text-xs font-bold text-gray-500">
                                        <td class="p-3 text-left">Max</td>
                                        <td class="p-3 text-green-700">
                                            {{ number_format(max($edasSteps['weighted_sums']['sp']), 6) }}</td>
                                        <td class="p-3 text-red-600">
                                            {{ number_format(max($edasSteps['weighted_sums']['sn']), 6) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- NSP, NSN, AS --}}
                @if (!empty($edasSteps['normalized_sums']))
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">NSP, NSN & Appraisal
                            Score</p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left">Alternatif</th>
                                        <th class="p-3 text-green-700">NSP_i</th>
                                        <th class="p-3 text-blue-600">NSN_i</th>
                                        <th class="p-3 bg-purple-50 text-purple-700">AS = 0.5(NSP+NSN)</th>
                                        <th class="p-3">Rank</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($edasSteps['results'] ?? [] as $row)
                                        <tr
                                            class="hover:bg-gray-50 {{ ($row['rank'] ?? 99) <= 3 ? 'bg-amber-50/50' : '' }}">
                                            <td class="p-3 text-left font-semibold text-gray-700 bg-gray-50">
                                                {{ $row['name'] }}</td>
                                            <td class="p-3 font-mono text-green-700">
                                                {{ number_format($row['nsp'] ?? 0, 4) }}</td>
                                            <td class="p-3 font-mono text-blue-600">
                                                {{ number_format($row['nsn'] ?? 0, 4) }}</td>
                                            <td class="p-3 font-mono font-bold text-purple-700 bg-purple-50">
                                                {{ number_format($row['score'] ?? 0, 4) }}
                                            </td>
                                            <td class="p-3 font-black text-gray-700">#{{ $row['rank'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    @push('scripts')
        <style>
            @media print {

                nav,
                button,
                a[href] {
                    display: none !important;
                }

                [x-cloak] {
                    display: block !important;
                }

                .mx-8 {
                    margin: 0 !important;
                }

                * {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    @endpush
</x-app-layout>
