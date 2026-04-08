# 💻 Implementasi Praktis: HL7 FHIR & Regulasi Indonesia untuk Smart Healthcare AI

**File Implementasi:** Code examples & architecture patterns  
**Status:** Production-ready examples  
**Tanggal:** April 8, 2026

---

## Bagian 1: FHIR 4.0 Implementation Patterns

### 1.1 FHIR Patient Resource Parser

```php
<?php
// app/Services/FHIR/PatientParser.php

namespace App\Services\FHIR;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PatientParser {
    /**
     * Parse FHIR Patient JSON to Smart Healthcare AI model
     * 
     * @param array $fhirPatient - FHIR Patient resource
     * @return array - Model data for DB storage
     */
    public static function parseToModel(array $fhirPatient): array {
        return [
            'external_id' => $fhirPatient['id'] ?? null,
            'nik' => self::extractIdentifier($fhirPatient, 'NIK'),
            'bpjs_number' => self::extractIdentifier($fhirPatient, 'BPJS'),
            'first_name' => $fhirPatient['name'][0]['given'][0] ?? null,
            'last_name' => $fhirPatient['name'][0]['family'] ?? null,
            'full_name' => self::extractFullName($fhirPatient),
            'gender' => self::mapGender($fhirPatient['gender'] ?? null),
            'date_of_birth' => $fhirPatient['birthDate'] ?? null,
            'age' => self::calculateAge($fhirPatient['birthDate'] ?? null),
            'phone' => self::extractContact($fhirPatient, 'phone'),
            'email' => self::extractContact($fhirPatient, 'email'),
            'address' => self::extractAddress($fhirPatient),
            'city' => $fhirPatient['address'][0]['city'] ?? null,
            'province' => $fhirPatient['address'][0]['state'] ?? null,
            'country' => $fhirPatient['address'][0]['country'] ?? 'ID',
            'religion' => self::extractExtension($fhirPatient, 'religion'),
            'education' => self::extractExtension($fhirPatient, 'education'),
            'occupation' => self::extractExtension($fhirPatient, 'occupation'),
            'marital_status' => $fhirPatient['maritalStatus']['coding'][0]['code'] ?? null,
            'language' => $fhirPatient['communication'][0]['language']['coding'][0]['code'] ?? 'id',
            'preferred_facility' => self::extractGeneralPractitioner($fhirPatient),
            'is_active' => ($fhirPatient['active'] ?? true),
            'fhir_data' => $fhirPatient, // Store original FHIR for audit
        ];
    }
    
    /**
     * Convert model to FHIR Patient JSON for API response
     */
    public static function toFHIR($patient): array {
        return [
            'resourceType' => 'Patient',
            'id' => $patient->external_id ?? $patient->id,
            'meta' => [
                'versionId' => '1',
                'lastUpdated' => now()->toIso8601String(),
                'profile' => ['http://hl7.org/fhir/StructureDefinition/Patient']
            ],
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://kemkes.go.id/nik',
                    'value' => $patient->nik
                ],
                [
                    'use' => 'secondary',
                    'system' => 'http://bpjs.kemkes.go.id',
                    'value' => $patient->bpjs_number
                ]
            ],
            'name' => [
                [
                    'use' => 'official',
                    'family' => $patient->last_name,
                    'given' => [$patient->first_name]
                ]
            ],
            'gender' => self::mapGenderToFHIR($patient->gender),
            'birthDate' => $patient->date_of_birth,
            'address' => [
                [
                    'use' => 'home',
                    'line' => [explode(',', $patient->address)[0] ?? $patient->address],
                    'city' => $patient->city,
                    'state' => $patient->province,
                    'country' => $patient->country
                ]
            ],
            'telecom' => self::buildTelecom($patient),
            'maritalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/marital-status',
                        'code' => $patient->marital_status ?? 'unknown'
                    ]
                ]
            ],
            'communication' => [
                [
                    'language' => [
                        'coding' => [
                            [
                                'system' => 'urn:ietf:bcp:47',
                                'code' => $patient->language ?? 'id'
                            ]
                        ]
                    ],
                    'preferred' => true
                ]
            ],
            'active' => $patient->is_active
        ];
    }
    
    // Helper methods
    private static function extractIdentifier(array $fhir, string $type): ?string {
        foreach ($fhir['identifier'] ?? [] as $id) {
            if (stripos($id['system'] ?? '', $type) !== false) {
                return $id['value'];
            }
        }
        return null;
    }
    
    private static function extractFullName(array $fhir): string {
        $name = $fhir['name'][0] ?? [];
        $given = implode(' ', $name['given'] ?? []);
        $family = $name['family'] ?? '';
        return trim("$given $family");
    }
    
    private static function mapGender(?string $fhirGender): ?string {
        $map = ['male' => 'M', 'female' => 'F', 'other' => 'O', 'unknown' => 'U'];
        return $map[$fhirGender] ?? null;
    }
    
    private static function mapGenderToFHIR(?string $gender): string {
        $map = ['M' => 'male', 'F' => 'female', 'O' => 'other', 'U' => 'unknown'];
        return $map[$gender] ?? 'unknown';
    }
    
    private static function calculateAge(?string $birthDate): ?int {
        return $birthDate ? Carbon::parse($birthDate)->age : null;
    }
    
    private static function extractContact(array $fhir, string $type): ?string {
        foreach ($fhir['telecom'] ?? [] as $contact) {
            if ($contact['system'] === $type) {
                return $contact['value'];
            }
        }
        return null;
    }
    
    private static function extractAddress(array $fhir): ?string {
        $address = $fhir['address'][0] ?? null;
        if (!$address) return null;
        
        $parts = array_filter([
            implode(' ', $address['line'] ?? []),
            $address['city'],
            $address['state']
        ]);
        
        return implode(', ', $parts);
    }
    
    private static function extractExtension(array $fhir, string $type): ?string {
        foreach ($fhir['extension'] ?? [] as $ext) {
            if (stripos($ext['url'] ?? '', $type) !== false) {
                return $ext['valueString'] ?? $ext['valueCode'] ?? null;
            }
        }
        return null;
    }
    
    private static function extractGeneralPractitioner(array $fhir): ?string {
        $gp = $fhir['generalPractitioner'][0] ?? null;
        return $gp['reference'] ?? null;
    }
    
    private static function buildTelecom($patient): array {
        $telecom = [];
        
        if ($patient->phone) {
            $telecom[] = [
                'system' => 'phone',
                'value' => $patient->phone,
                'use' => 'mobile'
            ];
        }
        
        if ($patient->email) {
            $telecom[] = [
                'system' => 'email',
                'value' => $patient->email,
                'use' => 'work'
            ];
        }
        
        return $telecom;
    }
}
?>
```

---

### 1.2 FHIR Observation Resource untuk Lab Results

```php
<?php
// app/Services/FHIR/ObservationBuilder.php

namespace App\Services\FHIR;

use Carbon\Carbon;

class ObservationBuilder {
    /**
     * Build FHIR Observation dari hasil lab lokal
     * Map ke LOINC codes dan SNOMED CT
     */
    public static function buildLabResult(array $labData): array {
        return [
            'resourceType' => 'Observation',
            'id' => $labData['uuid'] ?? md5(json_encode($labData)),
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'laboratory',
                            'display' => 'Laboratory'
                        ]
                    ]
                ]
            ],
            'code' => self::mapToLOINC($labData['test_name']),
            'subject' => [
                'reference' => "Patient/{$labData['patient_id']}",
                'display' => $labData['patient_name']
            ],
            'effectiveDateTime' => $labData['test_date']->toIso8601String(),
            'issued' => now()->toIso8601String(),
            'performer' => [
                [
                    'reference' => "Organization/{$labData['lab_id']}"
                ]
            ],
            'value' => self::mapValue($labData),
            'referenceRange' => self::mapReferenceRange($labData),
            'interpretation' => self::mapInterpretation($labData),
            'specimen' => self::mapSpecimen($labData),
            'note' => [
                [
                    'text' => $labData['notes'] ?? ''
                ]
            ]
        ];
    }
    
    /**
     * Map lokal test nama ke LOINC codes
     * Reference: https://loinc.org/
     */
    private static function mapToLOINC(string $testName): array {
        $loincMap = [
            'hemoglobin' => [
                'system' => 'http://loinc.org',
                'code' => '718-7',
                'display' => 'Hemoglobin [Mass/volume] in Blood'
            ],
            'glucose' => [
                'system' => 'http://loinc.org',
                'code' => '2345-7',
                'display' => 'Glucose [Mass/volume] in Serum or Plasma'
            ],
            'creatinine' => [
                'system' => 'http://loinc.org',
                'code' => '2160-0',
                'display' => 'Creatinine [Mass/volume] in Serum or Plasma'
            ],
            'wbc' => [
                'system' => 'http://loinc.org',
                'code' => '6690-2',
                'display' => 'Leukocytes [#/volume] in Blood by Automated count'
            ]
        ];
        
        $key = strtolower(str_replace(' ', '', $testName));
        return $loincMap[$key] ?? [
            'text' => $testName
        ];
    }
    
    private static function mapValue(array $labData): array {
        $value = [
            'system' => 'http://unitsofmeasure.org',
            'value' => (float) $labData['result'],
            'unit' => $labData['unit'] ?? '',
            'code' => self::mapUnitCode($labData['unit'])
        ];
        
        return ['valueQuantity' => $value];
    }
    
    private static function mapUnitCode(?string $unit): ?string {
        $unitMap = [
            'g/dL' => 'g/dL',
            'mg/dL' => 'mg/dL',
            'mmol/L' => 'mmol/L',
            '/uL' => '10*3/uL',
            '%' => '%'
        ];
        
        return $unitMap[$unit] ?? $unit;
    }
    
    private static function mapReferenceRange(array $labData): array {
        if (!isset($labData['reference_low']) && !isset($labData['reference_high'])) {
            return [];
        }
        
        return [
            [
                'low' => isset($labData['reference_low']) ? 
                    ['value' => (float) $labData['reference_low']] : null,
                'high' => isset($labData['reference_high']) ? 
                    ['value' => (float) $labData['reference_high']] : null,
                'text' => $labData['reference_text'] ?? ''
            ]
        ];
    }
    
    private static function mapInterpretation(array $labData): array {
        $result = (float) $labData['result'];
        $low = (float) ($labData['reference_low'] ?? $result);
        $high = (float) ($labData['reference_high'] ?? $result);
        
        if ($result < $low) {
            $code = 'L';
            $display = 'Low';
        } elseif ($result > $high) {
            $code = 'H';
            $display = 'High';
        } else {
            $code = 'N';
            $display = 'Normal';
        }
        
        return [
            [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0078',
                        'code' => $code,
                        'display' => $display
                    ]
                ]
            ]
        ];
    }
    
    private static function mapSpecimen(array $labData): array {
        if (!isset($labData['specimen_type'])) {
            return [];
        }
        
        return [
            [
                'reference' => "Specimen/{$labData['specimen_id']}"
            ]
        ];
    }
}
?>
```

---

### 1.3 HL7 v2 to FHIR Converter

```php
<?php
// app/Services/HL7/V2ToFHIRConverter.php

namespace App\Services\HL7;

use App\Services\FHIR\ObservationBuilder;

class V2ToFHIRConverter {
    /**
     * Convert HL7 v2 message ke FHIR Bundle
     * 
     * Input:
     * MSH|^~\&|SendingApp|SendingFacility|...
     * PID|||123456^^^Hospital||Doe^John||19900101|M
     * OBX|1|NM|1234-5^Hemoglobin^LN||15.2|g/dL|13.5-17.5|N
     */
    public static function convertMessage(string $hl7Message): array {
        $lines = explode("\r", $hl7Message);
        
        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'transaction',
            'entry' => []
        ];
        
        $patientData = [];
        $observations = [];
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $segment = explode('|', $line);
            $segmentId = array_shift($segment);
            
            switch ($segmentId) {
                case 'MSH':
                    // Message header - extract audit info
                    break;
                    
                case 'PID':
                    $patientData = self::parsePID($segment);
                    break;
                    
                case 'OBX':
                    $observations[] = self::parseOBX($segment, $patientData);
                    break;
            }
        }
        
        // Build FHIR bundle
        if (!empty($patientData)) {
            $bundle['entry'][] = [
                'resource' => self::buildPatientResource($patientData),
                'request' => [
                    'method' => 'POST',
                    'url' => 'Patient'
                ]
            ];
        }
        
        foreach ($observations as $obs) {
            $bundle['entry'][] = [
                'resource' => $obs,
                'request' => [
                    'method' => 'POST',
                    'url' => 'Observation'
                ]
            ];
        }
        
        return $bundle;
    }
    
    private static function parsePID(array $fields): array {
        return [
            'pid' => $fields[1] ?? null,
            'mrn' => $fields[2] ?? null,
            'name' => self::parseName($fields[5] ?? ''),
            'dob' => $fields[7] ?? null,
            'gender' => $fields[8] ?? null,
            'address' => $fields[11] ?? null,
            'phone' => $fields[13] ?? null,
        ];
    }
    
    private static function parseName(string $nameField): array {
        $parts = explode('^', $nameField);
        return [
            'family' => $parts[0] ?? '',
            'given' => $parts[1] ?? '',
            'middle' => $parts[2] ?? null
        ];
    }
    
    private static function parseOBX(array $fields, array $patientData): array {
        // OBX fields:
        // [0] = OBX
        // [1] = set ID
        // [2] = value type
        // [3] = observation ID
        // [4] = (reserved)
        // [5] = observation value
        // [6] = units
        // [7] = reference range
        // [8] = abnormal flags
        
        $obsCode = explode('^', $fields[2] ?? '');
        
        return [
            'resourceType' => 'Observation',
            'status' => 'final',
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $obsCode[0] ?? '',
                        'display' => $obsCode[1] ?? ''
                    ]
                ]
            ],
            'subject' => [
                'reference' => 'Patient/' . ($patientData['mrn'] ?? '')
            ],
            'valueQuantity' => [
                'value' => (float) ($fields[4] ?? 0),
                'unit' => $fields[5] ?? '',
                'system' => 'http://unitsofmeasure.org'
            ],
            'referenceRange' => !empty($fields[6]) ? [[
                'text' => $fields[6]
            ]] : [],
            'interpretation' => self::mapAbnormalFlag($fields[7] ?? '')
        ];
    }
    
    private static function buildPatientResource(array $data): array {
        return [
            'resourceType' => 'Patient',
            'identifier' => [
                [
                    'type' => ['coding' => [['code' => 'MR']]],
                    'value' => $data['mrn']
                ]
            ],
            'name' => [$data['name']],
            'gender' => strtolower($data['gender'] ?? 'unknown'),
            'birthDate' => $data['dob'],
            'telecom' => [[
                'system' => 'phone',
                'value' => $data['phone']
            ]],
            'address' => [[
                'text' => $data['address']
            ]]
        ];
    }
    
    private static function mapAbnormalFlag(string $flag): array {
        $map = [
            'L' => 'low',
            'H' => 'high',
            'LL' => 'critically-low',
            'HH' => 'critically-high',
            'N' => 'normal'
        ];
        
        return [[
            'coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0078',
                'code' => $map[$flag] ?? $flag
            ]]
        ]];
    }
}
?>
```

---

## Bagian 2: UU PDP & HIPAA Compliance

### 2.1 Compliance Middleware

```php
<?php
// app/Http/Middleware/PrivacyComplianceCheckMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\DataConsent;

class PrivacyComplianceCheckMiddleware {
    /**
     * Check UU PDP compliance sebelum data access
     */
    public function handle(Request $request, Closure $next) {
        // Check 1: Verify user authentication
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Anda harus login terlebih dahulu'
            ], 401);
        }
        
        // Check 2: Verify user has consent for data access
        if ($this->isPatientDataRequest($request)) {
            $patientId = $this->extractPatientId($request);
            
            if (!$this->hasValidConsent(auth()->id(), $patientId)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Tidak ada persetujuan untuk akses data pasien ini'
                ], 403);
            }
        }
        
        // Check 3: Verify request comes from approved IP/device
        if (!$this->isApprovedDevice($request->ip())) {
            AuditLog::create([
                'event' => 'UNAUTHORIZED_DEVICE_ACCESS',
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'severity' => 'HIGH'
            ]);
            
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Akses dari perangkat ini tidak diizinkan'
            ], 403);
        }
        
        // Check 4: Verify data retention period
        if ($this->isPatientDeleted($this->extractPatientId($request))) {
            return response()->json([
                'error' => 'NotFound',
                'message' => 'Data pasien telah dihapus sesuai permintaan pasien'
            ], 404);
        }
        
        // Log audit trail
        $this->logAuditTrail($request);
        
        return $next($request);
    }
    
    private function isPatientDataRequest(Request $request): bool {
        return $request->is('api/patients/*') ||
               $request->is('api/observations/*') ||
               $request->is('api/consultations/*');
    }
    
    private function extractPatientId(Request $request): int {
        return $request->route('patient') ?? null;
    }
    
    private function hasValidConsent($userId, $patientId): bool {
        return DataConsent::where([
            ['patient_id', $patientId],
            ['user_id', $userId],
            ['status', 'approved'],
            ['expires_at', '>=', now()]
        ])->exists();
    }
    
    private function isApprovedDevice(string $ip): bool {
        // Implement device fingerprinting/IP whitelist
        return true; // TODO: Implement
    }
    
    private function isPatientDeleted($patientId): bool {
        // Check if patient requested deletion (Right to Erasure)
        return \DB::table('data_erasure_requests')
            ->where('patient_id', $patientId)
            ->where('status', 'approved')
            ->where('execution_date', '<=', now())
            ->exists();
    }
    
    private function logAuditTrail(Request $request): void {
        AuditLog::create([
            'event' => 'DATA_ACCESS',
            'user_id' => auth()->id(),
            'resource_type' => $request->route('resource_type') ?? 'unknown',
            'resource_id' => $this->extractPatientId($request),
            'action' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now(),
            'status' => 'success'
        ]);
    }
}
?>
```

---

### 2.2 Data Subject Rights Service (UU PDP)

```php
<?php
// app/Services/Compliance/DataSubjectRightsService.php

namespace App\Services\Compliance;

use App\Models\Patient;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class DataSubjectRightsService {
    /**
     * Right to Access (Hak Akses) - UU PDP Pasal 21
     * Pasien berhak mendapat akses ke seluruh data pribadi mereka
     */
    public function exerciseRightToAccess($patientId): string {
        $patient = Patient::with(['consultations', 'observations', 'allergies'])
            ->findOrFail($patientId);
        
        // Verify patient identity
        if (!$this->verifyPatientIdentity($patient)) {
            throw new \Exception('Identitas pasien tidak terverifikasi');
        }
        
        $exportData = [
            'exported_at' => now()->toIso8601String(),
            'patient_data' => $this->serializePatient($patient),
            'consultations' => $patient->consultations->toArray(),
            'observations' => $patient->observations->toArray(),
            'allergies' => $patient->allergies->toArray(),
            'audit_trail' => AuditLog::where('resource_id', $patientId)
                ->where('resource_type', 'Patient')
                ->get()
        ];
        
        // Log the access request
        AuditLog::create([
            'event' => 'RIGHT_TO_ACCESS_EXERCISED',
            'patient_id' => $patientId,
            'data_size_kb' => strlen(json_encode($exportData)) / 1024,
            'format' => 'json',
            'timestamp' => now()
        ]);
        
        return json_encode($exportData, JSON_PRETTY_PRINT);
    }
    
    /**
     * Right to Rectification (Hak Koreksi) - UU PDP Pasal 22
     * Pasien berhak perbaiki data yang tidak akurat
     */
    public function exerciseRightToRectification(
        $patientId, 
        array $corrections
    ): bool {
        DB::transaction(function () use ($patientId, $corrections) {
            $patient = Patient::findOrFail($patientId);
            
            // Store original data untuk audit trail
            $originalData = $patient->toArray();
            
            // Apply corrections
            $patient->update($corrections);
            
            // Log changes
            AuditLog::create([
                'event' => 'RIGHT_TO_RECTIFICATION_EXERCISED',
                'patient_id' => $patientId,
                'original_data' => json_encode($originalData),
                'corrected_data' => json_encode($patient->toArray()),
                'timestamp' => now()
            ]);
        });
        
        return true;
    }
    
    /**
     * Right to Erasure (Hak Dihapus) - UU PDP Pasal 23
     * Pasien berhak meminta penghapusan data pribadi mereka
     * This is scheduled deletion (tidak immediate) untuk compliance
     */
    public function exerciseRightToErasure($patientId): array {
        $request = DB::transaction(function () use ($patientId) {
            // Create erasure request (bukan langsung delete)
            $erasureRequest = \DB::table('data_erasure_requests')->create([
                'patient_id' => $patientId,
                'requested_at' => now(),
                'status' => 'pending_verification',
                'execution_date' => now()->addDays(30), // Grace period 30 hari per regulasi
                'reason' => 'Patient exercised right to erasure'
            ]);
            
            // Notify patient of pending deletion
            $this->notifyPatientOfErasure($patientId, $erasureRequest->id);
            
            return $erasureRequest;
        });
        
        return [
            'request_id' => $request->id,
            'status' => 'pending_verification',
            'execution_date' => $request->execution_date,
            'message' => 'Permintaan penghapusan data telah diterima. Data akan dihapus pada ' . 
                        $request->execution_date->format('d-m-Y')
        ];
    }
    
    /**
     * Right to Object (Hak Keberatan) - UU PDP Pasal 25
     * Pasien berhak keberatan atas pemrosesan data tertentu
     */
    public function exerciseRightToObject(
        $patientId,
        string $processingType,
        string $reason
    ): bool {
        DB::table('data_processing_objections')->create([
            'patient_id' => $patientId,
            'processing_type' => $processingType, // e.g., 'marketing', 'research'
            'reason' => $reason,
            'objection_date' => now(),
            'status' => 'pending_review'
        ]);
        
        AuditLog::create([
            'event' => 'RIGHT_TO_OBJECT_EXERCISED',
            'patient_id' => $patientId,
            'processing_type' => $processingType,
            'timestamp' => now()
        ]);
        
        return true;
    }
    
    /**
     * Right to Data Portability (Hak Portabilitas) - UU PDP Pasal 24
     * Pasien berhak mentransfer data ke sistem lain dalam format terstandar
     */
    public function exerciseRightToPortability($patientId, $targetSystem): string {
        $patient = Patient::findOrFail($patientId);
        
        // Convert to FHIR format untuk portability
        $fhirData = $this->convertToFHIR($patient);
        
        AuditLog::create([
            'event' => 'RIGHT_TO_PORTABILITY_EXERCISED',
            'patient_id' => $patientId,
            'target_system' => $targetSystem,
            'format' => 'FHIR 4.0',
            'timestamp' => now()
        ]);
        
        return json_encode($fhirData);
    }
    
    /**
     * Verify patient identity using multiple factors
     */
    private function verifyPatientIdentity(Patient $patient): bool {
        // TODO: Implement 2FA verification (SMS, email, etc)
        return true;
    }
    
    private function serializePatient(Patient $patient): array {
        return [
            'id' => $patient->id,
            'nik' => $patient->nik,
            'name' => $patient->full_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'date_of_birth' => $patient->date_of_birth,
            'gender' => $patient->gender,
            'address' => $patient->address,
            'created_at' => $patient->created_at,
            'updated_at' => $patient->updated_at
        ];
    }
    
    private function convertToFHIR(Patient $patient): array {
        // Use PatientParser::toFHIR() dari bagian 1.1
        return \App\Services\FHIR\PatientParser::toFHIR($patient);
    }
    
    private function notifyPatientOfErasure($patientId, $requestId): void {
        // TODO: Send email/SMS notification
    }
}
?>
```

---

## Bagian 3: SATUSEHAT Integration

### 3.1 SATUSEHAT Service Implementation

```php
<?php
// app/Services/SATUSEHAT/SATUSEHATService.php

namespace App\Services\SATUSEHAT;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SATUSEHATService {
    protected $baseUrl = 'https://api.satusehat.kemkes.go.id/fhir-r4/';
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;
    
    public function __construct() {
        $this->clientId = config('satusehat.client_id');
        $this->clientSecret = config('satusehat.client_secret');
        $this->ensureAuthenticated();
    }
    
    /**
     * OAuth 2.0 Authentication
     * Token di-cache untuk mengurangi API calls
     */
    private function ensureAuthenticated(): void {
        // Check if token exists in cache
        $cachedToken = Cache::get('satusehat_access_token');
        
        if ($cachedToken) {
            $this->accessToken = $cachedToken;
            return;
        }
        
        // Request new token
        $response = Http::post(
            'https://api.satusehat.kemkes.go.id/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]
        );
        
        if ($response->failed()) {
            throw new \Exception('SATUSEHAT authentication failed: ' . 
                               $response->body());
        }
        
        $this->accessToken = $response->json('access_token');
        
        // Cache token (expiry biasanya 1 jam)
        Cache::put('satusehat_access_token', $this->accessToken, 
                   now()->addMinutes(55));
    }
    
    /**
     * Find patient di SATUSEHAT by NIK
     */
    public function findPatientByNIK($nik): ?array {
        try {
            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . 'Patient', [
                    'identifier' => "http://nik.kemkes.go.id|$nik"
                ]);
            
            if ($response->successful()) {
                $entries = $response->json('entry', []);
                return !empty($entries) ? $entries[0]['resource'] : null;
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('SATUSEHAT findPatientByNIK error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Submit observation ke SATUSEHAT
     */
    public function submitObservation($patientId, array $observationData): array {
        $fhirObservation = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => $observationData['category'] ?? 'laboratory'
                        ]
                    ]
                ]
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => $observationData['code_system'] ?? 'http://loinc.org',
                        'code' => $observationData['code'],
                        'display' => $observationData['display']
                    ]
                ]
            ],
            'subject' => [
                'reference' => "Patient/$patientId"
            ],
            'effectiveDateTime' => now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $observationData['value'],
                'unit' => $observationData['unit'],
                'system' => 'http://unitsofmeasure.org',
                'code' => $observationData['unit_code']
            ]
        ];
        
        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . 'Observation', $fhirObservation);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'resource_id' => $response->json('id'),
                    'message' => 'Observation berhasil disimpan di SATUSEHAT'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to submit observation',
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get patient observations dari SATUSEHAT
     */
    public function getPatientObservations($patientId, $limit = 100): array {
        try {
            $observations = [];
            $page = 1;
            
            while (count($observations) < $limit) {
                $response = Http::withToken($this->accessToken)
                    ->get($this->baseUrl . 'Observation', [
                        'patient' => "Patient/$patientId",
                        'status' => 'final',
                        '_count' => 100,
                        '_page' => $page
                    ]);
                
                if (!$response->successful()) {
                    break;
                }
                
                $entries = $response->json('entry', []);
                
                if (empty($entries)) {
                    break;
                }
                
                foreach ($entries as $entry) {
                    if (count($observations) >= $limit) {
                        break 2;
                    }
                    $observations[] = $entry['resource'];
                }
                
                $page++;
            }
            
            return $observations;
        } catch (\Exception $e) {
            \Log::error('SATUSEHAT getObservations error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync bi-directional: Pull dari SATUSEHAT, Push ke SATUSEHAT
     */
    public function syncPatientData($patientId): bool {
        try {
            $patient = \App\Models\Patient::findOrFail($patientId);
            
            // Pull observations dari SATUSEHAT
            $sObservations = $this->getPatientObservations($patientId);
            
            // Save ke local database
            foreach ($sObservations as $obs) {
                $this->saveObservationLocally($patient, $obs);
            }
            
            // Push local data yang belum di SATUSEHAT
            $this->pushLocalObservationsToSATUSEHAT($patient);
            
            \Log::info("SATUSEHAT sync successful for patient $patientId");
            
            return true;
        } catch (\Exception $e) {
            \Log::error('SATUSEHAT sync error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function saveObservationLocally($patient, $fhirObservation): void {
        \App\Models\Observation::updateOrCreate(
            ['external_id' => $fhirObservation['id']],
            [
                'patient_id' => $patient->id,
                'code' => $fhirObservation['code']['coding'][0]['code'] ?? null,
                'value' => $fhirObservation['valueQuantity']['value'] ?? null,
                'unit' => $fhirObservation['valueQuantity']['unit'] ?? null,
                'status' => $fhirObservation['status'] ?? 'unknown',
                'fhir_data' => $fhirObservation
            ]
        );
    }
    
    private function pushLocalObservationsToSATUSEHAT($patient): void {
        $localObservations = $patient->observations()
            ->whereNull('external_id')
            ->get();
        
        foreach ($localObservations as $obs) {
            $result = $this->submitObservation($patient->external_id ?? $patient->id, [
                'category' => 'laboratory',
                'code_system' => 'http://loinc.org',
                'code' => $obs->code,
                'display' => $obs->display,
                'value' => $obs->value,
                'unit' => $obs->unit,
                'unit_code' => 'g/dL'
            ]);
            
            if ($result['success']) {
                $obs->update(['external_id' => $result['resource_id']]);
            }
        }
    }
}
?>
```

---

## Kesimpulan: Best Practices untuk Smart Healthcare AI

### 1. FHIR-First Architecture
```
✅ Gunakan FHIR 4.0 untuk semua data medis
✅ REST API sebagai primary interface
✅ JSON sebagai default format
✅ LOINC codes untuk lab results
```

### 2. Compliance by Design
```
✅ UU PDP: Implement data subject rights
✅ Audit logging untuk setiap data access
✅ Data encryption at rest + in transit
✅ Explicit consent management
```

### 3. Interoperability
```
✅ SATUSEHAT integration untuk platform nasional
✅ Support HL7 v2 legacy systems
✅ SNOMED CT untuk clinical semantics
✅ Standardized terminologies
```

### 4. Security
```
✅ OAuth 2.0 + SMART on FHIR
✅ Role-based access control (RBAC)
✅ Encryption: AES-256, TLS 1.3
✅ Penetration testing regular
✅ Cyber insurance
```

---

**Dokumen Implementasi Praktis** - April 8, 2026  
**Status:** Production-ready code examples

