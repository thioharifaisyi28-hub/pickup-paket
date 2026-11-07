<?php
/**
 * helpers.php - Global Helper Functions
 */

/**
 * Load .env file
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || empty($line)) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Get environment variable
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false) {
        return $default;
    }

    return match (strtolower($value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        default            => $value
    };
}

/**
 * Generate URL
 */
function url(string $path = ''): string
{
    return App::url($path);
}

/**
 * Asset URL
 */
function asset(string $path): string
{
    return App::url('/assets/' . ltrim($path, '/'));
}

/**
 * Escape output untuk XSS protection
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF input field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

/**
 * Get flash message
 */
function flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Get current user
 */
function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Check user role
 */
function isAdmin(): bool
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Format date Indonesia
 */
function formatDate(?string $date, string $format = 'd M Y H:i'): string
{
    if (empty($date)) {
        return '-';
    }

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Format date Indonesia Stacked (Day Month \n Year)
 */
function formatDateStacked(?string $date, bool $includeTime = false): string
{
    if (empty($date)) return '-';
    
    $months = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agt',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];

    try {
        $dt = new DateTime($date);
        $d = $dt->format('d');
        $m = $months[$dt->format('m')];
        $y = $dt->format('Y');
        $t = $dt->format('H.i');
        
        $html = "<div style='white-space: nowrap; font-size: 11px; color: #3b82f6;'>";
        $html .= "{$d} {$m} {$y}";
        if ($includeTime) {
            $html .= " <span style='font-weight: 500;'>{$t} WIB</span>";
        }
        $html .= "</div>";
        return $html;
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Generate random package number
 */
function generateNoPaket(): string
{
    $prefix = 'PKT';
    $date   = date('Ymd');
    $rand   = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    return "{$prefix}-{$date}-{$rand}";
}

/**
 * Status badge HTML
 */
function statusBadge(string $status): string
{
    $config = match ($status) {
        'Pending'   => ['label' => 'Pending', 'bg' => 'rgba(245, 158, 11, 0.1)', 'color' => '#f59e0b'],
        'Sudah'     => ['label' => 'Sudah',   'bg' => 'rgba(16, 185, 129, 0.1)', 'color' => '#10b981'],
        'Batal'     => ['label' => 'Batal',   'bg' => 'rgba(239, 68, 68, 0.1)', 'color' => '#ef4444'],
        'Retur'     => ['label' => 'Retur',   'bg' => 'rgba(59, 130, 246, 0.1)', 'color' => '#3b82f6'],
        default     => ['label' => ucfirst($status), 'bg' => '#f1f5f9', 'color' => '#64748b']
    };

    return '<span class="badge" style="background-color: ' . $config['bg'] . '; color: ' . $config['color'] . '; border-radius: 6px; font-weight: 600; padding: 0.35rem 0.6rem; font-size: 11px;">' . e($config['label']) . '</span>';
}

/**
 * Old input value (for form re-population)
 */
function old(string $key, string $default = ''): string
{
    return e($_SESSION['old_input'][$key] ?? $default);
}

/**
 * Set old input
 */
function setOldInput(array $data): void
{
    $_SESSION['old_input'] = $data;
}

/**
 * Clear old input
 */
function clearOldInput(): void
{
    unset($_SESSION['old_input']);
}

/**
 * Class XlsxWriter - Native PHP Excel (.xlsx) Generator
 * Generates beautifully formatted spreadsheets without any external dependencies or shell commands.
 */
class XlsxWriter
{
    private $sheets = [];
    private $mergedCells = [];
    private $colWidths = [];
    private $cellStyles = [];

    public function __construct()
    {
    }

    public function setColWidths(array $widths): void
    {
        $this->colWidths = $widths;
    }

    public function addMergeCell(string $range): void
    {
        $this->mergedCells[] = $range;
    }

    public function setCellStyles(array $styles): void
    {
        $this->cellStyles = $styles;
    }

    public static function colLetter(int $col): string
    {
        $letter = '';
        while ($col >= 0) {
            $letter = chr(($col % 26) + 65) . $letter;
            $col = intval($col / 26) - 1;
        }
        return $letter;
    }

    public function writeSheet(array $data): void
    {
        $this->sheets[] = $data;
    }

    public function download(string $filename): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($this->save($tempFile)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tempFile));
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($tempFile);
            unlink($tempFile);
            exit;
        }
    }

    public function save(string $path): bool
    {
        $files = [
            '_rels/.rels' => $this->getRelsXml(),
            '[Content_Types].xml' => $this->getContentTypesXml(),
            'xl/workbook.xml' => $this->getWorkbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->getWorkbookRelsXml(),
            'xl/styles.xml' => $this->getStylesXml(),
            'xl/worksheets/sheet1.xml' => $this->getSheet1Xml()
        ];

        $zipData = $this->buildZip($files);
        return file_put_contents($path, $zipData) !== false;
    }

    private function buildZip(array $files): string
    {
        $zip = '';
        $cd = '';
        $offset = 0;

        foreach ($files as $name => $data) {
            $nameLen = strlen($name);
            $dataLen = strlen($data);
            $crc = crc32($data);

            // Local File Header
            $lfh = "PK\x03\x04" .
                pack('v', 10) .          // version needed to extract
                pack('v', 0) .           // general purpose bit flag
                pack('v', 0) .           // compression method (0 = stored)
                pack('v', 0) .           // last mod file time
                pack('v', 0) .           // last mod file date
                pack('V', $crc) .        // crc-32
                pack('V', $dataLen) .    // compressed size
                pack('V', $dataLen) .    // uncompressed size
                pack('v', $nameLen) .    // file name length
                pack('v', 0) .           // extra field length
                $name;

            $zip .= $lfh . $data;

            // Central Directory Header
            $cdh = "PK\x01\x02" .
                pack('v', 10) .          // version made by
                pack('v', 10) .          // version needed to extract
                pack('v', 0) .           // general purpose bit flag
                pack('v', 0) .           // compression method
                pack('v', 0) .           // last mod file time
                pack('v', 0) .           // last mod file date
                pack('V', $crc) .        // crc-32
                pack('V', $dataLen) .    // compressed size
                pack('V', $dataLen) .    // uncompressed size
                pack('v', $nameLen) .    // file name length
                pack('v', 0) .           // extra field length
                pack('v', 0) .           // file comment length
                pack('v', 0) .           // disk number start
                pack('v', 0) .           // internal file attributes
                pack('V', 32) .          // external file attributes (archive attribute)
                pack('V', $offset) .     // local header offset
                $name;

            $cd .= $cdh;
            $offset += strlen($lfh) + $dataLen;
        }

        $cdLen = strlen($cd);

        // End of Central Directory
        $eocd = "PK\x05\x06" .
            pack('v', 0) .               // number of this disk
            pack('v', 0) .               // disk with central directory
            pack('v', count($files)) .   // number of central directory records on this disk
            pack('v', count($files)) .   // total number of central directory records
            pack('V', $cdLen) .          // size of central directory
            pack('V', $offset) .         // offset of start of central directory
            pack('v', 0);                // comment length

        return $zip . $cd . $eocd;
    }

    private function getRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\r\n" .
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\r\n" .
            '</Relationships>';
    }

    private function getContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\r\n" .
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\r\n" .
            '  <Default Extension="xml" ContentType="application/xml"/>' . "\r\n" .
            '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\r\n" .
            '  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\r\n" .
            '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\r\n" .
            '</Types>';
    }

    private function getWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\r\n" .
            '  <sheets>' . "\r\n" .
            '    <sheet name="Sheet1" sheetId="1" r:id="rId1"/>' . "\r\n" .
            '  </sheets>' . "\r\n" .
            '</workbook>';
    }

    private function getWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\r\n" .
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\r\n" .
            '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\r\n" .
            '</Relationships>';
    }

    private function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\r\n" .
            '  <fonts count="13">' . "\r\n" .
            '    <font><sz val="11"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="16"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><i/><sz val="10"/><color rgb="FF4B5563"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FF16A34A"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FFDC2626"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FF166534"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><b/><sz val="11"/><color rgb="FF991B1B"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><sz val="11"/><color rgb="FF166534"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '    <font><sz val="11"/><color rgb="FF991B1B"/><name val="Calibri"/><family val="2"/></font>' . "\r\n" .
            '  </fonts>' . "\r\n" .
            '  <fills count="6">' . "\r\n" .
            '    <fill><patternFill patternType="none"/></fill>' . "\r\n" .
            '    <fill><patternFill patternType="gray125"/></fill>' . "\r\n" .
            '    <fill>' . "\r\n" .
            '      <patternFill patternType="solid">' . "\r\n" .
            '        <fgColor rgb="FF1E3A8A"/>' . "\r\n" .
            '        <bgColor indexed="64"/>' . "\r\n" .
            '      </patternFill>' . "\r\n" .
            '    </fill>' . "\r\n" .
            '    <fill>' . "\r\n" .
            '      <patternFill patternType="solid">' . "\r\n" .
            '        <fgColor rgb="FFF8FAFC"/>' . "\r\n" .
            '        <bgColor indexed="64"/>' . "\r\n" .
            '      </patternFill>' . "\r\n" .
            '    </fill>' . "\r\n" .
            '    <fill>' . "\r\n" .
            '      <patternFill patternType="solid">' . "\r\n" .
            '        <fgColor rgb="FFDCFCE7"/>' . "\r\n" .
            '        <bgColor indexed="64"/>' . "\r\n" .
            '      </patternFill>' . "\r\n" .
            '    </fill>' . "\r\n" .
            '    <fill>' . "\r\n" .
            '      <patternFill patternType="solid">' . "\r\n" .
            '        <fgColor rgb="FFFEE2E2"/>' . "\r\n" .
            '        <bgColor indexed="64"/>' . "\r\n" .
            '      </patternFill>' . "\r\n" .
            '    </fill>' . "\r\n" .
            '  </fills>' . "\r\n" .
            '  <borders count="2">' . "\r\n" .
            '    <border><left/><right/><top/><bottom/></border>' . "\r\n" .
            '    <border>' . "\r\n" .
            '      <left style="thin"><color rgb="FFCBD5E1"/></left>' . "\r\n" .
            '      <right style="thin"><color rgb="FFCBD5E1"/></right>' . "\r\n" .
            '      <top style="thin"><color rgb="FFCBD5E1"/></top>' . "\r\n" .
            '      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>' . "\r\n" .
            '    </border>' . "\r\n" .
            '  </borders>' . "\r\n" .
            '  <cellStyleXfs count="1">' . "\r\n" .
            '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\r\n" .
            '  </cellStyleXfs>' . "\r\n" .
            '  <cellXfs count="24">' . "\r\n" .
            '    <!-- 0: Default -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\r\n" .
            '    <!-- 1: Centered with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 2: Alternating Row Centered with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 3: Header Centered with Border and Fill -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="5" fillId="2" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 4: Title Centered -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 5: Subtitle Centered -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 6: Green Status with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="6" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 7: Green Status Alternating with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="6" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 8: Red Status with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="7" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 9: Red Status Alternating with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="7" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 10: Pending Status with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="8" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 11: Pending Status Alternating with Border -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="8" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 12: Footer Label (Bold Left with Border) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="left" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 13: Footer Green Value (Bold Centered with Border) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="6" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 14: Footer Red Value (Bold Centered with Border) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="7" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 15: Footer Standard Value (Bold Centered with Border) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 16: Selected Row Default (Light Green fill, Dark Green regular text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="11" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 17: Selected Row Highlight (Light Green fill, Dark Green bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="9" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 18: Not Selected Row Default (Light Red fill, Dark Red regular text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="12" fillId="5" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 19: Not Selected Row Highlight (Light Red fill, Dark Red bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="10" fillId="5" borderId="1" xfId="0" applyAlignment="1" applyBorder="1" applyFill="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 20: Footer Green Bold Label (Left, Green bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="9" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="left" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 21: Footer Red Bold Label (Left, Red bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="10" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="left" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 22: Footer Green Bold Value (Centered, Green bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="9" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '    <!-- 23: Footer Red Bold Value (Centered, Red bold text) -->' . "\r\n" .
            '    <xf numFmtId="0" fontId="10" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">' . "\r\n" .
            '      <alignment horizontal="center" vertical="center"/>' . "\r\n" .
            '    </xf>' . "\r\n" .
            '  </cellXfs>' . "\r\n" .
            '</styleSheet>';
    }

    private function getSheet1Xml(): string
    {
        $sheetData = $this->sheets[0] ?? [];
        $maxRow = count($sheetData);
        $maxCol = 0;
        foreach ($sheetData as $row) {
            $maxCol = max($maxCol, count($row));
        }
        $ref = 'A1:' . self::colLetter($maxCol - 1) . $maxRow;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n" .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\r\n" .
            '  <dimension ref="' . $ref . '"/>';

        if (!empty($this->colWidths)) {
            $xml .= "\r\n" . '  <cols>';
            foreach ($this->colWidths as $colIdx => $width) {
                $xml .= "\r\n" . '    <col min="' . ($colIdx + 1) . '" max="' . ($colIdx + 1) . '" width="' . $width . '" customWidth="1"/>';
            }
            $xml .= "\r\n" . '  </cols>';
        }

        $xml .= "\r\n" . '  <sheetData>';
        foreach ($sheetData as $rowIdx => $rowData) {
            $rNum = $rowIdx + 1;
            $xml .= "\r\n" . '    <row r="' . $rNum . '">';
            foreach ($rowData as $colIdx => $val) {
                $cLetter = self::colLetter($colIdx);
                $coord = $cLetter . $rNum;
                $styleIdx = $this->cellStyles[$coord] ?? 0;
                $xml .= '<c r="' . $coord . '" s="' . $styleIdx . '"';

                if (is_numeric($val) && !is_string($val)) {
                    $xml .= '><v>' . $val . '</v></c>';
                } else if ($val === null || $val === '') {
                    $xml .= '/>';
                } else {
                    $xml .= ' t="inlineStr"><is><t>' . htmlspecialchars((string)$val, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= "\r\n" . '  </sheetData>';

        if (!empty($this->mergedCells)) {
            $xml .= "\r\n" . '  <mergeCells count="' . count($this->mergedCells) . '">';
            foreach ($this->mergedCells as $range) {
                $xml .= "\r\n" . '    <mergeCell ref="' . $range . '"/>';
            }
            $xml .= "\r\n" . '  </mergeCells>';
        }

        $xml .= "\r\n" . '</worksheet>';
        return $xml;
    }
}

/**
 * Class XlsxReader - Native PHP Excel (.xlsx / .csv) Reader
 * Reads spreadsheet files without any external dependencies.
 * Uses ZipArchive + SimpleXML for .xlsx (built-in PHP).
 * Uses fgetcsv() for .csv (built-in PHP).
 */
class XlsxReader
{
    /**
     * Read a spreadsheet file and return all rows as a 2D array.
     * Auto-detects format by trying XLSX first, then CSV.
     *
     * @param string $filePath Absolute path to the file
     * @return array 2D array of rows
     * @throws RuntimeException if file cannot be read
     */
    public static function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        // Try XLSX first (most common format)
        $rows = self::readXlsx($filePath);
        if ($rows !== null) {
            return $rows;
        }

        // Fallback to CSV
        $rows = self::readCsv($filePath);
        if ($rows !== null) {
            return $rows;
        }

        throw new RuntimeException('Gagal membaca file. Pastikan format file adalah .xlsx atau .csv');
    }

    /**
     * Read .xlsx file using ZipArchive + SimpleXML (sheet1 only)
     *
     * @param string $filePath
     * @return array|null Returns null if not a valid XLSX
     */
    private static function readXlsx(string $filePath): ?array
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }

        $sharedStrings = self::loadSharedStrings($zip);

        // Read the first worksheet (sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            return null;
        }

        $rows = self::parseSheetXml($sheetXml, $sharedStrings);
        $zip->close();
        return empty($rows) ? null : $rows;
    }

    /**
     * Read ALL sheets from .xlsx file.
     * Returns associative array: [sheetName => rows[][]]
     *
     * @param string $filePath
     * @return array|null Returns null if not a valid XLSX
     */
    public static function readAllSheets(string $filePath): ?array
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }

        $sharedStrings = self::loadSharedStrings($zip);

        // Read workbook.xml to get sheet names
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($wbXml === false || $relsXml === false) {
            // Fallback: just read sheet1
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if ($sheetXml === false) return null;
            $rows = self::parseSheetXml($sheetXml, $sharedStrings);
            return empty($rows) ? null : ['Sheet1' => $rows];
        }

        $prev = libxml_use_internal_errors(true);
        $wbDoc = simplexml_load_string($wbXml);
        $relsDoc = simplexml_load_string($relsXml);
        libxml_use_internal_errors($prev);

        if (!$wbDoc || !$relsDoc) {
            $zip->close();
            return null;
        }

        // Build rId => target map
        $rels = [];
        foreach ($relsDoc->Relationship as $rel) {
            $rels[(string)$rel['Id']] = (string)$rel['Target'];
        }

        $allSheets = [];
        foreach ($wbDoc->sheets->sheet as $sheet) {
            $sheetName = (string)$sheet['name'];
            $rId = '';

            // Get r:id from relationship namespace
            foreach ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships') as $k => $v) {
                if ($k === 'id') {
                    $rId = (string)$v;
                    break;
                }
            }
            if (!$rId) {
                $rId = (string)($sheet->attributes('r', true)['id'] ?? '');
            }

            $target = $rels[$rId] ?? '';
            if (!$target) continue;

            $target = ltrim($target, '/');
            $sheetXml = $zip->getFromName('xl/' . $target);
            if ($sheetXml === false) {
                $sheetXml = $zip->getFromName($target);
            }
            if ($sheetXml === false) continue;

            $rows = self::parseSheetXml($sheetXml, $sharedStrings);
            if (!empty($rows)) {
                $allSheets[$sheetName] = $rows;
            }
        }

        $zip->close();
        return empty($allSheets) ? null : $allSheets;
    }

    /**
     * Load shared strings from zip
     */
    private static function loadSharedStrings(ZipArchive $zip): array
    {
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $prev = libxml_use_internal_errors(true);
            $ss = simplexml_load_string($ssXml);
            libxml_use_internal_errors($prev);

            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }
        return $sharedStrings;
    }

    /**
     * Parse a single sheet XML string into rows
     */
    private static function parseSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $prev = libxml_use_internal_errors(true);
        $sheet = simplexml_load_string($sheetXml);
        libxml_use_internal_errors($prev);

        if ($sheet === false || !isset($sheet->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];

            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                $colIndex = self::cellRefToColIndex($ref);

                while (count($rowData) < $colIndex) {
                    $rowData[] = '';
                }

                $value = '';
                $type = (string)$cell['t'];

                if ($type === 's') {
                    $idx = (int)(string)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is->t) ? (string)$cell->is->t : '';
                } elseif (isset($cell->v)) {
                    $value = (string)$cell->v;
                }

                $rowData[$colIndex] = $value;
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Read .csv file using fgetcsv
     *
     * @param string $filePath
     * @return array|null Returns null if not a valid CSV
     */
    private static function readCsv(string $filePath): ?array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }

        // Auto-detect BOM and remove it
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detect delimiter from first line
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3); // Skip BOM again
        }

        $delimiter = ',';
        if ($firstLine !== false) {
            $semicolons = substr_count($firstLine, ';');
            $commas = substr_count($firstLine, ',');
            $tabs = substr_count($firstLine, "\t");

            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);
        return empty($rows) ? null : $rows;
    }

    /**
     * Convert cell reference like "A1", "B2", "AA3" to 0-based column index
     *
     * @param string $cellRef e.g. "A1", "AB12"
     * @return int 0-based column index
     */
    private static function cellRefToColIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($cellRef), $matches);
        $col = $matches[1] ?? 'A';

        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }

        return $index - 1; // 0-based
    }
}
