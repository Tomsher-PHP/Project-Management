<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $projects,
        protected array $filters = []
    ) {}

    public function sheets(): array
    {
        return [
            new ProjectListSheet($this->projects, $this->filters),
            new ProjectTimelinesSheet($this->projects),
        ];
    }
}

