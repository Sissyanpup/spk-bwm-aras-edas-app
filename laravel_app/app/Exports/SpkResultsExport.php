<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SpkResultsExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $results;
    protected $method;

    public function __construct($results, $method = null)
    {
        $this->results = $results;
        $this->method = $method;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $exportData = [];

        // Jika method spesifik dipilih (misal hanya SAW)
        if ($this->method && isset($this->results[$this->method])) {
            foreach ($this->results[$this->method] as $res) {
                $exportData[] = [
                    'Rank' => $res['rank'],
                    'Alternatif' => $res['name'],
                    'Skor' => $res['score'],
                    'Metode' => strtoupper($this->method)
                ];
            }
        } else {
            // Jika export semua metode sekaligus
            foreach (['saw', 'aras', 'edas'] as $m) {
                if (isset($this->results[$m])) {
                    foreach ($this->results[$m] as $res) {
                        $exportData[] = [
                            'Rank' => $res['rank'],
                            'Alternatif' => $res['name'],
                            'Skor' => $res['score'],
                            'Metode' => strtoupper($m)
                        ];
                    }
                }
            }
        }

        return collect($exportData);
    }

    public function headings(): array
    {
        return ['Rank', 'Nama Alternatif', 'Skor Akhir', 'Metode'];
    }

    public function title(): string
    {
        return 'Hasil Perhitungan SPK';
    }
}