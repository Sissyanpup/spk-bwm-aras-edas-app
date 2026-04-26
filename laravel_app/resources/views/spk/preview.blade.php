<x-app-layout>
    <x-slot:title>Mapping Kriteria</x-slot:title>

    <div class="bg-white p-6 rounded-xl shadow-md mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Mapping Kriteria</h2>
            <p class="text-gray-500 text-sm">Tentukan peran kolom dan pilih metode pembobotan.</p>
        </div>
        <div class="flex gap-2">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">SAW</span>
            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">ARAS</span>
            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">EDAS</span>
        </div>
    </div>

    <form action="{{ route('spk.weighting') }}" method="POST">
        @csrf

        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b-2 border-gray-100">
                        <tr>
                            @foreach ($headers as $index => $header)
                                <th class="p-4 border-r">
                                    <select name="mapping[{{ $index }}]"
                                        class="block w-full text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="ignore">-- Abaikan (Ignore) --</option>
                                        <option value="identity">-- Identitas --</option>
                                        <option value="benefit">Kriteria: Benefit (Max)</option>
                                        <option value="cost">Kriteria: Cost (Min)</option>
                                    </select>
                                    <input type="hidden" name="header_names[{{ $index }}]"
                                        value="{{ $header }}">
                                </th>
                            @endforeach
                        </tr>
                        <tr class="bg-gray-900 text-white">
                            @foreach ($headers as $header)
                                <th
                                    class="px-6 py-4 text-sm font-semibold uppercase tracking-wider border-r border-gray-700">
                                    {{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach (array_slice($rows, 1, 5) as $row)
                            <tr class="hover:bg-blue-50 transition-colors">
                                @foreach ($row as $cell)
                                    <td class="px-6 py-4 text-sm text-gray-600 border-r">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 bg-white p-6 rounded-2xl shadow-lg border border-gray-100 m-10">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                    </path>
                </svg>
                Pilih Metode Pembobotan
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                    <input type="radio" name="weight_method" value="manual"
                        class="absolute top-4 right-4 h-4 w-4 text-indigo-600" checked>
                    <span class="font-bold text-gray-900">Manual (Direct)</span>
                    <span class="text-xs text-gray-500 mt-1">Input persentase bobot secara langsung (Total 100%).</span>
                </label>

                <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                    <input type="radio" name="weight_method" value="ahp"
                        class="absolute top-4 right-4 h-4 w-4 text-indigo-600">
                    <span class="font-bold text-gray-900">AHP</span>
                    <span class="text-xs text-gray-500 mt-1">Matriks perbandingan berpasangan (Analytical Hierarchy
                        Process).</span>
                </label>

                <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                    <input type="radio" name="weight_method" value="bwm"
                        class="absolute top-4 right-4 h-4 w-4 text-indigo-600">
                    <span class="font-bold text-gray-900">BWM</span>
                    <span class="text-xs text-gray-500 mt-1">Best-Worst Method. Lebih konsisten dan input lebih
                        sedikit.</span>
                </label>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center bg-blue-50 p-6 rounded-xl border border-blue-100">
            <div class="text-sm text-blue-800">
                <strong>Info:</strong> Tekan lanjut untuk mengatur nilai bobot berdasarkan metode yang dipilih.
            </div>
            <div class="flex gap-4">
                <a href="/"
                    class="px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-xl font-semibold hover:bg-gray-50 transition">Kembali</a>
                <button type="submit"
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition">
                    Lanjut ke Pembobotan →
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
