<x-app-layout>
    <x-slot:title>Hasil Perbandingan Metode</x-slot:title>

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 mx-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Hasil Perbandingan Metode</h1>
            <p class="text-sm text-gray-500 mt-1">Menampilkan 10 teratas · Export untuk data lengkap</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('spk.calculation') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Lihat Detail Perhitungan
            </a>
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

    {{-- ── Blok Konsistensi (adaptif: AHP, BWM, Manual) ── --}}
    @if (isset($consistency) && isset($method))
        @php
            $isConsistent = $consistency['is_consistent'] ?? true;
            $consistLabel = $consistency['label'] ?? 'Konsistensi';
            $consistValue = $consistency['value'] ?? 0;
            $threshold = $consistency['threshold'] ?? 0.1;
            $colorClass = $isConsistent
                ? [
                    'ring' => 'bg-emerald-100 text-emerald-600',
                    'text' => 'text-emerald-600',
                    'badge' => 'bg-emerald-50 border-emerald-200',
                    'alert' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
                ]
                : [
                    'ring' => 'bg-red-100 text-red-600',
                    'text' => 'text-red-600',
                    'badge' => 'bg-red-50 border-red-200',
                    'alert' => 'bg-red-50 border-red-100 text-red-700',
                ];
        @endphp

        <div class="mx-8 mb-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row items-center gap-6">

                    {{-- Ikon status --}}
                    <div
                        class="w-14 h-14 rounded-full flex-shrink-0 flex items-center justify-center {{ $colorClass['ring'] }}">
                        @if ($isConsistent)
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        @endif
                    </div>

                    {{-- Label & status --}}
                    <div class="flex-1 text-center md:text-left">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">
                            Validasi Pembobotan · {{ strtoupper($method) }}
                        </p>
                        <h3 class="text-lg font-extrabold text-gray-900">
                            {{ $isConsistent ? 'Bobot dinyatakan KONSISTEN' : 'Bobot dinyatakan TIDAK KONSISTEN' }}
                        </h3>
                        <p class="text-sm {{ $colorClass['text'] }} font-medium mt-0.5">
                            {{ $consistLabel }} = {{ number_format($consistValue, 4) }}
                            (batas ≤ {{ $threshold }})
                        </p>
                    </div>

                    {{-- Stat cards --}}
                    <div class="flex gap-4 flex-wrap justify-center">
                        @if ($method === 'ahp')
                            <x-spk-stat-mini label="λ max" :value="$consistency['lambda_max'] ?? '-'" />
                            <x-spk-stat-mini label="CI" :value="$consistency['ci'] ?? '-'" />
                            <x-spk-stat-mini label="RI" :value="$consistency['ri'] ?? '-'" />
                            <div class="text-center px-4 py-2 rounded-xl border {{ $colorClass['badge'] }}">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">CR</span>
                                <span class="text-xl font-black font-mono {{ $colorClass['text'] }}">
                                    {{ number_format($consistency['cr'] ?? 0, 4) }}
                                </span>
                                <span class="block text-[10px] text-gray-400">
                                    < 0.1</span>
                            </div>
                        @elseif ($method === 'bwm')
                            <x-spk-stat-mini label="ξ*" :value="number_format($consistency['xi'] ?? 0, 4)" />
                            <x-spk-stat-mini label="ξ max" :value="$consistency['xi_max'] ?? '-'" />
                            <div class="text-center px-4 py-2 rounded-xl border {{ $colorClass['badge'] }}">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">ξ* / ξmax</span>
                                <span class="text-xl font-black font-mono {{ $colorClass['text'] }}">
                                    {{ number_format($consistency['ci_ratio'] ?? 0, 4) }}
                                </span>
                                <span class="block text-[10px] text-gray-400">
                                    < 0.1</span>
                            </div>
                        @else
                            {{-- Manual: tidak ada ukuran konsistensi --}}
                            <div class="text-center px-4 py-3 rounded-xl bg-gray-50 border border-gray-200">
                                <span class="block text-xs text-gray-400">Pembobotan Manual</span>
                                <span class="block text-xs font-semibold text-gray-600 mt-1">
                                    Tidak ada uji konsistensi
                                </span>
                                <span class="block text-[10px] text-gray-400 mt-0.5">
                                    Bobot diinput langsung
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Alert tidak konsisten --}}
                @if (!$isConsistent)
                    <div class="mt-4 p-3 {{ $colorClass['alert'] }} border rounded-xl flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-xs leading-relaxed">
                            @if ($method === 'ahp')
                                <strong>Saran:</strong> Nilai CR > 0.1. Tinjau kembali matriks perbandingan berpasangan
                                — pastikan penilaian transitif dan logis.
                            @elseif ($method === 'bwm')
                                <strong>Saran:</strong> Nilai ξ*/ξmax > 0.1. Tinjau kembali vektor Best-to-Others dan
                                Others-to-Worst agar lebih konsisten.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Tabel Hasil 3 Metode ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mx-8">
        @foreach (['SAW', 'ARAS', 'EDAS'] as $mName)
            @php
                $key = strtolower($mName);
                $allRows = $results[$key] ?? [];
                $preview = array_slice($allRows, 0, 10);
                $total = count($allRows);
                $cfg = [
                    'SAW' => ['header' => 'bg-blue-600', 'badge' => 'bg-blue-100 text-blue-700'],
                    'ARAS' => ['header' => 'bg-purple-600', 'badge' => 'bg-purple-100 text-purple-700'],
                    'EDAS' => ['header' => 'bg-indigo-600', 'badge' => 'bg-indigo-100 text-indigo-700'],
                ][$mName];
            @endphp

            <div
                class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 flex flex-col hover:shadow-lg transition-shadow">
                <div class="{{ $cfg['header'] }} px-5 py-4 flex items-center justify-between">
                    <span class="text-white font-extrabold tracking-wide">{{ $mName }}</span>
                    <span class="text-white/70 text-xs font-medium">{{ $total }} alternatif</span>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="bg-gray-50 border-b border-gray-100 text-[10px] uppercase tracking-wider text-gray-400">
                                <th class="px-4 py-3 text-left w-10">Rank</th>
                                <th class="px-4 py-3 text-left">Alternatif</th>
                                <th class="px-4 py-3 text-right">Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($preview as $index => $res)
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
                                        title="{{ $res['name'] ?? '' }}">
                                        {{ $res['name'] ?? 'Tanpa Nama' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded-lg {{ $cfg['badge'] }} font-mono text-xs font-bold">
                                            {{ number_format((float) ($res['score'] ?? 0), 4) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400 text-xs italic">
                                        Tidak ada data
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-[10px] text-gray-400">
                        {{ $total > 10 ? '+' . ($total - 10) . ' lainnya' : 'Semua data ditampilkan' }}
                    </span>
                    <form action="{{ route('spk.export') }}" method="POST">
                        @csrf
                        <input type="hidden" name="method" value="{{ $key }}">
                        <button type="submit"
                            class="text-xs font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1 transition">
                            Excel
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Keterangan rank --}}
    <div class="mt-4 flex flex-wrap gap-4 text-[10px] text-gray-400 mx-10 uppercase font-bold tracking-widest">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Rank 1</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300"></span> Rank 2</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-300"></span> Rank 3</span>
    </div>

    {{-- ── Grafik Perbandingan ── --}}
    @php
        $sawTop = array_slice($results['saw'] ?? [], 0, 10);
        $labels = array_map(fn($r) => $r['name'] ?? 'N/A', $sawTop);

        $arasMap = [];
        foreach ($results['aras'] ?? [] as $r) {
            if ($r['name'] ?? null) {
                $arasMap[$r['name']] = $r['score'] ?? 0;
            }
        }
        $edasMap = [];
        foreach ($results['edas'] ?? [] as $r) {
            if ($r['name'] ?? null) {
                $edasMap[$r['name']] = $r['score'] ?? 0;
            }
        }

        $sawScores = array_map(fn($r) => round((float) ($r['score'] ?? 0), 4), $sawTop);
        $arasScores = array_map(fn($n) => round((float) ($arasMap[$n] ?? 0), 4), $labels);
        $edasScores = array_map(fn($n) => round((float) ($edasMap[$n] ?? 0), 4), $labels);
    @endphp

    <div class="mx-8 mt-8 mb-20">
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-900">Visualisasi Skor Lintas Metode</h2>
                    <p class="text-xs text-gray-400 mt-0.5 italic">Top 10 berdasarkan urutan SAW</p>
                </div>
                <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                    <button id="btnBar" onclick="switchChart('bar')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white shadow transition">
                        Bar
                    </button>
                    <button id="btnLine" onclick="switchChart('line')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-700 transition">
                        Line
                    </button>
                </div>
            </div>
            <div class="relative" style="height: 400px;">
                <canvas id="comparisonChart"></canvas>
            </div>
            <div class="flex flex-wrap gap-5 mt-4 justify-center border-t border-gray-50 pt-4">
                <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                    <span class="w-4 h-3 rounded inline-block bg-blue-500"></span> SAW
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                    <span class="w-4 h-3 rounded inline-block bg-purple-500"></span> ARAS
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                    <span class="w-4 h-3 rounded inline-block bg-indigo-500"></span> EDAS
                </span>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            const labels = @json($labels);
            const sawScores = @json($sawScores);
            const arasScores = @json($arasScores);
            const edasScores = @json($edasScores);

            function getDatasets(type) {
                const isLine = type === 'line';
                const mkDs = (label, color, data) => ({
                    label,
                    data,
                    backgroundColor: isLine ? color : color.replace('1)', '0.75)'),
                    borderColor: color,
                    borderWidth: 2,
                    borderRadius: isLine ? 0 : 5,
                    tension: 0.35,
                    fill: false,
                    pointRadius: isLine ? 4 : 0,
                    pointHoverRadius: 7,
                });
                return [
                    mkDs('SAW', 'rgba(59,130,246,1)', sawScores),
                    mkDs('ARAS', 'rgba(139,92,246,1)', arasScores),
                    mkDs('EDAS', 'rgba(99,102,241,1)', edasScores),
                ];
            }

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: c => ` ${c.dataset.label}: ${c.parsed.y.toFixed(4)}`
                        }
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 30
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        ticks: {
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)'
                        }
                    },
                },
            };

            let currentType = 'bar';
            let chart = new Chart(document.getElementById('comparisonChart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: getDatasets('bar')
                },
                options
            });

            function switchChart(type) {
                if (currentType === type) return;
                currentType = type;
                chart.destroy();
                chart = new Chart(document.getElementById('comparisonChart'), {
                    type,
                    data: {
                        labels,
                        datasets: getDatasets(type)
                    },
                    options
                });
                const on = 'px-4 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white shadow transition';
                const off = 'px-4 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-700 transition';
                document.getElementById('btnBar').className = type === 'bar' ? on : off;
                document.getElementById('btnLine').className = type === 'line' ? on : off;
            }
        </script>
    @endpush
</x-app-layout>
