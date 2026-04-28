<?php

namespace App\Services;

class SPKService
{
    // Nilai RI standar Saaty (n = 0..10)
    private array $ri = [0, 0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];

    // Tabel ξ_max untuk BWM (n kriteria = 2..9, Rezaei 2015)
    private array $bwmXiMax = [
        2 => 0.00, 3 => 0.1667, 4 => 0.2222, 5 => 0.3000,
        6 => 0.3333, 7 => 0.4286, 8 => 0.4444, 9 => 0.5000,
    ];

    // =========================================================================
    // AHP — Analytic Hierarchy Process
    // =========================================================================
    public function calculateAHP(array $matrix): array
    {
        $size  = count($matrix);
        if ($size === 0) return [];
        $names = array_keys($matrix);

        // 1. Jumlah kolom
        $columnSums = [];
        foreach ($matrix as $row) {
            foreach ($row as $col => $val) {
                $columnSums[$col] = ($columnSums[$col] ?? 0) + $val;
            }
        }

        // 2. Normalisasi & eigenvector
        $normalizedMatrix = [];
        $weights          = [];
        foreach ($matrix as $rowName => $row) {
            $rowSum = 0;
            foreach ($row as $col => $val) {
                $norm                            = round($val / ($columnSums[$col] ?: 1), 6);
                $normalizedMatrix[$rowName][$col] = $norm;
                $rowSum                          += $norm;
            }
            $weights[$rowName] = round($rowSum / $size, 6);
        }

        // 3. Weighted sum vector → λ
        $weightedSum  = [];
        $lambdaValues = [];
        foreach ($names as $rowName) {
            $sum = 0;
            foreach ($names as $col) {
                $sum += $matrix[$rowName][$col] * $weights[$col];
            }
            $weightedSum[$rowName]  = round($sum, 6);
            $lambdaValues[$rowName] = round($sum / ($weights[$rowName] ?: 1), 6);
        }

        $lambdaMax = round(array_sum($lambdaValues) / $size, 6);
        $ci        = ($size > 1) ? round(($lambdaMax - $size) / ($size - 1), 6) : 0;
        $riValue   = $this->ri[$size] ?? 1.49;
        $cr        = round($riValue > 0 ? $ci / $riValue : 0, 6);

        return [
            'weights'     => $weights,
            'consistency' => [
                'method'        => 'ahp',
                'lambda_max'    => $lambdaMax,
                'ci'            => $ci,
                'ri'            => $riValue,
                'cr'            => $cr,
                'is_consistent' => $cr < 0.1,
                'label'         => 'CR (Consistency Ratio)',
                'threshold'     => 0.1,
                'value'         => $cr,
            ],
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

    // =========================================================================
    // BWM — Best Worst Method
    // Menghitung bobot + Consistency Index (ξ*)
    //
    // Input:
    //   $best       — nama kriteria terbaik (string)
    //   $worst      — nama kriteria terburuk (string)
    //   $bto        — Best-to-Others: [nama_kriteria => nilai (1-9)]
    //   $otw        — Others-to-Worst: [nama_kriteria => nilai (1-9)]
    // =========================================================================
    public function calculateBWM(
        string $best,
        string $worst,
        array  $bto,
        array  $otw
    ): array {
        $names = array_keys($bto);
        $n     = count($names);

        // 1. Hitung bobot kasar (aproksimasi geometric mean)
        $weights    = [];
        $sumWeights = 0;
        foreach ($names as $name) {
            $wBest          = 1 / ($bto[$name] ?: 1);          // 1 / a_Bj
            $wWorst         = ($otw[$name] ?? 1) / 9;           // a_jW / 9
            $weights[$name] = sqrt($wBest * $wWorst);           // geometric mean
            $sumWeights     += $weights[$name];
        }

        // Normalisasi
        foreach ($weights as $name => $val) {
            $weights[$name] = round($val / ($sumWeights ?: 1), 6);
        }

        // 2. Hitung ξ* (Consistency Index BWM)
        // ξ* = max untuk semua j:
        //   |w_B / w_j - a_Bj| dan |w_j / w_W - a_jW|
        $xiValues = [];
        $wBest    = $weights[$best]  ?? 0;
        $wWorst   = $weights[$worst] ?? 0;

        foreach ($names as $name) {
            $wj = $weights[$name] ?: 1;
            // Deviasi dari vektor Best-to-Others
            $dev1 = abs($wBest / $wj - ($bto[$name] ?? 1));
            // Deviasi dari vektor Others-to-Worst
            $dev2 = abs($wj / ($wWorst ?: 1) - ($otw[$name] ?? 1));
            $xiValues[$name] = round(max($dev1, $dev2), 6);
        }

        $xi      = round(max($xiValues), 6);
        $xiMax   = $this->bwmXiMax[$n] ?? 0.5;
        $ciRatio = round($xiMax > 0 ? $xi / $xiMax : 0, 6);

        return [
            'weights'     => $weights,
            'consistency' => [
                'method'        => 'bwm',
                'xi'            => $xi,
                'xi_max'        => $xiMax,
                'ci_ratio'      => $ciRatio,
                'xi_values'     => $xiValues,
                'is_consistent' => $ciRatio <= 0.1,
                'label'         => 'ξ* / ξ_max (BWM Consistency)',
                'threshold'     => 0.1,
                'value'         => $ciRatio,
            ],
            'steps' => [
                'best'       => $best,
                'worst'      => $worst,
                'bto'        => $bto,
                'otw'        => $otw,
                'weights'    => $weights,
                'xi_values'  => $xiValues,
                'xi'         => $xi,
                'xi_max'     => $xiMax,
                'ci_ratio'   => $ciRatio,
                'consistent' => $ciRatio <= 0.1,
                'n'          => $n,
            ],
        ];
    }

    // =========================================================================
    // SAW — Simple Additive Weighting
    // =========================================================================
    public function calculateSAW(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $idx = array_keys($weights);

        // 1. Max & Min
        $maxVal = [];
        $minVal = [];
        foreach ($idx as $i) {
            $vals     = array_map(fn($r) => (float) ($r['values'][$i] ?? 0), $data);
            $maxVal[$i] = max($vals) ?: 1;
            $minVal[$i] = min($vals) ?: 1;
        }

        // 2. Normalisasi (key = nama)
        $normMatrix = [];
        foreach ($data as $row) {
            $name = $row['name'];
            foreach ($idx as $i) {
                $x = (float) ($row['values'][$i] ?? 0);
                $normMatrix[$name][$i] = ($mapping[$i] === 'benefit')
                    ? round($x / $maxVal[$i], 6)
                    : round($minVal[$i] / ($x ?: 1), 6);
            }
        }

        // 3. Weighted sum
        $processed = [];
        foreach ($data as $row) {
            $name     = $row['name'];
            $score    = 0;
            $weighted = [];
            foreach ($idx as $i) {
                $wv          = round(($weights[$i] ?? 0) * $normMatrix[$name][$i], 6);
                $weighted[$i] = $wv;
                $score       += $wv;
            }
            $processed[] = [
                'name'       => $name,
                'values'     => $row['values'],
                'normalized' => $normMatrix[$name],
                'weighted'   => $weighted,
                'score'      => round($score, 6),
            ];
        }

        $final = $this->sortResult($processed);

        return [
            'final' => $final,
            'steps' => [
                'original'    => $data,
                'criteria'    => $idx,
                'mapping'     => $mapping,
                'weights'     => $weights,
                'max_val'     => $maxVal,
                'min_val'     => $minVal,
                'norm_matrix' => $normMatrix,
                'results'     => $final,
            ],
        ];
    }

    // =========================================================================
    // ARAS — Additive Ratio Assessment
    // =========================================================================
    public function calculateARAS(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $idx = array_keys($weights);

        // 1. A0 Optimum
        $a0 = [];
        foreach ($idx as $i) {
            $vals  = array_map(fn($r) => (float) ($r['values'][$i] ?? 0), $data);
            $a0[$i] = ($mapping[$i] === 'benefit') ? max($vals) : min($vals);
        }

        $extendedData = array_merge(
            [['name' => 'A0 (Optimal)', 'values' => $a0]],
            $data
        );

        // 2. Transformasi Cost → 1/x (key = nama)
        $transformedMatrix = [];
        foreach ($extendedData as $row) {
            $name = $row['name'];
            foreach ($idx as $i) {
                $val = (float) ($row['values'][$i] ?? 0);
                $transformedMatrix[$name][$i] = ($mapping[$i] === 'cost')
                    ? 1 / ($val ?: 1)
                    : $val;
            }
        }

        // 3. Σ kolom (loop manual — array_column gagal pada asosiatif 2D)
        $totalPerColumn = [];
        foreach ($idx as $i) {
            $totalPerColumn[$i] = 0;
            foreach ($transformedMatrix as $row) {
                $totalPerColumn[$i] += $row[$i] ?? 0;
            }
        }

        // 4. Normalisasi (key = nama)
        $normMatrix = [];
        foreach ($transformedMatrix as $name => $values) {
            foreach ($idx as $i) {
                $normMatrix[$name][$i] = round(
                    $values[$i] / ($totalPerColumn[$i] ?: 1), 6
                );
            }
        }

        // 5. S_i
        $siValues = [];
        foreach ($normMatrix as $name => $values) {
            $s = 0;
            foreach ($idx as $i) {
                $s += ($weights[$i] ?? 0) * $values[$i];
            }
            $siValues[$name] = round($s, 6);
        }

        // 6. K_i = S_i / S_0
        $s0             = $siValues['A0 (Optimal)'] ?? 1;
        $finalProcessed = [];
        foreach ($data as $row) {
            $name           = $row['name'];
            $finalProcessed[] = [
                'name'  => $name,
                'si'    => $siValues[$name] ?? 0,
                'score' => round(($siValues[$name] ?? 0) / ($s0 ?: 1), 6),
            ];
        }

        $final = $this->sortResult($finalProcessed);

        return [
            'final' => $final,
            'steps' => [
                'original'           => $data,
                'criteria'           => $idx,
                'mapping'            => $mapping,
                'weights'            => $weights,
                'a0'                 => $a0,
                'all_rows'           => $extendedData,
                'sum_col'            => $totalPerColumn,
                'transformed_matrix' => $transformedMatrix,
                'norm_matrix'        => $normMatrix,
                'si_values'          => $siValues,
                's0'                 => $s0,
                'results'            => $final,
            ],
        ];
    }

    // =========================================================================
    // EDAS — Evaluation based on Distance from Average Solution
    // =========================================================================
    public function calculateEDAS(array $data, array $weights, array $mapping): array
    {
        if (empty($data)) return ['final' => [], 'steps' => []];

        $idx    = array_keys($weights);
        $numAlt = count($data);

        // 1. Average Solution
        $av = [];
        foreach ($idx as $i) {
            $vals  = array_map(fn($r) => (float) ($r['values'][$i] ?? 0), $data);
            $av[$i] = round(array_sum($vals) / $numAlt, 6);
        }

        // 2. PDA & NDA (key = nama)
        $pda = [];
        $nda = [];
        foreach ($data as $row) {
            $name = $row['name'];
            foreach ($idx as $i) {
                $x   = (float) ($row['values'][$i] ?? 0);
                $avg = $av[$i] ?: 1;
                if ($mapping[$i] === 'benefit') {
                    $pda[$name][$i] = round(max(0, $x - $avg) / $avg, 6);
                    $nda[$name][$i] = round(max(0, $avg - $x) / $avg, 6);
                } else {
                    $pda[$name][$i] = round(max(0, $avg - $x) / $avg, 6);
                    $nda[$name][$i] = round(max(0, $x - $avg) / $avg, 6);
                }
            }
        }

        // 3. SP & SN (key = nama)
        $sp = [];
        $sn = [];
        foreach ($data as $row) {
            $name      = $row['name'];
            $sp[$name] = 0;
            $sn[$name] = 0;
            foreach ($idx as $i) {
                $w          = $weights[$i] ?? 0;
                $sp[$name] += $w * $pda[$name][$i];
                $sn[$name] += $w * $nda[$name][$i];
            }
            $sp[$name] = round($sp[$name], 6);
            $sn[$name] = round($sn[$name], 6);
        }

        // 4. NSP & NSN
        $maxSP = max(array_values($sp)) ?: 1;
        $maxSN = max(array_values($sn)) ?: 1;
        $nsp   = [];
        $nsn   = [];
        foreach ($data as $row) {
            $name       = $row['name'];
            $nsp[$name] = round($sp[$name] / $maxSP, 6);
            $nsn[$name] = round(1 - $sn[$name] / $maxSN, 6);
        }

        // 5. Appraisal Score
        $finalProcessed = [];
        foreach ($data as $row) {
            $name           = $row['name'];
            $finalProcessed[] = [
                'name'  => $name,
                'nsp'   => $nsp[$name],
                'nsn'   => $nsn[$name],
                'score' => round(($nsp[$name] + $nsn[$name]) / 2, 6),
            ];
        }

        $final = $this->sortResult($finalProcessed);

        return [
            'final' => $final,
            'steps' => [
                'original'         => $data,
                'criteria'         => $idx,
                'mapping'          => $mapping,
                'weights'          => $weights,
                'n'                => $numAlt,
                'average_solution' => $av,
                'pda_matrix'       => $pda,
                'nda_matrix'       => $nda,
                'weighted_sums'    => ['sp' => $sp, 'sn' => $sn],
                'normalized_sums'  => ['nsp' => $nsp, 'nsn' => $nsn],
                'results'          => $final,
            ],
        ];
    }

    // =========================================================================
    // Helper
    // =========================================================================
    private function sortResult(array $data): array
    {
        usort($data, fn($a, $b) => $b['score'] <=> $a['score']);
        foreach ($data as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        return $data;
    }
}
