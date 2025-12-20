<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayrollExportFormat;
use App\Enums\Paie\PayrollVariableType;
use App\Models\Paie\PayrollSlip;
use App\Models\Paie\PayrollVariable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PayrollExportService
{
    public function generateCsv(PayrollSlip $slip, PayrollExportFormat $format = PayrollExportFormat::GenericCSV): string
    {
        $config = config("payroll.formats.{$format->value}");

        if (!$config) {
            throw new InvalidArgumentException("Le format d'export de paie '{$format->value}' n'est pas configuré.");
        }

        $delimiter = $config['delimiter'] ?? ';';
        $headers = $config['headers'] ?? [];
        $mapping = $config['mapping'] ?? [];
        $codeMapping = $config['code_mapping'] ?? [];

        $csvData = [];
        $csvData[] = $headers;

        foreach ($slip->variables as $variable) {
            $row = [];
            foreach ($headers as $header) {
                $source = $mapping[$header] ?? null;
                $row[] = $this->resolveValue($source, $slip, $variable, $codeMapping);
            }
            $csvData[] = $row;
        }

        return $this->createCsvString($csvData, $delimiter);
    }

    private function resolveValue(?string $source, PayrollSlip $slip, PayrollVariable $variable, array $codeMapping = [])
    {
        if ($source === null) {
            return '';
        }

        // Gérer les directives spéciales
        if (str_starts_with($source, '@')) {
            return $this->resolveDirective($source, $variable, $codeMapping);
        }

        // Gérer les formats de date
        if (str_contains($source, '|')) {
            [$source, $format] = explode('|', $source, 2);
            $value = $this->getValueFromPath($source, $slip, $variable);
            return $value instanceof \Carbon\Carbon ? $value->format($format) : $value;
        }

        return $this->getValueFromPath($source, $slip, $variable);
    }

    private function getValueFromPath(string $path, PayrollSlip $slip, PayrollVariable $variable)
    {
        $parts = explode('.', $path);
        $modelName = array_shift($parts);

        $model = match ($modelName) {
            'slip' => $slip,
            'variable' => $variable,
            'employee' => $slip->employee,
            default => null,
        };

        if (!$model) {
            return null;
        }

        return Arr::get($model, implode('.', $parts));
    }

    private function resolveDirective(string $directive, PayrollVariable $variable, array $codeMapping = []): mixed
    {
        return match ($directive) {
            '@quantity' => in_array($variable->type, [
                PayrollVariableType::StandardHour,
                PayrollVariableType::Overtime25,
                PayrollVariableType::Overtime50,
                PayrollVariableType::NightHour,
                PayrollVariableType::SundayHour,
                PayrollVariableType::Absence,
            ]) ? number_format($variable->quantity, 2, ',', '') : '0,00',

            '@amount' => !in_array($variable->type, [
                PayrollVariableType::StandardHour,
                PayrollVariableType::Overtime25,
                PayrollVariableType::Overtime50,
                PayrollVariableType::NightHour,
                PayrollVariableType::SundayHour,
                PayrollVariableType::Absence,
            ]) ? number_format($variable->quantity, 2, ',', '') : '0,00',

            '@mapped_code' => $codeMapping[$variable->type->value] ?? $variable->code,

            '@rate', '@base' => '', // Placeholder pour une logique future

            default => '',
        };
    }

    private function createCsvString(array $data, string $delimiter): string
    {
        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row, $delimiter, '"');
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    public function generateFileName(PayrollSlip $slip, PayrollExportFormat $format = PayrollExportFormat::GenericCSV): string
    {
        $employeeName = Str::slug($slip->employee->full_name);
        $period = Str::slug($slip->period->name);
        $formatSlug = Str::slug($format->value);

        return "export-paie_{$period}_{$employeeName}_{$formatSlug}_{$slip->id}.csv";
    }
}
