<x-app-layout>
    <x-slot:title>Pembobotan BWM</x-slot:title>

    <div class="max-w-4xl mx-auto" x-data="{ best: '', worst: '' }">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 m-10">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Best-Worst Method (BWM)</h2>

            <form action="{{ route('spk.calculate') }}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="weight_method" value="bwm">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                        <label class="block text-sm font-bold text-green-700 mb-2">Pilih Kriteria Terbaik (Best)</label>
                        <select name="best_criteria" x-model="best" class="w-full rounded-lg border-green-200">
                            <option value="">-- Pilih --</option>
                            @foreach ($selectedCriteria as $c)
                                <option value="{{ $c['name'] }}" :disabled="worst == '{{ $c['name'] }}'">
                                    {{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                        <label class="block text-sm font-bold text-red-700 mb-2">Pilih Kriteria Terburuk (Worst)</label>
                        <select name="worst_criteria" x-model="worst" class="w-full rounded-lg border-red-200">
                            <option value="">-- Pilih --</option>
                            @foreach ($selectedCriteria as $c)
                                <option value="{{ $c['name'] }}" :disabled="best == '{{ $c['name'] }}'">
                                    {{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="best" class="space-y-4 animate-fade-in">
                    <h3 class="font-bold text-gray-800 border-l-4 border-green-500 pl-3">Seberapa penting <span
                            class="text-green-600" x-text="best"></span> dibandingkan kriteria lain?</h3>
                    <p class="text-xs text-gray-500">(1: Sama penting, 9: Jauh lebih penting)</p>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach ($selectedCriteria as $c)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium">{{ $c['name'] }}</span>
                                <select name="best_to_others[{{ $c['name'] }}]" class="w-24 rounded border-gray-300">
                                    @foreach (range(1, 9) as $v)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="worst" class="space-y-4">
                    <h3 class="font-bold text-gray-800 border-l-4 border-red-500 pl-3">Seberapa penting kriteria lain
                        dibandingkan <span class="text-red-600" x-text="worst"></span>?</h3>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach ($selectedCriteria as $c)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium">{{ $c['name'] }}</span>
                                <select name="others_to_worst[{{ $c['name'] }}]"
                                    class="w-24 rounded border-gray-300">
                                    @foreach (range(1, 9) as $v)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" x-show="best && worst"
                    class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 shadow-lg transition-all">
                    Jalankan Perhitungan Final
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
