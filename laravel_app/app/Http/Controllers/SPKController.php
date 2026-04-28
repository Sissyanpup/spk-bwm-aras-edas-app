<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\CommisionImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\SPKService;
use App\Exports\SpkResultsExport;

class SPKController extends Controller
{
    protected SPKService $spkService;

    public function __construct(SPKService $spkService)
    {
        $this->spkService = $spkService;
    }

    // =========================================================================
    // index — halaman upload
    // =========================================================================
    public function index()
    {
        return view('spk.index');
    }

    // =========================================================================
    // preview — import Excel atau input manual
    // =========================================================================
    public function preview(Request $request)
    {
        if ($request->has('is_manual')) {
            $headers  = $request->input('headers');
            $rows     = $request->input('manual_rows');
            $fullData = array_merge([$headers], $rows);

            session(['spk_rows' => $fullData]);

            return view('spk.preview', [
                'headers' => $headers,
                'rows'    => $fullData,
            ]);
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
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

    // =========================================================================
    // weighting — mapping kriteria → pilih metode bobot
    // =========================================================================
    public function weighting(Request $request)
    {
        $mappings      = $request->input('mapping');
        $headerNames   = $request->input('header_names');
        $method        = $request->input('weight_method');

        $selectedCriteria = [];
        $identityIndex    = null;

        foreach ($mappings as $index => $type) {
            if ($type === 'benefit' || $type === 'cost') {
                $selectedCriteria[] = [
                    'name'  => $headerNames[$index],
                    'type'  => $type,
                    'index' => $index,
                ];
            }
            if ($type === 'identity') {
                $identityIndex = $index;
            }
        }

        if (empty($selectedCriteria)) {
            return redirect()->back()->with('error', 'Pilih minimal satu kriteria!');
        }

        session([
            'spk_criteria'       => $selectedCriteria,
            'spk_identity_index' => $identityIndex,
            'spk_weight_method'  => $method,
        ]);

        return view("spk.weighting_{$method}", [
            'selectedCriteria' => $selectedCriteria,
        ]);
    }

    // =========================================================================
    // calculate — proses bobot + jalankan SAW / ARAS / EDAS
    // =========================================================================
    public function calculate(Request $request)
    {
        $method      = $request->input('weight_method');
        $weightsRaw  = [];
        $consistency = null;
        $ahpSteps    = null;
        $bwmSteps    = null;

        // ── 1. Hitung bobot sesuai metode ────────────────────────────────────
        if ($method === 'manual') {
            foreach ($request->input('weights', []) as $name => $val) {
                $weightsRaw[$name] = floatval($val) / 100;
            }
            // Manual tidak punya uji konsistensi — $consistency tetap null

        } elseif ($method === 'ahp') {
            $ahpData     = $this->spkService->calculateAHP(
                $request->input('matrix', [])
            );
            // calculateAHP return ['weights', 'consistency', 'steps']
            $weightsRaw  = $ahpData['weights']     ?? [];
            $consistency = $ahpData['consistency'] ?? null;
            $ahpSteps    = $ahpData['steps']        ?? null;

        } elseif ($method === 'bwm') {
            $bwmData     = $this->spkService->calculateBWM(
                $request->input('best_criteria',  ''),
                $request->input('worst_criteria', ''),
                $request->input('best_to_others',   []),
                $request->input('others_to_worst',  [])
            );
            // calculateBWM return ['weights', 'consistency', 'steps']
            $weightsRaw  = $bwmData['weights']     ?? [];
            $consistency = $bwmData['consistency'] ?? null;
            $bwmSteps    = $bwmData['steps']        ?? null;
        }

        // ── 2. Ambil data dari session ────────────────────────────────────────
        $rows          = session('spk_rows', []);
        $criteria      = session('spk_criteria', []);
        $identityIndex = session('spk_identity_index');

        if (empty($rows) || empty($criteria)) {
            return redirect()->route('spk.index')
                ->with('error', 'Session habis atau kriteria belum dipilih.');
        }

        // ── 3. Bangun $alternatives & $mapping ───────────────────────────────
        $mapping      = [];
        $alternatives = [];

        foreach ($criteria as $c) {
            $mapping[$c['index']] = $c['type'];
        }

        foreach (array_slice($rows, 1) as $row) {
            if (empty(array_filter($row))) continue;

            $values = [];
            foreach ($criteria as $c) {
                $raw               = $row[$c['index']] ?? 0;
                $values[$c['index']] = (float) (
                    is_numeric($raw) ? $raw : str_replace(',', '.', $raw)
                );
            }

            $alternatives[] = [
                'name'   => $identityIndex !== null
                    ? ($row[$identityIndex] ?? 'Tanpa Nama')
                    : 'Tanpa Nama',
                'values' => $values,
            ];
        }

        // ── 4. Map bobot ke index kriteria ────────────────────────────────────
        $weightsMapped = [];
        foreach ($criteria as $c) {
            $weightsMapped[$c['index']] = $weightsRaw[$c['name']]
                ?? (1 / count($criteria));
        }

        // ── 5. Jalankan ketiga metode SPK ─────────────────────────────────────
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
            'bwm'  => $bwmSteps,
            'saw'  => $sawData['steps']  ?? [],
            'aras' => $arasData['steps'] ?? [],
            'edas' => $edasData['steps'] ?? [],
        ];

        // ── 6. Simpan ke session (untuk export & halaman calculation) ─────────
        session([
            'spk_results'        => $results,
            'calculation_steps'  => $allSteps,
            'spk_weights'        => $weightsRaw,
            'spk_consistency'    => $consistency,
            'spk_weight_method'  => $method,
        ]);

        return view('spk.results', [
            'results'     => $results,
            'weights'     => $weightsRaw,
            'criteria'    => $criteria,
            'method'      => $method,
            'consistency' => $consistency,
        ]);
    }

    // =========================================================================
    // results — tampilkan ulang hasil dari session (GET)
    // =========================================================================
    public function results()
    {
        if (!session()->has('spk_results')) {
            return redirect()->route('spk.index')
                ->with('error', 'Session habis. Silakan hitung ulang.');
        }

        return view('spk.results', [
            'results'     => session('spk_results', []),
            'weights'     => session('spk_weights', []),
            'criteria'    => session('spk_criteria', []),
            'method'      => session('spk_weight_method', 'manual'),
            'consistency' => session('spk_consistency'),
        ]);
    }

    // =========================================================================
    // calculation — halaman transparansi perhitungan
    // =========================================================================
    public function calculation()
    {
        if (!session()->has('calculation_steps')) {
            return redirect()->route('spk.index')
                ->with('error', 'Session habis. Silakan upload dan hitung ulang.');
        }

        return view('spk.calculation', [
            'weights'     => session('spk_weights', []),
            'criteria'    => session('spk_criteria', []),
            'method'      => session('spk_weight_method', 'manual'),
            'consistency' => session('spk_consistency'),
        ]);
    }

    // =========================================================================
    // export — download hasil sebagai Excel
    // =========================================================================
    public function export(Request $request)
    {
        $results = session('spk_results');

        if (empty($results)) {
            return redirect()->back()
                ->with('error', 'Tidak ada data. Silakan hitung ulang terlebih dahulu.');
        }

        $method   = $request->input('method');
        $filename = 'hasil-spk-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new SpkResultsExport($results, $method),
            $filename
        );
    }
}