<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\CommisionImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\SPKService;
use App\Exports\SpkResultsExport;

class SPKController extends Controller
{
    protected $spkService;

    public function __construct(SPKService $spkService)
    {
        $this->spkService = $spkService;
    }

    public function index()
    {
        return view('spk.index');
    }

    public function preview(Request $request)
    {

        // Jika input manual
        if ($request->has('is_manual')) {
            $headers = $request->input('headers');
            $rows = $request->input('manual_rows'); // Ini array 2 dimensi dari input manual

            // Simpan ke session dengan format yang sama seperti hasil import Excel
            // Masukkan headers sebagai baris pertama di array rows agar seragam
            $fullData = array_merge([$headers], $rows);
            session(['spk_rows' => $fullData]);

            return view('spk.preview', [
                'headers' => $headers,
                'rows' => $fullData
            ]);
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new CommisionImport();
        Excel::import($import, $request->file('file'));

        $rows    = $import->rows->toArray();
        $headers = $rows[0];

        session([
            'spk_rows'    => $rows,
            'spk_headers' => $headers,
        ]);

        return view('spk.preview', compact('rows', 'headers'));
    }

    public function weighting(Request $request)
    {
        $mappings = $request->input('mapping');
        $headerNames = $request->input('header_names');
        $method = $request->input('weight_method'); 
        
        $selectedCriteria = [];
        $identityIndex = null;

        foreach ($mappings as $index => $type) {
            if ($type === 'benefit' || $type === 'cost') {
                $selectedCriteria[] = [
                    'name' => $headerNames[$index],
                    'type' => $type,
                    'index' => $index
                ];
            }
            // Simpan index kolom identitas (Nama)
            if ($type === 'identity') {
                $identityIndex = $index;
            }
        }

        if (empty($selectedCriteria)) {
            return redirect()->back()->with('error', 'Pilih minimal satu kriteria!');
        }

        // Simpan konfigurasi ke session untuk digunakan di calculate()
        session([
            'spk_criteria' => $selectedCriteria,
            'spk_identity_index' => $identityIndex,
            'spk_weight_method' => $method
        ]);

        $viewName = "spk.weighting_" . $method;
        return view($viewName, ['selectedCriteria' => $selectedCriteria]);
    }

    public function calculate(Request $request)
{
    $method      = $request->input('weight_method');
    $weightsRaw  = [];
    $ahpSteps    = null;
    $consistency = null;

    if ($method === 'manual') {
        foreach ($request->input('weights', []) as $name => $val) {
            $weightsRaw[$name] = floatval($val) / 100;
        }

    } elseif ($method === 'ahp') {
        $ahpData     = $this->spkService->calculateAHP($request->input('matrix', []));
        // ✅ AHP return ['weights' => ..., 'consistency' => ..., 'steps' => ...]
        $weightsRaw  = $ahpData['weights'];
        $consistency = $ahpData['consistency'];
        $ahpSteps    = $ahpData['steps'] ?? null;

    } elseif ($method === 'bwm') {
        $weightsRaw = $this->spkService->calculateBWM(
            $request->input('best_criteria'),
            $request->input('worst_criteria'),
            $request->input('best_to_others'),
            $request->input('others_to_worst')
        );
    }

    $rows          = session('spk_rows', []);
    $criteria      = session('spk_criteria', []);
    $identityIndex = session('spk_identity_index');

    if (empty($rows) || empty($criteria)) {
        return redirect()->route('spk.index')
            ->with('error', 'Session habis atau kriteria belum dipilih.');
    }

    $mapping      = [];
    $alternatives = [];

    foreach ($criteria as $c) {
        $mapping[$c['index']] = $c['type'];
    }

    foreach (array_slice($rows, 1) as $row) {
        if (empty(array_filter($row))) continue;

        $values = [];
        foreach ($criteria as $c) {
            $raw          = $row[$c['index']] ?? 0;
            $values[$c['index']] = (float) (is_numeric($raw) ? $raw : str_replace(',', '.', $raw));
        }

        $alternatives[] = [
            'name'   => $identityIndex !== null ? ($row[$identityIndex] ?? 'Tanpa Nama') : 'Tanpa Nama',
            'values' => $values,
        ];
    }

    $weightsMapped = [];
    foreach ($criteria as $c) {
        $weightsMapped[$c['index']] = $weightsRaw[$c['name']] ?? (1 / count($criteria));
    }

    // ✅ PERBAIKAN UTAMA: ambil key 'final', bukan 'results'
    $sawData  = $this->spkService->calculateSAW($alternatives, $weightsMapped, $mapping);
    $arasData = $this->spkService->calculateARAS($alternatives, $weightsMapped, $mapping);
    $edasData = $this->spkService->calculateEDAS($alternatives, $weightsMapped, $mapping);

    $results = [
        'saw'  => $sawData['final']  ?? [],
        'aras' => $arasData['final'] ?? [],
        'edas' => $edasData['final'] ?? [],
    ];

    $allSteps = [
        'ahp'  => $ahpSteps,
        'saw'  => $sawData['steps']  ?? [],
        'aras' => $arasData['steps'] ?? [],
        'edas' => $edasData['steps'] ?? [],
    ];

    session([
        'spk_results'       => $results,
        'calculation_steps' => $allSteps,   // ← pastikan key ini yang dipakai
        'spk_weights'       => $weightsRaw, // ← untuk ditampilkan di calculation
        'spk_consistency'   => $consistency,
        'spk_weight_method' => $method,
    ]);

    // Hapus dd() dan lanjut ke view
    return view('spk.results', [
        'results'     => $results,
        'weights'     => $weightsRaw,
        'criteria'    => $criteria,
        'method'      => $method,
        'consistency' => $consistency,
    ]);

    // Cek session masih ada
    if (!session()->has('calculation_steps')) {
        return redirect()->route('spk.index')
            ->with('error', 'Session habis. Silakan hitung ulang terlebih dahulu.');
    }

    return view('spk.calculation', [
        'weights'     => session('spk_weights', []),     // bobot raw
        'criteria'    => session('spk_criteria', []),
        'method'      => session('spk_weight_method', 'manual'),
        'consistency' => session('spk_consistency'),
    ]);
}

    public function export(Request $request)
{
    $results  = session('spk_results'); // simpan hasil calculate ke session dulu
    $method   = $request->input('method'); // null = export semua

    // Gunakan Laravel Excel export
    return Excel::download(new SpkResultsExport($results, $method), 'hasil-spk.xlsx');
}
    public function calculation()
{
    // Cek session masih ada, kalau tidak redirect balik
    if (!session()->has('calculation_steps')) {
        return redirect()->route('spk.index')
            ->with('error', 'Session habis. Silakan upload dan hitung ulang.');
    }

    $calcSteps = session('calculation_steps', []);
    $arasSteps = $calcSteps['aras'] ?? [];

    // Tambahkan ini sementara untuk debug
    // dd([
    //     'norm_matrix_sample'    => array_slice($arasSteps['norm_matrix'] ?? [], 0, 3),
    //     'transformed_sample'    => array_slice($arasSteps['transformed_matrix'] ?? [], 0, 3),
    //     'sum_col'               => $arasSteps['sum_col'] ?? [],
    //     'norm_matrix_keys'      => array_keys($arasSteps['norm_matrix'] ?? []),
    //     'total_rows'            => count($arasSteps['norm_matrix'] ?? []),
    // ]);

    return view('spk.calculation', [
        'weights'     => session('spk_weights', []),
        'criteria'    => session('spk_criteria', []),
        'method'      => session('spk_weight_method', 'manual'),
        'consistency' => session('spk_consistency'),
    ]);
}

    public function results() {
        return view('spk.results', [
            'results'     => session('spk_results', []),
            'weights'     => session('spk_weights', []),
            'criteria'    => session('spk_criteria', []),
            'method'      => session('spk_weight_method', 'manual'),
            'consistency' => session('spk_consistency'),
        ]);
    }
}