<?php

namespace App\Services;

class SPKService
{

    // Nilai RI standar Saaty untuk n = 1 sampai 10 (Standard AHP)
    private $ri = [0, 0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];

    // -------------------------------------------------------------------------
    // AHP — Analytic Hierarchy Process
    // Menghitung bobot kriteria dari perbandingan berpasangan.
    // Input  : $matrix — array asosiatif [rowName => [colName => nilai]]
    // Output : array [rowName => bobot (0–1), semua bobot dijumlah = 1]
    // -------------------------------------------------------------------------
    public function calculateAHP(array $matrix): array
    {
        $size = count($matrix);
        if ($size === 0) return [];

        // 1. Jumlah setiap kolom
        $columnSums = [];
        foreach ($matrix as $row) {
            foreach ($row as $colName => $value) {
                $columnSums[$colName] = ($columnSums[$colName] ?? 0) + $value;
            }
        }

        // 2. Normalisasi & eigenvector
        $weights = [];
        foreach ($matrix as $rowName => $row) {
            $rowSum = 0;
            foreach ($row as $colName => $value) {
                $rowSum += $value / ($columnSums[$colName] ?: 1);
            }
            $weights[$rowName] = $rowSum / $size;
        }

        // 3. Weighted Sum Vector untuk lambda
        $weightedSum   = [];
        $lambdaValues  = [];
        $names         = array_keys($matrix);

        foreach ($names as $rowName) {
            $sum = 0;
            foreach ($names as $colName) {
                $sum += $matrix[$rowName][$colName] * $weights[$colName];
            }
            $weightedSum[$rowName]  = round($sum, 6);
            $lambdaValues[$rowName] = round($sum / ($weights[$rowName] ?: 1), 6);
        }

        $lambdaMax = round(array_sum($lambdaValues) / $size, 6);
        $ci        = ($size > 1) ? round(($lambdaMax - $size) / ($size - 1), 6) : 0;
        $riValue   = $this->ri[$size] ?? 1.49;
        $cr        = round(($riValue > 0) ? $ci / $riValue : 0, 6);

        // Normalisasi matriks (untuk ditampilkan di calculation blade)
        $normalizedMatrix = [];
        foreach ($matrix as $rowName => $row) {
            foreach ($row as $colName => $value) {
                $normalizedMatrix[$rowName][$colName] = round($value / ($columnSums[$colName] ?: 1), 6);
            }
        }

        return [
            'weights'     => $weights,
            'consistency' => [
                'lambda_max'    => $lambdaMax,
                'ci'            => $ci,
                'cr'            => $cr,
                'is_consistent' => $cr < 0.1,
            ],
            // Steps untuk halaman calculation
            'steps' => [
                'matrix'            => $matrix,
                'column_sums'       => $columnSums,
                'normalized_matrix' => $normalizedMatrix,
                'weights'           => $weights,
                'weighted_sum'      => $weightedSum,
                'lambda_values'     => $lambdaValues,
                'lambda_max'        => $lambdaMax,
                'n'                 => $size,
                'ci'                => $ci,
                'ri'                => $riValue,
                'cr'                => $cr,
                'consistent'        => $cr <= 0.1,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // BWM — Best Worst Method (New)
    // -------------------------------------------------------------------------
    public function calculateBWM($best, $worst, $bto, $otw): array
    {
        $weights    = [];
        $sumWeights = 0;

        foreach ($bto as $name => $pref) {
            $wBest          = 1 / ($pref ?: 1);
            $wWorst         = ($otw[$name] ?? 1);
            $weights[$name] = ($wBest + ($wWorst / 9)) / 2;
            $sumWeights     += $weights[$name];
        }

        foreach ($weights as $name => $val) {
            $weights[$name] = $val / ($sumWeights ?: 1);
        }

        return $weights;
    }

    // -------------------------------------------------------------------------
    // SAW — Simple Additive Weighting
    //
    // Langkah:
    //   1. Normalisasi per kolom kriteria
    //      • Benefit : r_ij = x_ij / max(x_j)
    //      • Cost    : r_ij = min(x_j) / x_ij
    //   2. Skor akhir : V_i = Σ (w_j × r_ij)
    //
    // Input:
    //   $data    — array of rows: [['name' => '...', 'values' => [0 => val, ...]], ...]
    //   $weights — array [kriteria_index => bobot]
    //   $mapping — array [kriteria_index => 'benefit'|'cost']
    //
    // Output: array of rows diurutkan skor tertinggi, tiap row berisi:
    //   name, values, normalized, score
    // -------------------------------------------------------------------------
    public function calculateSAW(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $criteriaIndexes = array_keys($weights);

        // Langkah 1: Max & Min per kriteria
        $maxVal = [];
        $minVal = [];
        foreach ($criteriaIndexes as $idx) {
            $vals       = array_map(fn($r) => $r['values'][$idx] ?? 0, $data);
            $maxVal[$idx] = max($vals) ?: 1;
            $minVal[$idx] = min($vals) ?: 1;
        }

        // Langkah 2: Normalisasi (key = nama alternatif)
        $normalizedMatrix = [];
        foreach ($data as $row) {
            $name = $row['name'];
            foreach ($criteriaIndexes as $idx) {
                $x = $row['values'][$idx] ?? 0;
                $normalizedMatrix[$name][$idx] = ($mapping[$idx] === 'benefit')
                    ? round($x / $maxVal[$idx], 6)
                    : round($minVal[$idx] / ($x ?: 1), 6);
            }
        }

        // Langkah 3: Weighted sum
        $processed = [];
        foreach ($data as $row) {
            $name    = $row['name'];
            $score   = 0;
            $weighted = [];

            foreach ($criteriaIndexes as $idx) {
                $wv          = round(($weights[$idx] ?? 0) * $normalizedMatrix[$name][$idx], 6);
                $weighted[$idx] = $wv;
                $score       += $wv;
            }

            $processed[] = [
                'name'     => $name,
                'values'   => $row['values'],
                'normalized' => $normalizedMatrix[$name],
                'weighted' => $weighted,
                'score'    => round($score, 6),
            ];
        }

        $finalResult = $this->sortResult($processed);

        return [
            'final' => $finalResult,
            'steps' => [
                'original'    => $data,
                'criteria'    => $criteriaIndexes,
                'mapping'     => $mapping,
                'weights'     => $weights,
                'max_val'     => $maxVal,
                'min_val'     => $minVal,
                'norm_matrix' => $normalizedMatrix, // key = nama alternatif
                'results'     => $finalResult,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // ARAS — Additive Ratio Assessment
    //
    // Langkah:
    //   1. Tambahkan baris optimum A0
    //      • Benefit : A0_j = max(x_j)
    //      • Cost    : A0_j = min(x_j)
    //   2. Normalisasi terhadap jumlah kolom
    //      • Benefit : x_ij / Σ x_j
    //      • Cost    : (1/x_ij) / Σ (1/x_j)
    //   3. Weighted normalized : y_ij = w_j × x̄_ij
    //   4. Utility function    : S_i = Σ y_ij
    //   5. Degree of utility   : K_i = S_i / S_0
    //
    // Input/Output sama dengan calculateSAW.
    // -------------------------------------------------------------------------
    public function calculateARAS(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $criteriaIndexes = array_keys($weights);

        // Langkah 1: A0 Optimum
        $a0 = [];
        foreach ($criteriaIndexes as $idx) {
            $vals   = array_map(fn($r) => $r['values'][$idx] ?? 0, $data);
            $a0[$idx] = ($mapping[$idx] === 'benefit') ? max($vals) : min($vals);
        }

        // Gabungkan A0 di depan
        $extendedData = array_merge(
            [['name' => 'A0 (Optimal)', 'values' => $a0]],
            $data
        );

        // Langkah 2: Transformasi Cost → 1/x (key = nama)
        $transformedMatrix = [];
        foreach ($extendedData as $row) {
            $name = $row['name'];
            foreach ($criteriaIndexes as $idx) {
                $val = $row['values'][$idx] ?? 0;
                $transformedMatrix[$name][$idx] = ($mapping[$idx] === 'cost')
                    ? 1 / ($val ?: 1)
                    : (float) $val;
            }
        }

        // Langkah 3: Σ per kolom
        // ✅ FIX: array_column tidak bekerja pada array asosiatif 2D,
        //         gunakan loop manual
        $totalPerColumn = [];
        foreach ($criteriaIndexes as $idx) {
            $totalPerColumn[$idx] = 0;
            foreach ($transformedMatrix as $row) {
                $totalPerColumn[$idx] += $row[$idx] ?? 0;
            }
        }

        // Langkah 4: Normalisasi (key = nama)
        $normalizedMatrix = [];
        foreach ($transformedMatrix as $name => $values) {
            foreach ($criteriaIndexes as $idx) {
                $normalizedMatrix[$name][$idx] = round(
                    $values[$idx] / ($totalPerColumn[$idx] ?: 1),
                    6
                );
            }
        }

        // Langkah 5: S_i = Σ (w_j × x̄_ij)
        $siValues = [];
        foreach ($normalizedMatrix as $name => $values) {
            $s = 0;
            foreach ($criteriaIndexes as $idx) {
                $s += ($weights[$idx] ?? 0) * $values[$idx];
            }
            $siValues[$name] = round($s, 6);
        }

        // Langkah 6: K_i = S_i / S_0
        $s0            = $siValues['A0 (Optimal)'] ?? 1;
        $finalProcessed = [];

        foreach ($data as $row) {
            $name = $row['name'];
            $finalProcessed[] = [
                'name'  => $name,
                'si'    => $siValues[$name] ?? 0,
                'score' => round(($siValues[$name] ?? 0) / ($s0 ?: 1), 6),
            ];
        }

        $finalResult = $this->sortResult($finalProcessed);

        return [
            'final' => $finalResult,
            'steps' => [
                'original'           => $data,
                'criteria'           => $criteriaIndexes,
                'mapping'            => $mapping,
                'weights'            => $weights,
                'a0'                 => $a0,
                'all_rows'           => $extendedData,
                'sum_col'            => $totalPerColumn,
                'transformed_matrix' => $transformedMatrix,
                'norm_matrix'        => $normalizedMatrix, // key = nama
                'si_values'          => $siValues,
                's0'                 => $s0,
                'results'            => $finalResult,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // EDAS — Evaluation based on Distance from Average Solution
    //
    // Langkah:
    //   1. Average Solution (AV_j) = mean setiap kolom kriteria
    //   2. Positive Distance from Average (PDA):
    //      • Benefit : max(0, x_ij − AV_j) / AV_j
    //      • Cost    : max(0, AV_j − x_ij) / AV_j
    //   3. Negative Distance from Average (NDA):
    //      • Benefit : max(0, AV_j − x_ij) / AV_j
    //      • Cost    : max(0, x_ij − AV_j) / AV_j
    //   4. Weighted sum:
    //      SP_i = Σ (w_j × PDA_ij)
    //      SN_i = Σ (w_j × NDA_ij)
    //   5. Normalisasi:
    //      NSP_i = SP_i / max(SP)
    //      NSN_i = 1 − SN_i / max(SN)
    //   6. Appraisal score:
    //      AS_i = 0.5 × (NSP_i + NSN_i)
    //
    // Input/Output sama dengan calculateSAW.
    // -------------------------------------------------------------------------
    public function calculateEDAS(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $criteriaIndexes = array_keys($weights);
        $numAlt          = count($data);

        // Langkah 1: Average Solution
        $av = [];
        foreach ($criteriaIndexes as $idx) {
            $vals   = array_map(fn($r) => $r['values'][$idx] ?? 0, $data);
            $av[$idx] = round(array_sum($vals) / $numAlt, 6);
        }

        // Langkah 2: PDA & NDA (key = nama)
        $pda = [];
        $nda = [];
        foreach ($data as $row) {
            $name = $row['name'];
            foreach ($criteriaIndexes as $idx) {
                $x   = $row['values'][$idx] ?? 0;
                $avg = $av[$idx] ?: 1;

                if ($mapping[$idx] === 'benefit') {
                    $pda[$name][$idx] = round(max(0, $x - $avg) / $avg, 6);
                    $nda[$name][$idx] = round(max(0, $avg - $x) / $avg, 6);
                } else {
                    $pda[$name][$idx] = round(max(0, $avg - $x) / $avg, 6);
                    $nda[$name][$idx] = round(max(0, $x - $avg) / $avg, 6);
                }
            }
        }

        // Langkah 3: SP & SN (key = nama)
        $sp = [];
        $sn = [];
        foreach ($data as $row) {
            $name   = $row['name'];
            $sp[$name] = 0;
            $sn[$name] = 0;
            foreach ($criteriaIndexes as $idx) {
                $w          = $weights[$idx] ?? 0;
                $sp[$name] += round($w * $pda[$name][$idx], 6);
                $sn[$name] += round($w * $nda[$name][$idx], 6);
            }
            $sp[$name] = round($sp[$name], 6);
            $sn[$name] = round($sn[$name], 6);
        }

        // Langkah 4: NSP & NSN
        $maxSP = max(array_values($sp)) ?: 1;
        $maxSN = max(array_values($sn)) ?: 1;

        $nsp = [];
        $nsn = [];
        foreach ($data as $row) {
            $name      = $row['name'];
            $nsp[$name] = round($sp[$name] / $maxSP, 6);
            $nsn[$name] = round(1 - $sn[$name] / $maxSN, 6);
        }

        // Langkah 5: Appraisal Score
        $finalProcessed = [];
        foreach ($data as $row) {
            $name = $row['name'];
            $finalProcessed[] = [
                'name'  => $name,
                'nsp'   => $nsp[$name],
                'nsn'   => $nsn[$name],
                'score' => round(($nsp[$name] + $nsn[$name]) / 2, 6),
            ];
        }

        $finalResult = $this->sortResult($finalProcessed);

        return [
            'final' => $finalResult,
            'steps' => [
                'original'         => $data,
                'criteria'         => $criteriaIndexes,
                'mapping'          => $mapping,
                'weights'          => $weights,
                'n'                => $numAlt,
                'average_solution' => $av,
                'pda_matrix'       => $pda,  // key = nama
                'nda_matrix'       => $nda,  // key = nama
                'weighted_sums'    => ['sp' => $sp, 'sn' => $sn],
                'normalized_sums'  => ['nsp' => $nsp, 'nsn' => $nsn],
                'results'          => $finalResult,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Helper: urutkan hasil dari skor tertinggi ke terendah
    // -------------------------------------------------------------------------
    private function sortResult(array $data): array
    {
        usort($data, fn($a, $b) => $b['score'] <=> $a['score']);
        foreach ($data as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        return $data;
    }
}