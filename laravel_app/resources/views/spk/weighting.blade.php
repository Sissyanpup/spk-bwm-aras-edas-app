<x-app-layout>
    <x-slot:title>Pembobotan AHP</x-slot:title>

    <div class="max-w-5xl mx-auto" x-data="{
        matrix: {},
        criteria: {{ json_encode($selectedCriteria) }},
        updateInverse(row, col, val) {
            if (val > 0) {
                this.matrix[col + '-' + row] = (1 / val).toFixed(3);
            }
        }
    }">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-gray-900">Matriks Perbandingan Berpasangan</h2>
                <p class="text-gray-500 mt-2">Bandingkan kepentingan antar kriteria (Skala Saaty 1-9).</p>
            </div>

            <form action="{{ route('spk.calculate') }}" method="POST">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-900 text-white">
                                <th class="p-4 text-left rounded-tl-xl">Kriteria</th>
                                @foreach ($selectedCriteria as $c)
                                    <th class="p-4 text-center">{{ $c['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($selectedCriteria as $rowIdx => $rowC)
                                <tr>
                                    <td class="p-4 font-bold bg-gray-50 border-r">{{ $rowC['name'] }}</td>
                                    @foreach ($selectedCriteria as $colIdx => $colC)
                                        <td class="p-4 text-center border-r">
                                            @if ($rowIdx == $colIdx)
                                                <input type="text"
                                                    name="matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]"
                                                    value="1"
                                                    class="w-16 text-center bg-gray-100 border-none rounded" readonly>
                                            @elseif($rowIdx < $colIdx)
                                                <select name="matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]"
                                                    class="w-20 border-blue-300 rounded-md focus:ring-blue-500 shadow-sm"
                                                    x-on:change="updateInverse('{{ $rowC['name'] }}', '{{ $colC['name'] }}', $event.target.value)">
                                                    @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $v)
                                                        <option value="{{ $v }}">{{ $v }}
                                                        </option>
                                                    @endforeach
                                                    @foreach ([2, 3, 4, 5, 6, 7, 8, 9] as $v)
                                                        <option value="{{ 1 / $v }}">{{ "1/$v" }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text"
                                                    name="matrix[{{ $rowC['name'] }}][{{ $colC['name'] }}]"
                                                    :x-ref="'inv-{{ $rowC['name'] }}-{{ $colC['name'] }}'"
                                                    x-model="matrix['{{ $rowC['name'] }}-{{ $colC['name'] }}']"
                                                    class="w-16 text-center bg-gray-50 border-none text-blue-600 font-medium"
                                                    readonly>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-10 flex items-center justify-between bg-indigo-50 p-6 rounded-2xl">
                    <div class="text-indigo-800 text-sm">
                        <p><strong>Note:</strong> Nilai di bawah diagonal akan terisi otomatis secara terbalik
                            (reciprocal).</p>
                    </div>
                    <button type="submit"
                        class="bg-indigo-600 text-white px-10 py-4 rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition-all transform hover:scale-105">
                        Hitung Konsistensi & Skor Akhir
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
