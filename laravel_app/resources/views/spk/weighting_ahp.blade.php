<x-app-layout>
    <x-slot:title>Pembobotan AHP</x-slot:title>

    <div class="max-w-5xl mx-auto" x-data="{
        matrix: {},
        criteria: {{ json_encode($selectedCriteria) }},
        init() {
            // Inisialisasi semua sel dengan 1 agar tidak '-' saat pertama render
            this.criteria.forEach(row => {
                this.criteria.forEach(col => {
                    this.matrix[row.name + '-' + col.name] = 1;
                });
            });
        },
        updateInverse(rowName, colName, val) {
            let numericVal = parseFloat(val);
            if (numericVal > 0) {
                // Update sel kebalikan (Reciprocal)
                // Contoh: matrix['Harga-Kualitas'] = 3, maka matrix['Kualitas-Harga'] = 0.333
                this.matrix[colName + '-' + rowName] = (1 / numericVal).toFixed(3);
            }
        }
    }">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-gray-900">Matriks Perbandingan AHP</h2>
                <p class="text-gray-500 mt-2">Bandingkan tingkat kepentingan antar kriteria (Skala Saaty 1-9).</p>
            </div>

            <form action="{{ route('spk.calculate') }}" method="POST">
                @csrf
                <input type="hidden" name="weight_method" value="ahp">

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-900 text-white">
                                <th class="p-4 text-left">Kriteria</th>
                                @foreach ($selectedCriteria as $c)
                                    <th class="p-4 text-center">{{ $c['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($selectedCriteria as $rowIdx => $rowC)
                                <tr>
                                    <td class="p-4 font-bold bg-gray-50 border-r text-gray-700">{{ $rowC['name'] }}</td>
                                    @foreach ($selectedCriteria as $colIdx => $colC)
                                        <td class="p-4 text-center border-r min-w-[100px]">
                                            @if ($rowIdx == $colIdx)
                                                <span class="font-bold text-gray-400">1</span>
                                                <input type="hidden"
                                                    name="matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]"
                                                    value="1">
                                            @elseif($rowIdx < $colIdx)
                                                <select name="matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]"
                                                    class="w-20 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 shadow-sm"
                                                    x-on:change="updateInverse('{{ $rowC['name'] }}', '{{ $colC['name'] }}', $event.target.value)">
                                                    <option value="1">1</option>
                                                    @foreach ([2, 3, 4, 5, 6, 7, 8, 9] as $v)
                                                        <option value="{{ $v }}">{{ $v }}
                                                        </option>
                                                        <option value="{{ round(1 / $v, 3) }}">1/{{ $v }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <div class="text-indigo-600 font-mono text-sm font-bold"
                                                    x-text="matrix['{{ $rowC['name'] }}-{{ $colC['name'] }}']">
                                                </div>
                                                <input type="hidden"
                                                    :name="'matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]'"
                                                    :value="matrix['{{ $rowC['name'] }}-{{ $colC['name'] }}']">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div
                    class="mt-8 flex items-center justify-between bg-indigo-50 p-6 rounded-2xl border border-indigo-100">
                    <div class="text-sm text-indigo-800 italic">
                        * Nilai di bawah diagonal (biru) dihitung otomatis sebagai kebalikan (reciprocal) dari input
                        Anda.
                    </div>
                    <div class="flex gap-4">
                        <button type="button" onclick="history.back()"
                            class="px-6 py-3 bg-white text-gray-600 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition">
                            Kembali
                        </button>
                        <button type="submit"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition transform active:scale-95">
                            Hitung AHP & Ranking
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            updateInverse(row, col, val) {
                let numericVal = parseFloat(val);
                if (numericVal > 0) {
                    // Jika user pilih 3, sel kebalikannya jadi 0.333
                    // Jika user pilih 0.333 (1/3), sel kebalikannya jadi 3
                    let inverse = (1 / numericVal).toFixed(3);
                    this.matrix[col + '-' + row] = inverse;
                }
            }
        </script>
    @endpush
</x-app-layout>
