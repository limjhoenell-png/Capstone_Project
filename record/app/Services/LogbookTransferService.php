<?php

namespace App\Services;

use App\Models\LogbookEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class LogbookTransferService
{
    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'Name of User',
            'Person Classification',
            'Address',
            'Country Code',
            'Contact Number',
            'Purpose of Transaction',
            'Time In',
            'Authorized Representative',
            'Authorization Letter Verified',
            'Valid ID Verified',
            'Representing',
        ];
    }

    /**
     * @return list<string>
     */
    public function purposeOptions(): array
    {
        return [
            'Request for Barangay Clearance',
            'Request for Certificate of Indigency',
            'Request for Certificate of Residency',
            'Filing a Complaint (Reklamo)',
            'Blotter Report (Incident Report)',
            'Mediation / Settlement (Barangay Hearing)',
            'Request for Assistance (Financial / Medical)',
            'Business Permit Clearance',
            'General Inquiry / Concern',
            'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function contactCountryCodes(): array
    {
        return [
            '+63' => '🇵🇭 Philippines',
            '+65' => '🇸🇬 Singapore',
            '+1' => '🇺🇸🇨🇦 United States / Canada',
            '+60' => '🇲🇾 Malaysia',
            '+62' => '🇮🇩 Indonesia',
            '+66' => '🇹🇭 Thailand',
            '+84' => '🇻🇳 Vietnam',
            '+86' => '🇨🇳 China',
            '+852' => '🇭🇰 Hong Kong',
            '+81' => '🇯🇵 Japan',
            '+82' => '🇰🇷 South Korea',
            '+61' => '🇦🇺 Australia',
            '+44' => '🇬🇧 United Kingdom',
            '+91' => '🇮🇳 India',
            '+971' => '🇦🇪 United Arab Emirates',
            '+966' => '🇸🇦 Saudi Arabia',
            '+886' => '🇹🇼 Taiwan',
        ];
    }

    /**
     * @return list<string>
     */
    private function contactCountryCodeOptions(): array
    {
        return [
            'Philippines (+63)',
            'Singapore (+65)',
            'United States (+1)',
            'Canada (+1)',
            'Malaysia (+60)',
            'Indonesia (+62)',
            'Thailand (+66)',
            'Vietnam (+84)',
            'China (+86)',
            'Hong Kong (+852)',
            'Japan (+81)',
            'South Korea (+82)',
            'Australia (+61)',
            'United Kingdom (+44)',
            'India (+91)',
            'United Arab Emirates (+971)',
            'Saudi Arabia (+966)',
            'Taiwan (+886)',
        ];
    }

    private function defaultContactCountryCode(): string
    {
        return $this->contactCountryCodeOptions()[0];
    }

    public function templateCsvContent(): string
    {
        return $this->toCsv([$this->templateHeaders()]);
    }

    public function templateXlsxFile(): string
    {
        $headers = $this->templateHeaders();
        $expectedWidths = [24, 24, 32, 30, 24, 48, 26, 30, 32, 24, 28];
        $worksheetColumns = '';
        $worksheetCells = '';

        foreach ($headers as $index => $header) {
            $columnNumber = $index + 1;
            $columnName = $this->spreadsheetColumnName($columnNumber);

            $width = max(strlen($header) + 3, $expectedWidths[$index] ?? 0);
            $style = match ($header) {
                'Contact Number' => 2,
                'Time In' => 1,
                default => null,
            };
            $worksheetColumns .= sprintf(
                '<col min="%d" max="%d" width="%d" customWidth="1"%s/>',
                $columnNumber,
                $columnNumber,
                $width,
                $style === null ? '' : ' style="'.$style.'"',
            );
            $worksheetCells .= sprintf(
                '<c r="%s1"%s t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                $columnName,
                $style === null ? '' : ' s="'.$style.'"',
                htmlspecialchars($header, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        $classificationColumn = array_search('Person Classification', $headers, true);
        if ($classificationColumn === false) {
            throw new RuntimeException('Person Classification column is missing from the import template.');
        }
        $classificationCell = $this->spreadsheetColumnName($classificationColumn + 1);
        $purposeColumn = array_search('Purpose of Transaction', $headers, true);
        if ($purposeColumn === false) {
            throw new RuntimeException('Purpose of Transaction column is missing from the import template.');
        }
        $purposeCell = $this->spreadsheetColumnName($purposeColumn + 1);
        $classificationValidation = sprintf(
            '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid classification" error="Choose Visitor, Resident, or Personnel." sqref="%s2:%s1048576"><formula1>"Visitor,Resident,Personnel"</formula1></dataValidation>',
            $classificationCell,
            $classificationCell,
        );
        $purposeValidation = sprintf(
            '<dataValidation type="list" allowBlank="1" showInputMessage="1" promptTitle="Custom purpose" prompt="For Other, replace the cell value with your custom purpose." showErrorMessage="1" errorStyle="warning" errorTitle="Custom purpose" error="Choose a standard purpose or enter your own value." sqref="%s2:%s1048576"><formula1>PurposeChoices</formula1></dataValidation>',
            $purposeCell,
            $purposeCell,
        );
        $timeInColumn = array_search('Time In', $headers, true);
        if ($timeInColumn === false) {
            throw new RuntimeException('Time In column is missing from the import template.');
        }
        $timeInCell = $this->spreadsheetColumnName($timeInColumn + 1);
        $timeInValidation = sprintf(
            '<dataValidation type="list" allowBlank="1" showErrorMessage="0" showDropDown="0" showInputMessage="1" promptTitle="Time In" prompt="Choose a suggested timestamp or type any date and time you need." sqref="%s2:%s1048576"><formula1>"2026-01-01 12:00:00 AM,2026-01-01 12:00:00 PM"</formula1></dataValidation>',
            $timeInCell,
            $timeInCell,
        );
        $contactColumn = array_search('Contact Number', $headers, true);
        if ($contactColumn === false) {
            throw new RuntimeException('Contact Number column is missing from the import template.');
        }
        $contactCell = $this->spreadsheetColumnName($contactColumn + 1);
        $countryCodeColumn = array_search('Country Code', $headers, true);
        if ($countryCodeColumn === false) {
            throw new RuntimeException('Country Code column is missing from the import template.');
        }
        $countryCodeCell = $this->spreadsheetColumnName($countryCodeColumn + 1);
        $countryCodeValidation = sprintf(
            '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid country code" error="Choose a country code from the dropdown list." sqref="%s2:%s1048576"><formula1>CountryCodes</formula1></dataValidation>',
            $countryCodeCell,
            $countryCodeCell,
        );
        $contactValidation = sprintf(
            '<dataValidation type="custom" allowBlank="1" showInputMessage="1" promptTitle="Contact Number" prompt="Enter digits only. For Philippines, enter an 11-digit number beginning with 09." showErrorMessage="1" errorTitle="Invalid contact number" error="Enter digits only. Use 11 digits for Philippines, or 4 to 15 digits for other country codes." sqref="%s2:%s1048576"><formula1>AND(IFERROR(ISNUMBER(VALUE(%s2)),FALSE),IF(RIGHT(%s2,3)=&quot;+63&quot;,OR(LEN(%s2)=10,LEN(%s2)=11),AND(LEN(%s2)&gt;=4,LEN(%s2)&lt;=15)))</formula1></dataValidation>',
            $contactCell,
            $contactCell,
            $contactCell,
            $countryCodeCell,
            $contactCell,
            $contactCell,
            $contactCell,
            $contactCell,
        );
        $dataValidations = '<dataValidations count="5">'.$classificationValidation.$purposeValidation.$countryCodeValidation.$contactValidation.$timeInValidation.'</dataValidations>';

        $timeRows = '';
        for ($rowNumber = 2; $rowNumber <= 1000; $rowNumber++) {
            $timeRows .= sprintf(
                '<row r="%d"><c r="%s" s="2"/><c r="%s%d" s="1"/></row>',
                $rowNumber,
                $contactCell.$rowNumber,
                $timeInCell,
                $rowNumber,
            );
        }

        $countryCodeOptions = '';
        $purposeChoices = $this->purposeOptions();
        $countryChoices = $this->contactCountryCodeOptions();
        for ($index = 0, $max = max(count($purposeChoices), count($countryChoices)); $index < $max; $index++) {
            $rowNumber = $index + 1;
            $purposeCell = isset($purposeChoices[$index])
                ? '<c r="A'.$rowNumber.'" t="inlineStr"><is><t xml:space="preserve">'
                    .htmlspecialchars($purposeChoices[$index], ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    .'</t></is></c>'
                : '';
            $countryCodeCell = isset($countryChoices[$index])
                ? '<c r="B'.$rowNumber.'" t="inlineStr"><is><t xml:space="preserve">'
                    .htmlspecialchars($countryChoices[$index], ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    .'</t></is></c>'
                : '';
            $countryCodeOptions .= sprintf('<row r="%d">%s%s</row>', $rowNumber, $purposeCell, $countryCodeCell);
        }

        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Logbook Import" sheetId="1" r:id="rId1"/><sheet name="Purpose Options" sheetId="2" state="hidden" r:id="rId2"/></sheets>'
                .'<definedNames><definedName name="PurposeChoices">&apos;Purpose Options&apos;!$A$1:$A$10</definedName>'
                .'<definedName name="CountryCodes">&apos;Purpose Options&apos;!$B$1:$B$18</definedName></definedNames>'
                .'<calcPr calcId="191029" calcMode="auto" fullCalcOnLoad="1" forceFullCalc="1"/>'
                .'</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
                .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<cols>'.$worksheetColumns.'</cols>'
                .'<sheetData><row r="1">'.$worksheetCells.'</row>'.$timeRows.'</sheetData>'
                .$dataValidations
                .'</worksheet>',
            'xl/worksheets/sheet2.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<sheetData>'.$countryCodeOptions.'</sheetData></worksheet>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<numFmts count="1"><numFmt numFmtId="164" formatCode="@"/></numFmts>'
                .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
                .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
                .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                .'<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="left"/></xf>'
                .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="left"/></xf>'
                .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left"/></xf></cellXfs>'
                .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                .'</styleSheet>',
        ];

        $directory = storage_path('app/private');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the private template storage directory.');
        }
        if (! is_writable($directory)) {
            throw new RuntimeException('The private template storage directory is not writable.');
        }

        $path = tempnam($directory, 'logbook-template-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary Excel template file.');
        }

        $archive = new \ZipArchive;
        $archiveOpened = false;
        $keepFile = false;

        try {
            if ($archive->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to open the Excel template archive.');
            }
            $archiveOpened = true;

            foreach ($parts as $name => $contents) {
                if (! $archive->addFromString($name, $contents)) {
                    throw new RuntimeException('Unable to write the Excel template archive.');
                }
            }

            if (! $archive->close()) {
                $archiveOpened = false;
                throw new RuntimeException('Unable to finalize the Excel template archive.');
            }
            $archiveOpened = false;

            $keepFile = true;

            return $path;
        } finally {
            if ($archiveOpened) {
                $archive->close();
            }
            if (! $keepFile && file_exists($path) && ! unlink($path)) {
                throw new RuntimeException('Unable to remove the temporary Excel template file.');
            }
        }
    }

    public function templateXlsxContent(): string
    {
        $path = $this->templateXlsxFile();

        try {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('Unable to read the generated Excel template.');
            }

            return $content;
        } finally {
            if (file_exists($path) && ! unlink($path)) {
                throw new RuntimeException('Unable to remove the temporary Excel template file.');
            }
        }
    }

    public function exportCsvContent(): string
    {
        if (Schema::hasTable('logbook')) {
            $rows = DB::table('logbook')->orderByDesc('time_in')->get();

            $records = [$this->exportHeaders()];
            foreach ($rows as $row) {
                $records[] = [
                    (string) ($row->logbook_id ?? ''),
                    (string) ($row->full_name ?? ''),
                    (string) data_get($row, 'person_category', data_get($row, 'person_classification', '')),
                    (string) ($row->address ?? ''),
                    (string) ($row->contact_number ?? ''),
                    (string) ($row->purpose_of_transaction ?? ''),
                    (string) data_get($row, 'is_authorized_representative', ''),
                    (string) data_get($row, 'authorization_letter_verified', ''),
                    (string) data_get($row, 'valid_id_verified', ''),
                    (string) data_get($row, 'represented_person_name', ''),
                    (string) ($row->time_in ?? ''),
                    (string) data_get($row, 'fingerprint_id', ''),
                ];
            }

            return $this->toCsv($records);
        }

        $rows = LogbookEntry::query()
            ->with('user')
            ->latest('entry_at')
            ->get();

        $records = [$this->exportHeaders()];
        foreach ($rows as $row) {
            $records[] = [
                (string) $row->id,
                (string) ($row->full_name ?: ($row->user?->name ?? $row->user?->full_name ?? '')),
                (string) ($row->person_category ?? ''),
                (string) ($row->address ?? ''),
                (string) ($row->contact_number ?: ($row->user?->contact_number ?? '')),
                (string) ($row->purpose_of_transaction ?? $row->description ?? ''),
                (string) ($row->is_authorized_representative ?? ''),
                (string) ($row->authorization_letter_verified ?? ''),
                (string) ($row->valid_id_verified ?? ''),
                (string) ($row->represented_person_name ?? ''),
                (string) ($row->entry_at?->format('Y-m-d H:i:s') ?? ''),
                '',
            ];
        }

        return $this->toCsv($records);
    }

    /**
     * Generate an offline Excel report from rows already mapped to the admin table.
     *
     * @param  list<list<string>>  $records
     */
    public function exportXlsxFile(array $records): string
    {
        $headers = $this->exportHeaders();
        array_unshift($records, $headers);

        $columnCount = count($headers);
        $rowCount = count($records);
        $minimumWidths = [10, 22, 23, 24, 18, 30, 10, 12, 10, 24, 24, 20];
        $columnWidths = array_fill(0, $columnCount, 0);

        foreach ($records as $record) {
            if (count($record) !== $columnCount) {
                throw new InvalidArgumentException('Each logbook export row must match the export columns.');
            }

            foreach ($record as $columnIndex => $value) {
                $text = (string) $value;
                foreach (preg_split('/\R/u', $text) ?: [$text] as $line) {
                    $columnWidths[$columnIndex] = max($columnWidths[$columnIndex], mb_strlen($line));
                }
            }
        }

        $columnsXml = '';
        foreach ($columnWidths as $index => $contentWidth) {
            $width = min(80, max($minimumWidths[$index], $contentWidth + 3));
            $columnNumber = $index + 1;
            $columnsXml .= sprintf(
                '<col min="%d" max="%d" width="%d" customWidth="1"/>',
                $columnNumber,
                $columnNumber,
                $width,
            );
        }

        $rowsXml = '';
        foreach ($records as $rowIndex => $record) {
            $rowNumber = $rowIndex + 1;
            $cellsXml = '';
            foreach ($record as $columnIndex => $value) {
                $columnName = $this->spreadsheetColumnName($columnIndex + 1);
                $cellStyle = $rowIndex === 0 ? 1 : 2;
                $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';
                $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $cellsXml .= sprintf(
                    '<c r="%s%d" s="%d" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                    $columnName,
                    $rowNumber,
                    $cellStyle,
                    $text,
                );
            }
            $rowsXml .= sprintf('<row r="%d">%s</row>', $rowNumber, $cellsXml);
        }

        $lastColumn = $this->spreadsheetColumnName($columnCount);
        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Logbook Export" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<dimension ref="A1:'.$lastColumn.$rowCount.'"/>'
                .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                .'<sheetFormatPr defaultRowHeight="18"/>'
                .'<cols>'.$columnsXml.'</cols>'
                .'<sheetData>'.$rowsXml.'</sheetData>'
                .'<autoFilter ref="A1:'.$lastColumn.$rowCount.'"/>'
                .'</worksheet>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
                .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
                .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs>'
                .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                .'</styleSheet>',
        ];

        $directory = storage_path('app/private');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the private export storage directory.');
        }
        if (! is_writable($directory)) {
            throw new RuntimeException('The private export storage directory is not writable.');
        }

        $path = tempnam($directory, 'logbook-export-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary Excel export file.');
        }

        $archive = new \ZipArchive;
        $archiveOpened = false;
        $keepFile = false;
        try {
            if ($archive->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to open the Excel export archive.');
            }
            $archiveOpened = true;

            foreach ($parts as $name => $contents) {
                if (! $archive->addFromString($name, $contents)) {
                    throw new RuntimeException('Unable to write the Excel export archive.');
                }
            }

            if (! $archive->close()) {
                $archiveOpened = false;
                throw new RuntimeException('Unable to finalize the Excel export archive.');
            }
            $archiveOpened = false;
            $keepFile = true;

            return $path;
        } finally {
            if ($archiveOpened) {
                $archive->close();
            }
            if (! $keepFile && file_exists($path) && ! unlink($path)) {
                throw new RuntimeException('Unable to remove the temporary Excel export file.');
            }
        }
    }

    /**
     * Keep the existing data export structure independent from the import template.
     *
     * @return list<string>
     */
    private function exportHeaders(): array
    {
        return [
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
        ];
    }

    private function spreadsheetColumnName(int $columnNumber): string
    {
        $columnName = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $columnName = chr(65 + $remainder).$columnName;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $columnName;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{inserted:int, skipped:int, errors:int, total:int}
     */
    public function importRows(array $rows): array
    {
        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $normalized = $this->normalizeImportRow($row);

                if ($normalized === null) {
                    $skipped++;

                    continue;
                }

                if (Schema::hasTable('logbook')) {
                    if ($this->legacyDuplicateExists($normalized)) {
                        $skipped++;

                        continue;
                    }

                    $insert = [
                        'full_name' => $normalized['full_name'],
                        'address' => $normalized['address'],
                        'contact_number' => $normalized['contact_number'],
                        'purpose_of_transaction' => $normalized['purpose_of_transaction'],
                        'time_in' => $normalized['time_in'],
                    ];
                    if (Schema::hasColumn('logbook', 'fingerprint_id')) {
                        $insert['fingerprint_id'] = ($normalized['fingerprint_id'] ?? '') !== ''
                            ? $normalized['fingerprint_id']
                            : null;
                    }
                    if (Schema::hasColumn('logbook', 'fp_template')) {
                        $insert['fp_template'] = null;
                    }
                    $classificationColumn = Schema::hasColumn('logbook', 'person_category')
                        ? 'person_category'
                        : (Schema::hasColumn('logbook', 'person_classification') ? 'person_classification' : null);
                    if ($classificationColumn !== null) {
                        $insert[$classificationColumn] = $normalized['person_category'] !== ''
                            ? $normalized['person_category']
                            : null;
                    }
                    foreach ([
                        'transaction_type',
                        'is_authorized_representative',
                        'authorization_letter_verified',
                        'valid_id_verified',
                        'represented_person_name',
                    ] as $column) {
                        if (Schema::hasColumn('logbook', $column) && array_key_exists($column, $normalized)) {
                            $insert[$column] = $normalized[$column];
                        }
                    }
                    DB::table('logbook')->insert($insert);
                } else {
                    if ($this->appDuplicateExists($normalized)) {
                        $skipped++;

                        continue;
                    }

                    LogbookEntry::query()->create([
                        'user_id' => $normalized['full_name'] === null ? null : (int) Auth::id(),
                        'entry_at' => $normalized['time_in'],
                        'full_name' => $normalized['full_name'],
                        'address' => $normalized['address'],
                        'contact_number' => $normalized['contact_number'],
                        'purpose_of_transaction' => $normalized['purpose_of_transaction'],
                        'description' => $normalized['purpose_of_transaction'] !== null && $normalized['purpose_of_transaction'] !== ''
                            ? $normalized['purpose_of_transaction']
                            : 'Imported logbook entry',
                        'person_category' => $normalized['person_category'] !== '' ? $normalized['person_category'] : null,
                        'transaction_type' => $normalized['transaction_type'] !== '' ? $normalized['transaction_type'] : null,
                        'is_authorized_representative' => $normalized['is_authorized_representative'],
                        'authorization_letter_verified' => $normalized['authorization_letter_verified'],
                        'valid_id_verified' => $normalized['valid_id_verified'],
                        'represented_person_name' => $normalized['represented_person_name'] !== '' ? $normalized['represented_person_name'] : null,
                    ]);
                }

                $inserted++;
            } catch (InvalidArgumentException) {
                $errors++;
            } catch (\Throwable $exception) {
                report($exception);
                $errors++;
            }
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseCsvUpload(UploadedFile $file): array
    {
        $rows = [];
        $header = null;
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open uploaded file.');
        }

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(
                    fn ($value) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value) ?? (string) $value),
                    $line,
                );

                continue;
            }

            $assoc = [];
            foreach ($header as $index => $name) {
                if ($name === '') {
                    continue;
                }
                $assoc[$name] = $line[$index] ?? null;
            }
            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{full_name:?string,address:?string,contact_number:?string,purpose_of_transaction:?string,time_in:string,person_category:string,transaction_type:?string,is_authorized_representative:?int,authorization_letter_verified:?int,valid_id_verified:?int,represented_person_name:?string,fingerprint_id:string}|null
     */
    private function normalizeImportRow(array $row): ?array
    {
        $name = trim((string) $this->importValue($row, ['Name of User', 'User', 'Full Name', 'Name'], ''));
        $personCategoryRaw = trim((string) $this->importValue($row, ['Person Classification', 'Person Category'], ''));
        $address = trim((string) $this->importValue($row, ['Address'], ''));
        $contactValue = $this->importValue($row, ['Contact Number', 'Contact'], '');
        $purpose = trim((string) $this->importValue($row, ['Purpose of Transaction', 'Purpose'], ''));
        $timeValue = $this->importValue($row, ['Time In'], null);
        $transactionType = trim((string) $this->importValue($row, ['Transaction Type'], ''));
        $representing = trim((string) $this->importValue($row, ['Representing', 'Represented Person Name'], ''));
        $fingerprintId = trim((string) $this->importValue($row, ['Fingerprint ID'], ''));
        $truthy = function (mixed $value): ?int {
            $value = strtolower(trim((string) $value));
            if ($value === '') {
                return null;
            }

            return in_array($value, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
        };
        $isAuthorizedRepresentative = $truthy($this->importValue($row, ['Authorized Representative', 'Is Authorized Representative'], ''));
        $authorizationLetterVerified = $truthy($this->importValue($row, ['Authorization Letter Verified'], ''));
        $validIdVerified = $truthy($this->importValue($row, ['Valid ID Verified'], ''));

        if ($name === '' && $personCategoryRaw === '' && $address === '' && trim((string) $contactValue) === '' && $purpose === ''
            && $transactionType === '' && $representing === '' && $fingerprintId === ''
            && ($isAuthorizedRepresentative ?? 0) === 0 && ($authorizationLetterVerified ?? 0) === 0
            && ($validIdVerified ?? 0) === 0) {
            return null;
        }

        if ($name === '') {
            throw new InvalidArgumentException('Name of User is required for each imported record.');
        }

        $countryCodeValue = $this->importValue($row, ['Country Code', 'Country Calling Code'], $this->defaultContactCountryCode());
        $contact = $this->normalizeImportContactNumber($contactValue, $countryCodeValue);
        $personCategory = match (strtolower($personCategoryRaw)) {
            '' => '',
            'visitor' => 'Visitor',
            'resident' => 'Resident',
            'personnel' => 'Personnel',
            default => throw new InvalidArgumentException('Person Classification must be Visitor, Resident, or Personnel.'),
        };

        $timeIn = $this->parseImportDateTime($timeValue) ?? now()->toDateTimeString();

        return [
            'full_name' => $name !== '' ? $name : null,
            'address' => $address !== '' ? $address : null,
            'contact_number' => $contact,
            'purpose_of_transaction' => $purpose !== '' ? $purpose : null,
            'time_in' => $timeIn,
            'person_category' => $personCategory,
            'transaction_type' => $transactionType !== '' ? $transactionType : null,
            'is_authorized_representative' => $isAuthorizedRepresentative,
            'authorization_letter_verified' => $authorizationLetterVerified,
            'valid_id_verified' => $validIdVerified,
            'represented_person_name' => $representing !== '' ? $representing : null,
            'fingerprint_id' => $fingerprintId,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $aliases
     */
    private function importValue(array $row, array $aliases, mixed $default): mixed
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedKey = preg_replace('/[_\s]+/', ' ', $normalizedKey) ?? $normalizedKey;
            $normalized[$normalizedKey] = $value;
        }

        foreach ($aliases as $alias) {
            $normalizedAlias = strtolower(trim($alias));
            $normalizedAlias = preg_replace('/[_\s]+/', ' ', $normalizedAlias) ?? $normalizedAlias;
            if (array_key_exists($normalizedAlias, $normalized)) {
                return $normalized[$normalizedAlias];
            }
        }

        return $default;
    }

    private function normalizeImportContactNumber(mixed $value, mixed $selectedCountryCode = null): ?string
    {
        $contact = trim((string) $value);
        if ($contact === '') {
            return null;
        }

        $countryCode = '+63';
        $selectedCountryCode = trim((string) $selectedCountryCode);
        if ($selectedCountryCode !== '') {
            preg_match('/\((\+\d+)\)\s*$/', $selectedCountryCode, $selectedCodeMatch);
            if (! isset($selectedCodeMatch[1])) {
                preg_match('/^\+\d+/', $selectedCountryCode, $selectedCodeMatch);
                if (isset($selectedCodeMatch[0])) {
                    $selectedCodeMatch[1] = $selectedCodeMatch[0];
                }
            }
            $candidateCode = $selectedCodeMatch[1] ?? '';
            if (! array_key_exists($candidateCode, $this->contactCountryCodes())) {
                throw new InvalidArgumentException('Choose a supported Country Code.');
            }
            $countryCode = $candidateCode;
        }

        if (str_starts_with($contact, '+')) {
            $matchedCode = false;
            foreach (array_keys($this->contactCountryCodes()) as $supportedCode) {
                if (str_starts_with($contact, $supportedCode)) {
                    $countryCode = $supportedCode;
                    $contact = substr($contact, strlen($supportedCode));
                    $matchedCode = true;
                    break;
                }
            }
            if (! $matchedCode) {
                throw new InvalidArgumentException('Contact Number contains an unsupported country code.');
            }
        }

        $digits = preg_replace('/\s+/', '', $contact);
        if ($digits === null || ! ctype_digit($digits)) {
            throw new InvalidArgumentException('Contact Number must contain digits only, with an optional supported country code.');
        }

        if ($countryCode === '+63' && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        if ($countryCode === '+63' && strlen($digits) !== 11) {
            throw new InvalidArgumentException('Philippines contact numbers must contain exactly 11 digits.');
        }
        if ($countryCode !== '+63' && (strlen($digits) < 4 || strlen($digits) > 15)) {
            throw new InvalidArgumentException('Contact Number must contain between 4 and 15 digits.');
        }

        return $countryCode.' '.$digits;
    }

    private function parseImportDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            if (! is_finite((float) $value) || $value < 1 || $value >= 2958466) {
                throw new InvalidArgumentException('Time In must be a valid date and time.');
            }

            $days = (int) floor($value);
            $seconds = (int) round(($value - $days) * 86400);
            $date = new \DateTimeImmutable('1899-12-30 00:00:00', new \DateTimeZone('UTC'));

            return $date->modify("+{$days} days")->modify("+{$seconds} seconds")->format('Y-m-d H:i:s');
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Time In must be a valid date and time.');
        }

        $value = trim($value);
        $formats = [
            '!n/j/Y g:i:s A',
            '!n/j/Y g:i A',
            '!m/d/Y h:i:s A',
            '!m/d/Y h:i A',
            '!Y-m-d h:i:s A',
            '!Y-m-d h:i A',
            '!n/j/Y G:i:s',
            '!Y-m-d H:i:s',
            '!Y-m-d H:i',
            '!Y-m-d\TH:i:s',
            '!Y-m-d\TH:i',
            '!Y-m-d\TH:i:sP',
        ];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value, new \DateTimeZone('UTC'));
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        throw new InvalidArgumentException('Time In must use a supported date and time format.');
    }

    /**
     * @param  array{full_name:?string,address:?string,contact_number:?string,purpose_of_transaction:?string,time_in:string}  $row
     */
    private function legacyDuplicateExists(array $row): bool
    {
        $time = $row['time_in'] ?? '';

        return DB::table('logbook')
            ->where('full_name', $row['full_name'])
            ->where('address', $row['address'])
            ->where('contact_number', $row['contact_number'])
            ->where('purpose_of_transaction', $row['purpose_of_transaction'])
            ->whereRaw("COALESCE(time_in, '') = ?", [$time])
            ->exists();
    }

    /**
     * @param  array{full_name:?string,address:?string,contact_number:?string,purpose_of_transaction:?string,time_in:string}  $row
     */
    private function appDuplicateExists(array $row): bool
    {
        $time = $row['time_in'];
        $description = $row['purpose_of_transaction'] !== null && $row['purpose_of_transaction'] !== ''
            ? $row['purpose_of_transaction']
            : 'Imported logbook entry';

        $query = LogbookEntry::query()
            ->where('description', $description)
            ->where('full_name', $row['full_name'])
            ->where('address', $row['address'])
            ->where('contact_number', $row['contact_number'])
            ->where('purpose_of_transaction', $row['purpose_of_transaction'])
            ->where('person_category', $row['person_category'] !== '' ? $row['person_category'] : null)
            ->where('transaction_type', $row['transaction_type'] !== '' ? $row['transaction_type'] : null)
            ->where('represented_person_name', $row['represented_person_name'] !== '' ? $row['represented_person_name'] : null);
        foreach ([
            'is_authorized_representative',
            'authorization_letter_verified',
            'valid_id_verified',
        ] as $column) {
            $value = $row[$column];
            $query->where(function ($query) use ($column, $value): void {
                $query->where($column, $value);
                if ($value === null) {
                    $query->orWhere($column, 0);
                }
            });
        }
        if ($time !== null) {
            $query->where('entry_at', $time);
        }

        return $query->exists();
    }

    /**
     * @param  array<int, array<int, string>>  $records
     */
    private function toCsv(array $records): string
    {
        $stream = fopen('php://temp', 'wb+');
        foreach ($records as $record) {
            fputcsv($stream, $record);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
    }
}
