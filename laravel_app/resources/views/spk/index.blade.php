<x-app-layout>
    <x-slot:title>Input Data Komisi</x-slot:title>

    <div class="max-w-4xl mx-auto mt-10" x-data="{
        mode: 'upload', // State awal
        manualData: {
            headers: ['Nama Alternatif', 'Kriteria 1'],
            rows: [
                ['Alternatif 1', 0]
            ]
        },
        addCriteria() {
            this.manualData.headers.push('Kriteria ' + (this.manualData.headers.length));
            this.manualData.rows.forEach(row => row.push(0));
        },
        addAlternative() {
            this.manualData.rows.push(new Array(this.manualData.headers.length).fill('').map((_, i) => i === 0 ? 'Alternatif ' + (this.manualData.rows.length + 1) : 0));
        }
    }">

        <div class="flex bg-gray-200 p-1.5 rounded-2xl mb-8 w-fit mx-auto shadow-inner">
            <button @click="mode = 'upload'"
                :class="mode === 'upload' ? 'bg-white shadow-md text-blue-600 scale-105' : 'text-gray-500 hover:text-gray-700'"
                class="px-8 py-2.5 rounded-xl font-bold transition-all duration-200 ease-in-out transform">
                📁 Upload Excel
            </button>
            <button @click="mode = 'manual'"
                :class="mode === 'manual' ? 'bg-white shadow-md text-blue-600 scale-105' : 'text-gray-500 hover:text-gray-700'"
                class="px-8 py-2.5 rounded-xl font-bold transition-all duration-200 ease-in-out transform">
                ✍️ Input Manual
            </button>
        </div>

        <div x-show="mode === 'upload'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 m-10">

            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900">Upload File Excel</h2>
                <p class="text-sm text-gray-500">Gunakan template standar untuk hasil terbaik</p>
            </div>

            <form action="{{ route('spk.preview') }}" method="POST" enctype="multipart/form-data" id="spkUploadForm"
                class="space-y-6">
                @csrf
                <div
                    class="group border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer relative">
                    <input type="file" name="file" id="fileInput" accept=".xlsx, .xls, .csv"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required
                        @change="window.fileName = $event.target.files[0].name">

                    <div class="space-y-3">
                        <div class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <span id="fileNameDisplay"
                            class="bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold inline-block">Pilih
                            File Excel</span>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-[0.98]">
                    Preview Data
                </button>
            </form>
        </div>

        <div x-show="mode === 'manual'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 m-10">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900">Input Data Manual</h2>
                    <p class="text-sm text-gray-400 uppercase tracking-widest font-semibold mt-1">Mode Demo Presentasi
                    </p>
                </div>
                <div class="flex gap-2">
                    <button @click="addCriteria()"
                        class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold hover:bg-emerald-100 transition border border-emerald-200">+
                        Kriteria</button>
                    <button @click="addAlternative()"
                        class="px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-xs font-bold hover:bg-blue-100 transition border border-blue-200">+
                        Alternatif</button>
                </div>
            </div>

            <form action="{{ route('spk.preview') }}" method="POST">
                @csrf
                <input type="hidden" name="is_manual" value="1">
                <div class="overflow-x-auto border border-gray-100 rounded-2xl mb-6 shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <template x-for="(header, hIdx) in manualData.headers" :key="hIdx">
                                    <th class="p-4">
                                        <input type="text" name="headers[]" x-model="manualData.headers[hIdx]"
                                            class="w-full bg-white border border-gray-200 rounded-lg font-bold focus:ring-blue-500 p-2 text-gray-700 shadow-sm">
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="(row, rIdx) in manualData.rows" :key="rIdx">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <template x-for="(cell, cIdx) in row" :key="cIdx">
                                        <td class="p-3">
                                            <input :type="cIdx === 0 ? 'text' : 'number'"
                                                :name="'manual_rows[' + rIdx + '][]'"
                                                x-model="manualData.rows[rIdx][cIdx]"
                                                class="w-full border-gray-200 rounded-xl focus:ring-blue-500 text-sm shadow-sm">
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all active:scale-[0.98]">
                    Lanjut Preview Data
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('fileInput').addEventListener('change', function(e) {
                let name = e.target.files[0] ? e.target.files[0].name : 'Pilih File Excel';
                document.getElementById('fileNameDisplay').innerText = name;
            });
        </script>
    @endpush
</x-app-layout>
