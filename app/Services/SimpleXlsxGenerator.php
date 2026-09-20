<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxGenerator
{
    /**
     * Genera un archivo .xlsx nativo (OpenXML) formateado para lectura limpia en Microsoft Excel.
     *
     * @param string $sheetName Nombre de la hoja de cálculo
     * @param array $headers Encabezados de las columnas
     * @param array $rows Matriz de filas de datos
     * @param array $columnWidths Anchos aproximados por columna
     * @return string Ruta del archivo temporal .xlsx generado
     */
    public static function create(string $sheetName, array $headers, array $rows, array $columnWidths = []): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'judigest_xlsx_') . '.xlsx';

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No se pudo crear el archivo temporal .xlsx en {$tempFile}");
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', self::getContentTypesXml());

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', self::getRootRelsXml());

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::getWorkbookRelsXml());

        // 4. xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', self::getWorkbookXml($sheetName));

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', self::getStylesXml());

        // 6. xl/worksheets/sheet1.xml
        $zip->addFromString('xl/worksheets/sheet1.xml', self::getSheetXml($headers, $rows, $columnWidths));

        $zip->close();

        return $tempFile;
    }

    protected static function getContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n"
            . '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n"
            . '  <Default Extension="xml" ContentType="application/xml"/>' . "\n"
            . '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n"
            . '  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n"
            . '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n"
            . '</Types>';
    }

    protected static function getRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
            . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n"
            . '</Relationships>';
    }

    protected static function getWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n"
            . '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n"
            . '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n"
            . '</Relationships>';
    }

    protected static function getWorkbookXml(string $sheetName): string
    {
        $escapedSheetName = htmlspecialchars($sheetName, ENT_XML1, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n"
            . '  <sheets>' . "\n"
            . '    <sheet name="' . $escapedSheetName . '" sheetId="1" r:id="rId1"/>' . "\n"
            . '  </sheets>' . "\n"
            . '</workbook>';
    }

    protected static function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n"
            . '  <fonts count="5">' . "\n"
            . '    <font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>' . "\n" // 0: Normal
            . '    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' . "\n" // 1: Header blanco
            . '    <font><b/><sz val="10"/><color rgb="FF991B1B"/><name val="Calibri"/></font>' . "\n" // 2: Crítico rojo negrita
            . '    <font><b/><sz val="10"/><color rgb="FF92400E"/><name val="Calibri"/></font>' . "\n" // 3: Sospechoso ámbar negrita
            . '    <font><b/><sz val="10"/><color rgb="FF065F46"/><name val="Calibri"/></font>' . "\n" // 4: Éxito verde negrita
            . '  </fonts>' . "\n"
            . '  <fills count="6">' . "\n"
            . '    <fill><patternFill patternType="none"/></fill>' . "\n" // 0
            . '    <fill><patternFill patternType="gray125"/></fill>' . "\n" // 1
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FF185FA5"/></patternFill></fill>' . "\n" // 2: Header azul corporativo #185FA5
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>' . "\n" // 3: Fondo rojo suave
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>' . "\n" // 4: Fondo ámbar suave
            . '    <fill><patternFill patternType="solid"><fgColor rgb="FFECFDF5"/></patternFill></fill>' . "\n" // 5: Fondo verde suave
            . '  </fills>' . "\n"
            . '  <borders count="2">' . "\n"
            . '    <border><left/><right/><top/><bottom/><diagonal/></border>' . "\n"
            . '    <border>' . "\n"
            . '      <left style="thin"><color rgb="FFCBD5E1"/></left>' . "\n"
            . '      <right style="thin"><color rgb="FFCBD5E1"/></right>' . "\n"
            . '      <top style="thin"><color rgb="FFCBD5E1"/></top>' . "\n"
            . '      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>' . "\n"
            . '    </border>' . "\n"
            . '  </borders>' . "\n"
            . '  <cellStyleXfs count="1">' . "\n"
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n"
            . '  </cellStyleXfs>' . "\n"
            . '  <cellXfs count="9">' . "\n"
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\n" // 0: Default
            . '    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' . "\n" // 1: Header
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' . "\n" // 2: Texto izquierda
            . '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" // 3: Centrado normal
            . '    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" // 4: Pill Fallido / Crítico
            . '    <xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" // 5: Pill Sospechoso / Ámbar
            . '    <xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" // 6: Pill Exitoso / Verde
            . '    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' . "\n" // 7: Fila con fondo rojo suave
            . '    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' . "\n" // 8: Fila con fondo ámbar suave
            . '  </cellXfs>' . "\n"
            . '</styleSheet>';
    }

    protected static function getSheetXml(array $headers, array $rows, array $columnWidths): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Definición de anchos de columna automáticos/personalizados
        if (!empty($columnWidths)) {
            $xml .= '  <cols>' . "\n";
            foreach ($columnWidths as $colIndex => $width) {
                $colNum = $colIndex + 1;
                $xml .= '    <col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>' . "\n";
            }
            $xml .= '  </cols>' . "\n";
        }

        $xml .= '  <sheetData>' . "\n";

        // 1. Fila de Encabezados (Fila 1)
        $xml .= '    <row r="1" ht="28" customHeight="1">' . "\n";
        foreach ($headers as $colIndex => $headerText) {
            $cellRef = self::getColumnLetter($colIndex) . '1';
            $escaped = htmlspecialchars((string) $headerText, ENT_XML1, 'UTF-8');
            $xml .= '      <c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>' . "\n";
        }
        $xml .= '    </row>' . "\n";

        // 2. Filas de Datos (A partir de la Fila 2)
        $rowIndex = 2;
        foreach ($rows as $row) {
            $isCritica = !empty($row['_es_critica']);
            $isFallido = !empty($row['_es_fallido']);

            // Estilo base de fondo para la fila
            $rowStyle = 2; // Normal izquierda
            if ($isFallido) {
                $rowStyle = 7; // Fondo rojo suave
            } elseif ($isCritica) {
                $rowStyle = 8; // Fondo ámbar suave
            }

            $xml .= '    <row r="' . $rowIndex . '">' . "\n";
            $colIndex = 0;

            foreach ($row as $key => $cellValue) {
                // Omitir metadatos internos de control
                if (str_starts_with($key, '_')) {
                    continue;
                }

                $cellRef = self::getColumnLetter($colIndex) . $rowIndex;
                $colIndex++;

                // Determinar el estilo visual adecuado por tipo de campo
                $style = $rowStyle;

                if ($key === 'id') {
                    $style = 3; // Centrado
                } elseif ($key === 'resultado') {
                    $valLower = mb_strtolower((string) $cellValue);
                    if ($valLower === 'fallido') {
                        $style = 4; // Pill Rojo
                    } elseif ($valLower === 'exitoso') {
                        $style = 6; // Pill Verde
                    } else {
                        $style = 3;
                    }
                } elseif ($key === 'nivel_riesgo') {
                    $valUpper = mb_strtoupper((string) $cellValue);
                    if ($valUpper === 'ALTO') {
                        $style = 4; // Rojo
                    } elseif ($valUpper === 'MEDIO') {
                        $style = 5; // Ámbar
                    } else {
                        $style = 3; // Centrado
                    }
                } elseif (in_array($key, ['fecha_hora', 'ip', 'rol'], true)) {
                    $style = 3; // Centrado
                }

                if (is_numeric($cellValue) && !str_starts_with((string) $cellValue, '0') && $key === 'id') {
                    $xml .= '      <c r="' . $cellRef . '" s="' . $style . '"><v>' . $cellValue . '</v></c>' . "\n";
                } else {
                    $escaped = htmlspecialchars((string) ($cellValue ?? ''), ENT_XML1, 'UTF-8');
                    $xml .= '      <c r="' . $cellRef . '" s="' . $style . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>' . "\n";
                }
            }

            $xml .= '    </row>' . "\n";
            $rowIndex++;
        }

        $xml .= '  </sheetData>' . "\n";
        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * Convierte un índice numérico de columna (0-indexed) a letra de Excel (0 -> A, 1 -> B, 26 -> AA).
     */
    protected static function getColumnLetter(int $colIndex): string
    {
        $letter = '';
        $colIndex++;

        while ($colIndex > 0) {
            $modulo = ($colIndex - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $colIndex = (int) (($colIndex - $modulo) / 26);
        }

        return $letter;
    }
}
