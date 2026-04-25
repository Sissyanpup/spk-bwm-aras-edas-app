<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithLimit;
use Illuminate\Support\Collection;

class CommisionImport implements ToCollection, WithLimit
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }

    // Batasi hanya baca 101 baris (1 header + 100 data)
    public function limit(): int
    {
        return 101;
    }
}