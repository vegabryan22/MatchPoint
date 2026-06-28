<?php

namespace App\Services\Reports;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\Filesystem;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

final class ReportExportService
{
    public function __construct(private readonly ReportDataService $data, private readonly Filesystem $files) {}

    public function export(ReportType $type, ReportFormat $format, array $filters): array
    {
        $data = $this->data->build($type, $filters);
        $directory = storage_path('app/tmp/reports');
        $this->files->ensureDirectoryExists($directory);
        $filename = $type->value.'-'.now()->format('Ymd-His').'.'.$format->value;
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        match ($format) {
            ReportFormat::Pdf => Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape')->save($path),
            ReportFormat::Xlsx => $this->xlsx($path, $data),
            ReportFormat::Csv => $this->csv($path, $data),
        };

        return compact('path', 'filename');
    }

    private function xlsx(string $path, array $data): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($data['headers']));
        foreach ($data['rows'] as $row) {
            $writer->addRow(Row::fromValues($row));
        } $writer->close();
    }

    private function csv(string $path, array $data): void
    {
        $stream = fopen($path, 'wb');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $data['headers']);
        foreach ($data['rows'] as $row) {
            fputcsv($stream, $row);
        } fclose($stream);
    }
}
