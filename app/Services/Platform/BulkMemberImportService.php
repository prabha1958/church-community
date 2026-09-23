<?php

namespace App\Services\Platform;

use App\Exceptions\BulkMemberImportException;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkMemberImportService
{
    public const REQUIRED_CSV_COLUMNS = [
        'family_name',
        'first_name',
        'date_of_birth',
        'area_no',
        'email',
        'mobile_number',
        'gender',
        'status',
    ];

    public const OPTIONAL_CSV_COLUMNS = [
        'middle_name',
        'last_name',
        'wedding_date',
        'spouse_name',
        'occupation',
        'membership_fee',
        'address_flat_number',
        'address_premises',
        'address_area',
        'address_landmark',
        'address_city',
        'address_pin',
    ];

    public const ALL_CSV_COLUMNS = [
        'family_name',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'wedding_date',
        'area_no',
        'email',
        'mobile_number',
        'gender',
        'spouse_name',
        'occupation',
        'status',
        'membership_fee',
        'address_flat_number',
        'address_premises',
        'address_area',
        'address_landmark',
        'address_city',
        'address_pin',
    ];

    /**
     * Generate a clean CSV template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = self::ALL_CSV_COLUMNS;

        return response()->streamDownload(
            function () use ($headers) {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM improves Excel compatibility.
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $headers);

                fclose($handle);
            },
            'members_import_template.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]
        );
    }

    /**
     * Parse and validate a CSV for preview.
     *
     * No database records are created.
     */
    public function preview(UploadedFile $file): array
    {
        $csv = $this->readCsv($file);

        if (count($csv['rows']) === 0) {
            throw new \RuntimeException(
                'The CSV does not contain any member rows.'
            );
        }

        $validRows = [];
        $invalidRows = [];

        foreach ($csv['rows'] as $row) {
            $result = $this->validateRow($row);

            if (!$result['valid']) {
                $invalidRows[] = [
                    'row' => $row['_row_number'],
                    'data' => $result['data'],
                    'errors' => $result['errors'],
                ];

                continue;
            }

            $validRows[] = [
                'row_number' => $row['_row_number'],
                'data' => $result['data'],
            ];
        }

        $duplicateMap = $this->detectDuplicates($validRows);

        $duplicateRows = [];

        foreach ($duplicateMap as $rowNumber => $reasons) {
            $duplicateRows[] = [
                'row' => $rowNumber,
                'errors' => $reasons,
            ];
        }

        $duplicateRowNumbers = array_map(
            'intval',
            array_keys($duplicateMap)
        );

        $importableRows = array_values(
            array_filter(
                $validRows,
                fn(array $item): bool =>
                !in_array(
                    $item['row_number'],
                    $duplicateRowNumbers,
                    true
                )
            )
        );

        return [
            'columns' => $csv['headers'],

            'summary' => [
                'total_rows' => count($csv['rows']),
                'valid_rows' => count($validRows),
                'invalid_rows' => count($invalidRows),
                'duplicate_rows' => count($duplicateRows),
                'importable_rows' => count($importableRows),
            ],

            'invalid_rows' => $invalidRows,

            'duplicate_rows' => $duplicateRows,

            'importable_rows' => array_map(
                function (array $item): array {
                    return [
                        'row' => $item['row_number'],
                        'data' => $item['data'],
                    ];
                },
                $importableRows
            ),
        ];
    }

    /**
     * Validate and import the CSV in one tenant transaction.
     *
     * The file is revalidated here rather than trusting the previous preview.
     */
    public function import(UploadedFile $file): array
    {
        $csv = $this->readCsv($file);

        if (count($csv['rows']) === 0) {
            throw new \RuntimeException(
                'The CSV does not contain any member rows.'
            );
        }

        /*
         * Validate every row again.
         */
        $validRows = [];
        $invalidRows = [];

        foreach ($csv['rows'] as $row) {
            $result = $this->validateRow($row);

            if (!$result['valid']) {
                $invalidRows[] = [
                    'row' => $row['_row_number'],
                    'data' => $result['data'],
                    'errors' => $result['errors'],
                ];

                continue;
            }

            $validRows[] = [
                'row_number' => $row['_row_number'],
                'data' => $result['data'],
            ];
        }

        /*
         * All-or-nothing behavior.
         */
        if (!empty($invalidRows)) {
            throw new BulkMemberImportException(
                'The CSV contains validation errors. No members were imported.',
                $invalidRows
            );
        }

        return DB::connection('tenant')->transaction(
            function () use ($validRows): array {
                /*
                 * Re-check duplicates inside the transaction.
                 *
                 * The preview may have happened several minutes earlier,
                 * and another administrator may have created a member
                 * in the meantime.
                 */
                $duplicateMap = $this->detectDuplicates($validRows);

                if (!empty($duplicateMap)) {
                    $duplicateRows = [];

                    foreach ($duplicateMap as $rowNumber => $errors) {
                        $duplicateRows[] = [
                            'row' => $rowNumber,
                            'errors' => $errors,
                        ];
                    }

                    throw new BulkMemberImportException(
                        'The CSV contains duplicate members. No members were imported.',
                        $duplicateRows
                    );
                }

                /*
                 * Additional database-level recheck immediately before
                 * inserting the records.
                 */
                $emails = collect($validRows)
                    ->pluck('data.email')
                    ->map(
                        fn($email) =>
                        strtolower(trim((string) $email))
                    )
                    ->unique()
                    ->values()
                    ->all();

                $mobiles = collect($validRows)
                    ->pluck('data.mobile_number')
                    ->map(
                        fn($mobile) =>
                        trim((string) $mobile)
                    )
                    ->unique()
                    ->values()
                    ->all();

                $existingEmails = Member::query()
                    ->whereIn('email', $emails)
                    ->lockForUpdate()
                    ->pluck('email')
                    ->map(
                        fn($email) =>
                        strtolower(trim((string) $email))
                    )
                    ->all();

                $existingMobiles = Member::query()
                    ->whereIn('mobile_number', $mobiles)
                    ->lockForUpdate()
                    ->pluck('mobile_number')
                    ->map(
                        fn($mobile) =>
                        trim((string) $mobile)
                    )
                    ->all();

                $existingEmailLookup = array_flip($existingEmails);
                $existingMobileLookup = array_flip($existingMobiles);

                $databaseDuplicates = [];

                foreach ($validRows as $item) {
                    $rowNumber = $item['row_number'];
                    $data = $item['data'];

                    $rowErrors = [];

                    $email = strtolower(
                        trim((string) $data['email'])
                    );

                    $mobile = trim(
                        (string) $data['mobile_number']
                    );

                    if (isset($existingEmailLookup[$email])) {
                        $rowErrors[] =
                            'Email already exists in this church.';
                    }

                    if (isset($existingMobileLookup[$mobile])) {
                        $rowErrors[] =
                            'Mobile number already exists in this church.';
                    }

                    if (!empty($rowErrors)) {
                        $databaseDuplicates[] = [
                            'row' => $rowNumber,
                            'errors' => $rowErrors,
                        ];
                    }
                }

                if (!empty($databaseDuplicates)) {
                    throw new BulkMemberImportException(
                        'One or more members already exist. No members were imported.',
                        $databaseDuplicates
                    );
                }

                /*
                 * Create the members.
                 */
                $created = 0;

                foreach ($validRows as $item) {
                    $data = $this->prepareMemberData(
                        $item['data']
                    );

                    Member::create($data);

                    $created++;
                }

                return [
                    'created' => $created,
                ];
            }
        );
    }

    /**
     * Parse CSV and return headers + rows.
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'Unable to open the CSV file.'
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false || count($headers) === 0) {
            fclose($handle);

            throw new \RuntimeException(
                'The CSV file is empty or does not contain a header row.'
            );
        }

        $headers = array_map(
            fn($header) =>
            $this->normalizeHeader($header),
            $headers
        );

        /*
         * Remove empty headers.
         */
        $headers = array_values(
            array_filter(
                $headers,
                fn($header) => $header !== ''
            )
        );

        /*
         * Duplicate column names are ambiguous.
         */
        if (
            count($headers) !==
            count(array_unique($headers))
        ) {
            fclose($handle);

            throw new \RuntimeException(
                'The CSV contains duplicate column names.'
            );
        }

        /*
         * Required columns must be present.
         */
        $missingColumns = array_diff(
            self::REQUIRED_CSV_COLUMNS,
            $headers
        );

        if (!empty($missingColumns)) {
            fclose($handle);

            throw new \RuntimeException(
                'The CSV is missing required columns: ' .
                    implode(', ', $missingColumns)
            );
        }

        $rows = [];
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;

            /*
             * Ignore completely empty rows.
             */
            if (
                count($values) === 1 &&
                trim((string) $values[0]) === ''
            ) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index])
                    ? trim((string) $values[$index])
                    : null;
            }

            $row['_row_number'] = $rowNumber;

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Normalize CSV column names.
     */
    private function normalizeHeader(?string $header): string
    {
        $header = trim((string) $header);

        /*
         * Remove UTF-8 BOM from first column.
         */
        $header = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $header
        );

        $header = strtolower($header);

        $header = str_replace(
            [' ', '-', '/'],
            '_',
            $header
        );

        return trim($header);
    }

    /**
     * Validate one CSV row.
     */
    private function validateRow(array $row): array
    {
        $data = [];

        foreach (self::ALL_CSV_COLUMNS as $column) {
            $value = $row[$column] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$column] =
                $value === ''
                ? null
                : $value;
        }

        $validator = Validator::make(
            $data,
            [
                'family_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'first_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'middle_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'last_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'date_of_birth' => [
                    'required',
                    'date_format:Y-m-d',
                ],

                'wedding_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],

                'area_no' => [
                    'required',
                    'string',
                    'regex:/^\d{1,2}$/',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'mobile_number' => [
                    'required',
                    'regex:/^\d{10}$/',
                ],

                'gender' => [
                    'required',
                    Rule::in([
                        'male',
                        'female',
                        'other',
                    ]),
                ],

                'spouse_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'occupation' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'status' => [
                    'required',
                    Rule::in([
                        'in_service',
                        'retired',
                        'other',
                    ]),
                ],

                'membership_fee' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'address_flat_number' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address_premises' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address_area' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address_landmark' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address_city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address_pin' => [
                    'nullable',
                    'regex:/^\d{6}$/',
                ],
            ],
            [
                'date_of_birth.date_format' =>
                'Date of birth must be in YYYY-MM-DD format.',

                'wedding_date.date_format' =>
                'Wedding date must be in YYYY-MM-DD format.',

                'mobile_number.regex' =>
                'Mobile number must contain exactly 10 digits.',

                'area_no.regex' =>
                'Area number must contain 1 or 2 digits.',

                'address_pin.regex' =>
                'PIN code must contain exactly 6 digits.',
            ]
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'data' => $data,
                'errors' => $validator
                    ->errors()
                    ->toArray(),
            ];
        }

        /*
         * Normalize values after validation.
         */
        $data['email'] = strtolower(
            trim((string) $data['email'])
        );

        $data['mobile_number'] = trim(
            (string) $data['mobile_number']
        );

        return [
            'valid' => true,
            'data' => $data,
            'errors' => [],
        ];
    }

    /**
     * Detect both database duplicates and duplicates
     * inside the uploaded CSV.
     */
    private function detectDuplicates(
        array $validRows
    ): array {
        if (empty($validRows)) {
            return [];
        }

        $emails = [];
        $mobiles = [];

        foreach ($validRows as $item) {
            $data = $item['data'];

            $email = strtolower(
                trim((string) $data['email'])
            );

            $mobile = trim(
                (string) $data['mobile_number']
            );

            if ($email !== '') {
                $emails[$email][] =
                    $item['row_number'];
            }

            if ($mobile !== '') {
                $mobiles[$mobile][] =
                    $item['row_number'];
            }
        }

        /*
         * Existing emails in this church.
         */
        $existingEmails = [];

        if (!empty($emails)) {
            $existingEmails = Member::query()
                ->whereIn(
                    'email',
                    array_keys($emails)
                )
                ->pluck('email')
                ->map(
                    fn($email) =>
                    strtolower(
                        trim((string) $email)
                    )
                )
                ->all();
        }

        /*
         * Existing mobiles in this church.
         */
        $existingMobiles = [];

        if (!empty($mobiles)) {
            $existingMobiles = Member::query()
                ->whereIn(
                    'mobile_number',
                    array_keys($mobiles)
                )
                ->pluck('mobile_number')
                ->map(
                    fn($mobile) =>
                    trim((string) $mobile)
                )
                ->all();
        }

        $existingEmailLookup =
            array_flip($existingEmails);

        $existingMobileLookup =
            array_flip($existingMobiles);

        $duplicates = [];

        foreach ($validRows as $item) {
            $rowNumber = $item['row_number'];
            $data = $item['data'];

            $email = strtolower(
                trim((string) $data['email'])
            );

            $mobile = trim(
                (string) $data['mobile_number']
            );

            $reasons = [];

            /*
             * Existing database records.
             */
            if (isset($existingEmailLookup[$email])) {
                $reasons[] =
                    'Email already exists in this church.';
            }

            if (isset($existingMobileLookup[$mobile])) {
                $reasons[] =
                    'Mobile number already exists in this church.';
            }

            /*
             * Duplicate email within CSV.
             */
            if (
                isset($emails[$email]) &&
                count($emails[$email]) > 1
            ) {
                $reasons[] =
                    'Email appears more than once in this CSV.';
            }

            /*
             * Duplicate mobile within CSV.
             */
            if (
                isset($mobiles[$mobile]) &&
                count($mobiles[$mobile]) > 1
            ) {
                $reasons[] =
                    'Mobile number appears more than once in this CSV.';
            }

            if (!empty($reasons)) {
                $duplicates[$rowNumber] = $reasons;
            }
        }

        return $duplicates;
    }

    /**
     * Prepare validated CSV data for Member::create().
     */
    private function prepareMemberData(array $data): array
    {
        /*
         * Keep dates date-only.
         */
        $data['date_of_birth'] =
            Carbon::createFromFormat(
                'Y-m-d',
                $data['date_of_birth']
            )->format('Y-m-d');

        if (!empty($data['wedding_date'])) {
            $data['wedding_date'] =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $data['wedding_date']
                )->format('Y-m-d');
        }

        $data['status_flag'] = true;

        /*
         * Imported accounts are ordinary members.
         *
         * role is intentionally NOT supplied by the CSV.
         */
        $data['role'] = Member::ROLE_MEMBER;

        return array_filter(
            $data,
            fn($value) => $value !== null
        );
    }
}
