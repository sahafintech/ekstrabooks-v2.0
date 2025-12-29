<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

final class SupplierImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsFailures;

    /**
     * Field mappings from Excel header to system field
     * Format: ['Excel Header' => 'system_field']
     */
    private array $mappings;

    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    /**
     * Transform row data using field mappings
     */
    private function mapRowData(array $row): array
    {
        $mappedData = [];
        
        foreach ($row as $header => $value) {
            $normalizedHeader = $this->normalizeHeader($header);
            
            foreach ($this->mappings as $excelHeader => $systemField) {
                $normalizedExcelHeader = $this->normalizeHeader($excelHeader);
                if ($normalizedHeader === $normalizedExcelHeader && $systemField !== 'skip') {
                    $mappedData[$systemField] = $value;
                    break;
                }
            }
        }
        
        return $mappedData;
    }

    /**
     * Normalize header for comparison
     */
    private function normalizeHeader(string $header): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', trim($header)));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): ?Vendor
    {
        // Apply field mappings
        $data = $this->mapRowData($row);
        
        // Skip empty rows
        if (empty($data['name'])) {
            return null;
        }

        $vendorData = [
            'name' => !empty($data['name']) ? trim((string) $data['name']) : '',
            'company_name' => !empty($data['company_name']) ? trim((string) $data['company_name']) : null,
            'email' => !empty($data['email']) ? trim((string) $data['email']) : null,
            'mobile' => !empty($data['mobile']) ? trim((string) $data['mobile']) : null,
            'country' => !empty($data['country']) ? trim((string) $data['country']) : null,
            'vat_id' => !empty($data['vat_id']) ? trim((string) $data['vat_id']) : null,
            'registration_no' => !empty($data['registration_no']) ? trim((string) $data['registration_no']) : null,
            'city' => !empty($data['city']) ? trim((string) $data['city']) : null,
            'contract_no' => !empty($data['contract_no']) ? trim((string) $data['contract_no']) : null,
            'address' => !empty($data['address']) ? trim((string) $data['address']) : null,
            'profile_picture' => !empty($data['profile_picture']) ? (string) $data['profile_picture'] : 'default.png',
        ];

        // Check if ID is provided for update
        if (!empty($data['id'])) {
            $vendor = Vendor::find($data['id']);
            if ($vendor) {
                $vendor->update($vendorData);
                return null; // Return null since we've already updated
            }
        }

        // Check if vendor with same email already exists (for updates)
        if (!empty($data['email'])) {
            $existingVendor = Vendor::where('email', $data['email'])->first();
            if ($existingVendor) {
                $existingVendor->update($vendorData);
                return null; // Return null since we've already updated
            }
        }

        // Create new vendor
        $vendor = new Vendor($vendorData);
        $vendor->save();
        
        return null; // Return null since we've already saved manually
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
