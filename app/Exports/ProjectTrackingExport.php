<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectTrackingExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    public function __construct(
        protected Collection $trackings,
        protected Project $project
    ) {}

    public function collection(): Collection
    {
        return $this->trackings;
    }

    public function headings(): array
    {
        $maxAttachments = $this->trackings
            ->map(fn ($tracking) => $tracking->attachments?->count() ?? 0)
            ->max();

        $headings = [
            'Date',
            'Title',
            'Description',
        ];

        for ($i = 1; $i <= $maxAttachments; $i++) {
            $headings[] = 'Attachment ' . $i;
        }

        return $headings;
    }

    public function map($tracking): array
    {
        $row = [
            $tracking->date?->format('d-m-Y'),
            $tracking->title,
            strip_tags($tracking->description ?? ''),
        ];

        $attachments = $tracking->attachments ?? collect();

        foreach ($attachments as $attachment) {
            $fileName =
                $attachment->filename
                ?? $attachment->file_name
                ?? $attachment->original_name
                ?? $attachment->name
                ?? 'Attachment';

            $url = route(
                'projects.trackings.attachments.download',
                [
                    'project' => $this->project->id,
                    'attachment' => $attachment->id,
                ]
            );

            // Make filename a clickable Excel hyperlink.
            $row[] = '=HYPERLINK("' .
                str_replace('"', '""', $url) .
                '","' .
                str_replace('"', '""', $fileName) .
                '")';
        }

        // Keep the number of columns consistent.
        $maxAttachments = $this->trackings
            ->map(fn ($item) => $item->attachments?->count() ?? 0)
            ->max();

        while (count($row) < 3 + $maxAttachments) {
            $row[] = '';
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header
        $sheet
            ->getStyle('A1:' . $highestColumn . '1')
            ->getFont()
            ->setBold(true);

        if ($highestRow >= 2) {
            $sheet
                ->getStyle('A1:' . $highestColumn . $highestRow)
                ->getAlignment()
                ->setVertical('top')
                ->setWrapText(true);

            // Style attachment columns as hyperlinks.
            $maxAttachments = $this->trackings
                ->map(fn ($tracking) => $tracking->attachments?->count() ?? 0)
                ->max();

            if ($maxAttachments > 0) {
                $attachmentStartColumn = 4; // Column D

                for ($i = 0; $i < $maxAttachments; $i++) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                        $attachmentStartColumn + $i
                    );

                    $sheet
                        ->getStyle($column . '2:' . $column . $highestRow)
                        ->getFont()
                        ->setUnderline(
                            \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE
                        )
                        ->getColor()
                        ->setARGB('FF0563C1');
                }
            }
        }

        return [];
    }
}
