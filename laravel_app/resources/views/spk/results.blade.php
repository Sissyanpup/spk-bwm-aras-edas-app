<x-app-layout>
    <x-slot:title>Hasil Perbandingan Metode</x-slot:title>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 mx-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Hasil Perbandingan Metode</h1>
            <p class="text-sm text-gray-500 mt-1">Menampilkan 10 teratas · Export untuk data lengkap</p>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- Tombol baru: Lihat Detail Perhitungan --}}
            <a href="{{ route('spk.calculation') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Lihat Detail Perhitungan
            </a>
            {{-- Tombol export --}}
            <form action="{{ route('spk.export') }}" method="POST">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-200 transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    Export Full Data (.xlsx)
                </button>
            </form>
        </div>
    </div>

    {{-- Statistik Pembobotan (Validasi AHP) --}}
    @if (isset($consistency) && isset($method) && $method == 'ahp')
        <div class="mx-8 mb-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="flex-shrink-0">
                        <div
                            class="w-16 h-16 rounded-full flex items-center justify-center {{ $consistency['is_consistent'] ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if ($consistency['is_consistent'])
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                @endif
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <h3 class="text-lg font-bold text-gray-900">Validasi Konsistensi AHP</h3>
                        <p
                            class="text-sm {{ $consistency['is_consistent'] ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                            {{ $consistency['is_consistent'] ? 'Matriks dinyatakan KONSISTEN' : 'Matriks dinyatakan TIDAK KONSISTEN' }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 uppercase font-bold">Lambda Max</span>
                            <span
                                class="text-lg font-mono font-bold text-gray-700">{{ number_format($consistency['lambda_max'], 4) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 uppercase font-bold">CI</span>
                            <span
                                class="text-lg font-mono font-bold text-gray-700">{{ number_format($consistency['ci'], 4) }}</span>
                        </div>
                        <div class="text-center col-span-2 md:col-span-1 border-t md:border-t-0 pt-2 md:pt-0">
                            <span class="block text-xs text-gray-400 uppercase font-bold">CR</span>
                            <span
                                class="text-xl font-mono font-black {{ $consistency['is_consistent'] ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ number_format($consistency['cr'], 4) }}
                            </span>
                            <span class="text-[10px] text-gray-400 block">Batas: < 0.1</span>
                        </div>
                    </div>
                </div>

                {{-- Alert jika tidak konsisten --}}
                @if (!$consistency['is_consistent'])
                    <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-xs text-red-700 leading-relaxed italic">
                            <strong>Saran:</strong> Nilai CR melebihi 10%. Mohon tinjau kembali matriks perbandingan
                            kriteria Anda untuk memastikan penilaian yang lebih logis dan transitif.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Tabel Perbandingan 3 Metode --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mx-8">
        @foreach (['SAW', 'ARAS', 'EDAS'] as $currentMethodName)
            @php
                $key = strtolower($currentMethodName);
                $allRows = $results[$key] ?? [];
                $preview = array_slice($allRows, 0, 10);
                $total = count($allRows);

                $config = [
                    'SAW' => ['header' => 'bg-blue-600', 'badge' => 'bg-blue-100 text-blue-700'],
                    'ARAS' => ['header' => 'bg-purple-600', 'badge' => 'bg-purple-100 text-purple-700'],
                    'EDAS' => ['header' => 'bg-indigo-600', 'badge' => 'bg-indigo-100 text-indigo-700'],
                ][$currentMethodName];
            @endphp

            <div
                class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 flex flex-col transition-all hover:shadow-lg">
                <div class="{{ $config['header'] }} px-5 py-4 flex items-center justify-between">
                    <span class="text-white font-extrabold tracking-wide text-base">{{ $currentMethodName }}</span>
                    <span class="text-white/70 text-xs font-medium">{{ $total }} alternatif</span>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400">
                                <th class="px-4 py-3 text-left w-12">Rank</th>
                                <th class="px-4 py-3 text-left">Alternatif</th>
                                <th class="px-4 py-3 text-right">Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($preview as $index => $res)
                                @php
                                    // Ambil nama
                                    $name = $res['name'] ?? 'Tanpa Nama';

                                    // Logika "Smart Score": Ambil dari key 'score',
                                    // jika tidak ada cari di key lain yang umum (si, ki, as, nilai)
                                    $score =
                                        $res['score'] ??
                                        ($res['si'] ?? ($res['ki'] ?? ($res['as'] ?? ($res['nilai'] ?? 0))));
                                @endphp
                                <tr
                                    class="hover:bg-gray-50 transition-colors {{ $loop->index < 3 ? 'bg-amber-50/40' : '' }}">
                                    <td class="px-4 py-3">
                                        @if ($loop->index === 0)
                                            <span
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-400 text-white text-[10px] font-black shadow-sm">1</span>
                                        @elseif ($loop->index === 1)
                                            <span
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-300 text-gray-700 text-[10px] font-black shadow-sm">2</span>
                                        @elseif ($loop->index === 2)
                                            <span
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-orange-300 text-white text-[10px] font-black shadow-sm">3</span>
                                        @else
                                            <span
                                                class="text-gray-400 font-semibold pl-1 text-xs">#{{ $loop->iteration }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium truncate max-w-[140px]"
                                        title="{{ $name }}">
                                        {{ $name }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded-lg {{ $config['badge'] }} font-mono text-xs font-bold">
                                            {{ number_format((float) $score, 4) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400 text-xs italic">Tidak
                                        ada data hasil kalkulasi</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-[10px] text-gray-400">
                        {{ $total > 10 ? '+ ' . ($total - 10) . ' lainnya tidak ditampilkan' : 'Menampilkan seluruh data' }}
                    </span>
                    <form action="{{ route('spk.export') }}" method="POST">
                        @csrf
                        <input type="hidden" name="method" value="{{ $key }}">
                        <button type="submit"
                            class="text-xs font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1 transition">
                            Excel <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Keterangan Rank --}}
    <div class="mt-6 flex flex-wrap gap-4 text-[10px] text-gray-400 mx-10 uppercase font-bold tracking-widest">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Rank 1</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300"></span> Rank 2</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-300"></span> Rank 3</span>
    </div>

    {{-- Grafik Perbandingan --}}
    @php
        // 1. Ambil 10 teratas dari SAW dengan proteksi jika data kosong
        $sawTop = array_slice($results['saw'] ?? [], 0, 10);

        // 2. Map Label (Nama) dengan proteksi jika key 'name' tidak ada
        $chartLabels = array_map(function ($r) {
            return $r['name'] ?? ($r['alternatif'] ?? 'Unknown');
        }, $sawTop);

        // 3. Helper untuk membuat Map Skor agar tidak error saat array_column
        // Kita pastikan data target adalah array sebelum diproses
        $arasData = $results['aras'] ?? [];
        $edasData = $results['edas'] ?? [];

        // Gunakan fallback key jika 'score' atau 'name' berbeda di tiap metode
        $arasMap = [];
        foreach ($arasData as $item) {
            $nameKey = $item['name'] ?? ($item['alternatif'] ?? null);
            if ($nameKey) {
                $arasMap[$nameKey] = $item['score'] ?? ($item['ki'] ?? ($item['nilai'] ?? 0));
            }
        }

        $edasMap = [];
        foreach ($edasData as $item) {
            $nameKey = $item['name'] ?? ($item['alternatif'] ?? null);
            if ($nameKey) {
                $edasMap[$nameKey] = $item['score'] ?? ($item['as'] ?? ($item['nilai'] ?? 0));
            }
        }

        // 4. Ambil Skor Akhir untuk Grafik
        $sawScores = array_map(function ($r) {
            return (float) round($r['score'] ?? ($r['nilai'] ?? 0), 4);
        }, $sawTop);

        $arasScores = array_map(function ($name) use ($arasMap) {
            return (float) round($arasMap[$name] ?? 0, 4);
        }, $chartLabels);

        $edasScores = array_map(function ($name) use ($edasMap) {
            return (float) round($edasMap[$name] ?? 0, 4);
        }, $chartLabels);
    @endphp

    <div class="mx-8 mt-10 mb-20">
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-900">Visualisasi Skor Lintas Metode</h2>
                    <p class="text-xs text-gray-400 mt-0.5 italic">Membandingkan konsistensi skor antar metode untuk 10
                        alternatif teratas</p>
                </div>
                <div class="flex gap-2 bg-gray-100 rounded-xl p-1">
                    <button id="btnBar" onclick="switchChart('bar')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white shadow transition">Bar</button>
                    <button id="btnLine" onclick="switchChart('line')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-700 transition">Line</button>
                </div>
            </div>

            <div class="relative" style="height: 400px;">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            const labels = @json($chartLabels);
            const dataSets = {
                saw: @json($sawScores),
                aras: @json($arasScores),
                edas: @json($edasScores)
            };

            function getDatasets(type) {
                const isLine = type === 'line';

                return [{
                        label: 'SAW',
                        data: dataSets.saw,
                        // Jika line, backgroundColor hanya untuk titik (point), jika bar untuk batang
                        backgroundColor: isLine ? '#3B82F6' : 'rgba(59, 130, 246, 0.7)',
                        borderColor: '#3B82F6',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false, // PAKSA FALSE: Menghilangkan warna ke sumbu X
                        pointRadius: isLine ? 4 : 0, // Munculkan titik hanya jika mode line
                        pointHoverRadius: 6
                    },
                    {
                        label: 'ARAS',
                        data: dataSets.aras,
                        backgroundColor: isLine ? '#8B5CF6' : 'rgba(139, 92, 246, 0.7)',
                        borderColor: '#8B5CF6',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false, // PAKSA FALSE
                        pointRadius: isLine ? 4 : 0,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'EDAS',
                        data: dataSets.edas,
                        backgroundColor: isLine ? '#6366F1' : 'rgba(99, 102, 241, 0.7)',
                        borderColor: '#6366F1',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false, // PAKSA FALSE
                        pointRadius: isLine ? 4 : 0,
                        pointHoverRadius: 6
                    }
                ];
            }

            let chartConfig = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: getDatasets('bar')
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grace: '10%'
                        }
                    }
                }
            };

            let ctx = document.getElementById('comparisonChart').getContext('2d');
            let myChart = new Chart(ctx, chartConfig);

            function switchChart(type) {
                myChart.config.type = type;
                myChart.data.datasets = getDatasets(type);
                myChart.update();

                const active = 'px-4 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white shadow transition';
                const inactive = 'px-4 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-700 transition';
                document.getElementById('btnBar').className = type === 'bar' ? active : inactive;
                document.getElementById('btnLine').className = type === 'line' ? active : inactive;
            }
        </script>
    @endpush
</x-app-layout>
