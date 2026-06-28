<?php

namespace App\Enums;

enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Xlsx = 'xlsx';
    case Csv = 'csv';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
