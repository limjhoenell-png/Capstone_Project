<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\LogbookController;
use App\Services\LogbookTransferService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class LogbookTransferServiceTest extends TestCase
{
    public function test_download_controller_returns_the_generated_template_as_a_download(): void
    {
        $response = app(LogbookController::class)->downloadFormat();
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type'),
        );
        $this->assertStringContainsString(
            'logbook_import_template.xlsx',
            (string) $response->headers->get('content-disposition'),
        );

        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);

        if (! unlink($path)) {
            throw new \RuntimeException('Unable to remove the temporary Excel template test file.');
        }
    }

    public function test_excel_template_has_requested_headers_dropdown_and_time_example(): void
    {
        $path = (new LogbookTransferService)->templateXlsxFile();
        $this->assertSame(realpath(storage_path('app/private')), realpath(dirname($path)));

        try {
            $archive = new ZipArchive;
            $this->assertSame(true, $archive->open($path));

            try {
                $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
                $workbook = $archive->getFromName('xl/workbook.xml');
                $purposeOptions = $archive->getFromName('xl/worksheets/sheet2.xml');
                $styles = $archive->getFromName('xl/styles.xml');
                $this->assertNotFalse($worksheet);
                $this->assertNotFalse($workbook);
                $this->assertNotFalse($purposeOptions);
                $this->assertNotFalse($styles);
                $this->assertSame(11, substr_count($worksheet, '<col '));
                $this->assertSame(2009, substr_count($worksheet, '<c r='));
                $this->assertStringContainsString('Name of User', $worksheet);
                $this->assertStringContainsString('Person Classification', $worksheet);
                $this->assertStringContainsString('Country Code', $worksheet);
                $this->assertStringContainsString('Contact Number', $worksheet);
                $this->assertStringContainsString('<c r="E1" s="2" t="inlineStr">', $worksheet);
                $this->assertStringContainsString('<c r="G1" s="1" t="inlineStr">', $worksheet);
                $this->assertStringContainsString('Purpose of Transaction', $worksheet);
                $this->assertStringContainsString('Authorized Representative', $worksheet);
                $this->assertStringContainsString('Authorization Letter Verified', $worksheet);
                $this->assertStringContainsString('Valid ID Verified', $worksheet);
                $this->assertStringContainsString('Representing', $worksheet);
                $this->assertStringNotContainsString('Time Period', $worksheet);
                $this->assertStringNotContainsString('Transaction Type', $worksheet);
                $this->assertStringNotContainsString('Fingerprint ID', $worksheet);
                $this->assertStringContainsString('<c r="G2" s="1"/>', $worksheet);
                $this->assertStringContainsString('<c r="G3" s="1"/>', $worksheet);
                $this->assertStringContainsString('<c r="G100" s="1"/>', $worksheet);
                $this->assertStringContainsString('<c r="G1000" s="1"/>', $worksheet);
                $this->assertStringNotContainsString('46023', $worksheet);
                $this->assertStringNotContainsString('sqref="L2:L1048576"', $worksheet);
                $this->assertStringContainsString('sqref="G2:G1048576"', $worksheet);
                $this->assertStringContainsString('showErrorMessage="0" showDropDown="0" showInputMessage="1"', $worksheet);
                $this->assertStringContainsString('type="list" allowBlank="1"', $worksheet);
                $this->assertStringContainsString('<formula1>"2026-01-01 12:00:00 AM,2026-01-01 12:00:00 PM"</formula1>', $worksheet);
                $this->assertStringContainsString('fullCalcOnLoad="1" forceFullCalc="1"', $workbook);
                $this->assertStringContainsString('sqref="B2:B1048576"', $worksheet);
                $this->assertStringContainsString('<formula1>"Visitor,Resident,Personnel"</formula1>', $worksheet);
                $this->assertStringContainsString('sqref="F2:F1048576"', $worksheet);
                $this->assertStringContainsString('<formula1>PurposeChoices</formula1>', $worksheet);
                $this->assertStringContainsString('sqref="D2:D1048576"', $worksheet);
                $this->assertStringContainsString('<formula1>CountryCodes</formula1>', $worksheet);
                $this->assertStringContainsString('type="custom" allowBlank="1"', $worksheet);
                $this->assertStringContainsString('IF(RIGHT(D2,3)=&quot;+63&quot;', $worksheet);
                $this->assertStringContainsString('sqref="E2:E1048576"', $worksheet);
                $this->assertStringContainsString('formatCode="@"', $styles);
                $this->assertStringNotContainsString('<c r="D2"', $worksheet);
                $this->assertStringNotContainsString('<c r="D3"', $worksheet);
                $this->assertStringContainsString('<c r="E3" s="2"/>', $worksheet);
                $this->assertStringContainsString('For Other, replace the cell value with your custom purpose.', $worksheet);
                $this->assertStringContainsString('name="PurposeChoices"', $workbook);
                $this->assertStringContainsString('name="CountryCodes"', $workbook);
                $this->assertStringContainsString('state="hidden"', $workbook);
                $this->assertStringContainsString('Request for Barangay Clearance', $purposeOptions);
                $this->assertStringContainsString('Other', $purposeOptions);
                $this->assertStringContainsString('Singapore (+65)', $purposeOptions);
                $this->assertStringContainsString('United States (+1)', $purposeOptions);
                $this->assertStringContainsString('Canada (+1)', $purposeOptions);
                $this->assertStringContainsString('Malaysia (+60)', $purposeOptions);
                $this->assertStringContainsString('Taiwan (+886)', $purposeOptions);
                $this->assertStringContainsString('definedName name="CountryCodes">&apos;Purpose Options&apos;!$B$1:$B$18', $workbook);
                $this->assertSame(18, substr_count($purposeOptions, '<row r='));
                $this->assertStringContainsString(
                    '<row r="1"><c r="A1" t="inlineStr"><is><t xml:space="preserve">Request for Barangay Clearance</t></is></c><c r="B1" t="inlineStr"><is><t xml:space="preserve">Philippines (+63)</t></is></c></row>',
                    $purposeOptions,
                );
                $xml = new \DOMDocument;
                $this->assertTrue($xml->loadXML($worksheet, LIBXML_NONET));
                $this->assertTrue($xml->loadXML($purposeOptions, LIBXML_NONET));
                $this->assertTrue($xml->loadXML($workbook, LIBXML_NONET));
                $this->assertTrue($xml->loadXML($styles, LIBXML_NONET));
                $this->assertStringContainsString('<col min="4" max="4" width="30" customWidth="1"/>', $worksheet);
                $this->assertStringContainsString('<col min="5" max="5" width="24" customWidth="1" style="2"/>', $worksheet);
                $this->assertStringContainsString('<col min="6" max="6" width="48" customWidth="1"/>', $worksheet);
                $this->assertStringContainsString('<col min="7" max="7" width="26" customWidth="1" style="1"/>', $worksheet);
                $this->assertStringContainsString('<col min="8" max="8" width="30" customWidth="1"/>', $worksheet);
                $this->assertSame(3, substr_count($styles, '<alignment horizontal="left"/>'));
            } finally {
                $archive->close();
            }
        } finally {
            if (file_exists($path) && ! unlink($path)) {
                throw new \RuntimeException('Unable to remove the temporary Excel template test file.');
            }
        }
    }

    public function test_excel_export_includes_admin_columns_and_content_sized_widths(): void
    {
        $service = new LogbookTransferService;
        $path = $service->exportXlsxFile([[
            '42',
            'Alex Example',
            'Resident',
            '12 Extended Sample Street, Barangay Central, Springfield',
            '+63 09266952238',
            'Request for Barangay Clearance',
            'Yes',
            'Yes',
            'No',
            'Jamie Example',
            '2026-11-02 09:30:00 PM',
            'FP-0042',
        ]]);

        try {
            $archive = new ZipArchive;
            $this->assertSame(true, $archive->open($path));

            try {
                $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
                $this->assertNotFalse($worksheet);
                foreach ([
                    'Person Classification',
                    'Purpose of Transaction',
                    'Fingerprint ID',
                    'FP-0042',
                    '2026-11-02 09:30:00 PM',
                ] as $value) {
                    $this->assertStringContainsString($value, $worksheet);
                }

                $this->assertSame(12, substr_count($worksheet, '<col '));
                $this->assertSame(1, preg_match('/<col min="4" max="4" width="(\d+)"/', $worksheet, $widthMatch));
                $this->assertGreaterThan(24, (int) $widthMatch[1]);
                $xml = new \DOMDocument;
                $this->assertTrue($xml->loadXML($worksheet, LIBXML_NONET));
                $xpath = new \DOMXPath($xml);
                $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $headerNodes = $xpath->query('/x:worksheet/x:sheetData/x:row[@r="1"]/x:c/x:is/x:t');
                $this->assertNotFalse($headerNodes);
                $this->assertSame([
                    'ID',
                    'User',
                    'Person Classification',
                    'Address',
                    'Contact',
                    'Purpose of Transaction',
                    'Rep',
                    'Letter',
                    'ID',
                    'Representing',
                    'Time In',
                    'Fingerprint ID',
                ], array_map(
                    static fn (\DOMNode $node): string => $node->textContent,
                    iterator_to_array($headerNodes),
                ));
            } finally {
                $archive->close();
            }
        } finally {
            if (! unlink($path)) {
                throw new \RuntimeException('Unable to remove the temporary Excel export test file.');
            }
        }
    }

    public function test_import_maps_only_supported_person_classifications_and_skips_template_example_row(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        $row = $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Contact Number' => '09266952238',
            'Person Classification' => 'personnel',
            'Time In' => '2026-01-01 00:00:00',
        ]);
        $this->assertSame('Personnel', $row['person_category']);
        foreach (['Visitor', 'Resident', 'Personnel'] as $classification) {
            $normalized = $normalizer->invoke($service, [
                'Name of User' => 'A. User',
                'Contact Number' => '09266952238',
                'Person Classification' => $classification,
            ]);
            $this->assertSame($classification, $normalized['person_category']);
        }

        $this->assertNull($normalizer->invoke($service, [
            'Person Classification' => '',
            'Time In' => '2026-01-01 00:00:00',
        ]));

        $this->expectException(\InvalidArgumentException::class);
        $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Person Classification' => 'Employee',
        ]);
    }

    public function test_import_preserves_excel_datetime_strings_and_serial_values(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');
        $baseDate = new \DateTimeImmutable('1899-12-30 00:00:00', new \DateTimeZone('UTC'));
        $excelSerial = $baseDate->diff(new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')))->days
            + (1 / 86400);

        $formatted = $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Contact Number' => '09266952238',
            'Time In' => '1/1/2026 12:00:01 AM',
        ]);
        $this->assertSame('2026-01-01 00:00:01', $formatted['time_in']);

        $formattedAmPm = $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Contact Number' => '09266952238',
            'Time In' => '2026-01-01 12:00:00 AM',
        ]);
        $this->assertSame('2026-01-01 00:00:00', $formattedAmPm['time_in']);

        $serial = $normalizer->invoke($service, [
            'Name of User' => 'B. User',
            'Contact Number' => '09266952238',
            'Time In' => $excelSerial,
        ]);
        $this->assertSame('2026-01-01 00:00:01', $serial['time_in']);
    }

    public function test_import_rejects_invalid_nonblank_datetime_instead_of_using_current_time(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        $this->expectException(\InvalidArgumentException::class);
        $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Contact Number' => '09266952238',
            'Time In' => 'not a date',
        ]);
    }

    public function test_import_generates_current_timestamp_when_time_in_is_blank(): void
    {
        Carbon::setTestNow('2026-11-02 19:45:00');

        try {
            $service = new LogbookTransferService;
            $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

            foreach ([null, ''] as $blankTime) {
                $row = $normalizer->invoke($service, [
                    'Name of User' => 'A. User',
                    'Contact Number' => '09266952238',
                    'Purpose of Transaction' => 'Request for Barangay Clearance',
                    'Time In' => $blankTime,
                ]);
                $this->assertSame('2026-11-02 19:45:00', $row['time_in']);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_import_maps_template_headers_and_preserves_every_supplied_field(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');
        $row = $normalizer->invoke($service, [
            'NAME_OF_USER' => 'A. User',
            'Person Classification' => 'Resident',
            'Address' => '10 Main Street',
            'Contact Number' => '09266952238',
            'Purpose of Transaction' => 'Request for Barangay Clearance',
            'Time In' => '2026-01-01 09:30:00',
            'Authorized Representative' => 'Yes',
            'Authorization Letter Verified' => 'Yes',
            'Valid ID Verified' => '1',
            'Representing' => 'B. User',
        ]);

        $this->assertSame('A. User', $row['full_name']);
        $this->assertSame('Resident', $row['person_category']);
        $this->assertSame('10 Main Street', $row['address']);
        $this->assertSame('+63 09266952238', $row['contact_number']);
        $this->assertSame('Request for Barangay Clearance', $row['purpose_of_transaction']);
        $this->assertSame('2026-01-01 09:30:00', $row['time_in']);
        $this->assertSame(1, $row['is_authorized_representative']);
        $this->assertSame(1, $row['authorization_letter_verified']);
        $this->assertSame(1, $row['valid_id_verified']);
        $this->assertSame('B. User', $row['represented_person_name']);
    }

    public function test_import_allows_blank_optional_fields_and_normalizes_them_to_null(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');
        $row = $normalizer->invoke($service, [
            'Name of User' => 'A. User',
            'Person Classification' => '',
            'Address' => '   ',
            'Country Code' => 'Invalid country code is ignored without a contact number',
            'Contact Number' => '',
            'Purpose of Transaction' => '',
            'Transaction Type' => '',
            'Representing' => '',
        ]);

        $this->assertSame('A. User', $row['full_name']);
        $this->assertSame('', $row['person_category']);
        $this->assertNull($row['address']);
        $this->assertNull($row['contact_number']);
        $this->assertNull($row['purpose_of_transaction']);
        $this->assertNull($row['transaction_type']);
        $this->assertNull($row['represented_person_name']);
        $this->assertNull($row['is_authorized_representative']);
        $this->assertNull($row['authorization_letter_verified']);
        $this->assertNull($row['valid_id_verified']);
    }

    public function test_import_allows_a_name_without_optional_fields(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');
        $row = $normalizer->invoke($service, [
            'Name of User' => 'A. User',
        ]);

        $this->assertSame('A. User', $row['full_name']);
        $this->assertNull($row['contact_number']);
        $this->assertNull($row['address']);
        $this->assertNull($row['purpose_of_transaction']);
        $this->assertSame('', $row['person_category']);
    }

    public function test_import_rejects_a_contact_number_without_a_name(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name of User is required');
        $normalizer->invoke($service, [
            'Contact Number' => '09266952238',
        ]);
    }

    public function test_import_formats_excel_numeric_and_existing_country_code_contact_numbers(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        foreach ([
            9266952238 => '+63 09266952238',
            '09266952238' => '+63 09266952238',
            '+63 09266952238' => '+63 09266952238',
            '+1 4155550123' => '+1 4155550123',
        ] as $contact => $expected) {
            $row = $normalizer->invoke($service, [
                'Name of User' => 'A. User',
                'Contact Number' => $contact,
            ]);
            $this->assertSame($expected, $row['contact_number']);
        }
    }

    public function test_import_combines_selected_country_code_with_national_contact_number(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        foreach ([
            ['Philippines (+63)', 9266952238, '+63 09266952238'],
            ['Singapore (+65)', 81234567, '+65 81234567'],
            ['Hong Kong (+852)', 61234567, '+852 61234567'],
            ['Canada (+1)', 4155550123, '+1 4155550123'],
        ] as [$countryCode, $contactNumber, $expected]) {
            $row = $normalizer->invoke($service, [
                'Name of User' => 'A. User',
                'Country Code' => $countryCode,
                'Contact Number' => $contactNumber,
            ]);

            $this->assertSame($expected, $row['contact_number']);
        }
    }

    public function test_import_rejects_non_digit_and_wrong_length_philippines_contact_numbers(): void
    {
        $service = new LogbookTransferService;
        $normalizer = new \ReflectionMethod($service, 'normalizeImportRow');

        foreach (['09266A52238', '926695223'] as $contact) {
            try {
                $normalizer->invoke($service, [
                    'Name of User' => 'A. User',
                    'Contact Number' => $contact,
                ]);
                $this->fail('Invalid contact number should be rejected.');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
