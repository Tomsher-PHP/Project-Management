<?php

namespace App\Imports;

use App\Models\AppraisalQuestionImport;
use App\Services\AppraisalSettingsService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AppraisalQuestionsImport implements WithMultipleSheets
{
    public function __construct(
        private readonly AppraisalQuestionImport $import,
        private readonly AppraisalSettingsService $service
    ) {}

    public function sheets(): array
    {
        return [
            'Questions Upload' => new AppraisalQuestionsSheetImport($this->import, $this->service),
        ];
    }
}

class AppraisalQuestionsSheetImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $currentRow = 1;

    public function __construct(
        private readonly AppraisalQuestionImport $import,
        private readonly AppraisalSettingsService $service
    ) {}

    public function collection(Collection $rows): void
    {
        $startRow = $this->currentRow + 1;
        $this->service->processQuestionChunk($this->import, $rows->toArray(), $startRow);
        $this->currentRow += $rows->count();
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
