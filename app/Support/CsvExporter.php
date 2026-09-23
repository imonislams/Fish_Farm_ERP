<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CsvExporter — one CSV implementation for the whole ERP.
 *
 * WHY IT EXISTS
 *   CSV export was required repeatedly and did not exist. Every report and
 *   meaningful list must be able to export its REAL rows (never sample data).
 *   One implementation means one set of conventions:
 *
 *     - UTF-8 **with BOM**, so Excel opens Bangla text and "৳" correctly.
 *     - CRLF line endings (RFC 4180 / Excel-friendly).
 *     - Streamed, so a large export never loads the whole result set into memory.
 *     - A meaningful, dated filename:  ponds-2026-09-26.csv
 *     - Formula-injection protection: a leading =, +, -, @ in a text cell is
 *       neutralised, because Excel would otherwise execute it.
 *
 * USAGE (in a controller):
 *
 *   return CsvExporter::download('ponds', ['Pond No', 'Name'], $rows);
 *
 * where $rows is any iterable of arrays in the same order as the headings.
 */
final class CsvExporter
{
    /**
     * Build a streamed CSV download response.
     *
     * @param  string  $basename  filename WITHOUT extension or date
     * @param  array<int, string>  $headings  column headings (first row)
     * @param  iterable<int, array<int, mixed>>  $rows  rows in heading order
     */
    public static function download(string $basename, array $headings, iterable $rows): StreamedResponse
    {
        $filename = self::filename($basename);

        return response()->streamDownload(function () use ($headings, $rows): void {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — without this, Excel mis-decodes Bengali text and ৳.
            fwrite($out, "\xEF\xBB\xBF");

            // Headings are our own strings, so they are written verbatim.
            fputcsv($out, $headings, ',', '"', '\\');

            foreach ($rows as $row) {
                $safe = [];

                foreach ($row as $cell) {
                    $safe[] = self::sanitise($cell);
                }

                fputcsv($out, $safe, ',', '"', '\\');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Neutralise a cell for CSV.
     *
     * A leading =, +, -, @ (or tab/CR) makes Excel treat the cell as a formula.
     * Text that legitimately starts with those is prefixed with a single quote,
     * which Excel strips on display but still renders as text.
     */
    public static function sanitise(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $string = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $string) === 1) {
            return "'" . $string;
        }

        return $string;
    }

    /** A meaningful, dated, filesystem-safe filename. */
    public static function filename(string $basename): string
    {
        $slug = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $basename) ?: 'export';
        $slug = trim($slug, '-');

        return $slug . '-' . now()->format('Y-m-d') . '.csv';
    }
}
