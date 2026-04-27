<x-app-layout>
    <x-slot:title>Pembobotan Manual</x-slot:title>

    <div class="max-w-3xl mx-auto" x-data="{
        weights: {},
        total() {
            return Object.values(this.weights).reduce((a, b) => parseFloat(a || 0) + parseFloat(b || 0), 0);
        },
        isValid() {
            return Math.abs(this.total() - 100) < 0.01;
        }
    }">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            <div class="mb-8 flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900">Pembobotan Langsung</h2>
                    <p class="text-gray-500 mt-2">Tentukan bobot kriteria secara manual (Total harus 100%).</p>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Total Terisi</div>
                    <div :class="isValid() ? 'text-green-600' : 'text-red-600'"
                        class="text-4xl font-black transition-colors">
                        <span x-text="total().toFixed(0)"></span>%
                    </div>
                </div>
            </div>

            <form action="{{ route('spk.calculate') }}" method="POST">
                @csrf
                <input type="hidden" name="weight_method" value="manual">

                <div class="space-y-4">
                    @foreach ($selectedCriteria as $c)
                        <div
                            class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-blue-300 transition-all">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800">{{ $c['name'] }}</h4>
                                <span
                                    class="text-xs uppercase font-bold {{ $c['type'] == 'benefit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $c['type'] }}
                                </span>
                            </div>

                            <div class="w-32 relative">
                                <input type="number" name="weights[{{ $c['name'] }}]" step="0.01" min="0"
                                    max="100" x-model="weights['{{ $c['name'] }}']" placeholder="0"
                                    class="w-full pl-4 pr-10 py-3 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 font-mono font-bold text-right"
                                    required>
                                <span class="absolute right-3 top-3.5 text-gray-400 font-bold">%</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template x-if="total() > 0 && !isValid()">
                    <div
                        class="mt-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <span
                            x-text="total() > 100 ? 'Total melebihi 100%! Kurangi bobot.' : 'Total belum mencapai 100%. Kurang ' + (100 - total()).toFixed(0) + '% lagi.'"></span>
                    </div>
                </template>

                <div class="mt-10 flex gap-4">
                    <button type="button" onclick="history.back()"
                        class="flex-1 px-6 py-4 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="!isValid()"
                        :class="!isValid() ? 'opacity-50 cursor-not-allowed bg-gray-400' :
                            'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200'"
                        class="flex-[2] text-white px-6 py-4 rounded-xl font-bold shadow-lg transition-all transform active:scale-95">
                        Jalankan Perhitungan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
