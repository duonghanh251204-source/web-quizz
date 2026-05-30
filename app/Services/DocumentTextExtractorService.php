<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use ZipArchive;

final class DocumentTextExtractorService
{
    public function extract(string $filePath, string $extension): string
    {
        return match (strtolower($extension)) {
            'txt' => $this->extractTxt($filePath),
            'docx' => $this->extractDocx($filePath),
            'pdf' => $this->extractPdf($filePath),
            default => throw new RuntimeException('Dinh dang file khong duoc ho tro.'),
        };
    }

    private function extractTxt(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('Khong the doc noi dung file TXT.');
        }

        $normalized = $this->normalizeExtractedText((string) $content);
        if ($normalized === '') {
            throw new RuntimeException('Noi dung TXT rong.');
        }

        return $normalized;
    }

    private function extractDocx(string $filePath): string
    {
        $fromPhpWord = $this->extractDocxWithPhpWord($filePath);
        if ($fromPhpWord !== '') {
            return $fromPhpWord;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Khong the mo file DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || trim($xml) === '') {
            throw new RuntimeException('DOCX khong chua noi dung van ban hop le.');
        }

        $text = $this->extractDocxWithParagraphs($xml);
        if ($text === '') {
            $text = $this->extractDocxByTagStripping($xml);
        }

        $text = $this->normalizeExtractedText($text);
        if ($text === '') {
            throw new RuntimeException('Khong trich xuat duoc noi dung tu DOCX.');
        }

        return $text;
    }

    private function extractDocxWithPhpWord(string $filePath): string
    {
        if (!class_exists(IOFactory::class)) {
            return '';
        }

        try {
            $phpWord = IOFactory::load($filePath, 'Word2007');
        } catch (\Throwable) {
            return '';
        }

        $lines = [];
        foreach ($phpWord->getSections() as $section) {
            if (!method_exists($section, 'getElements')) {
                continue;
            }

            foreach ($section->getElements() as $element) {
                $this->appendDocxElementLine($element, $lines);
            }
        }

        return $this->normalizeExtractedText(implode("\n", $lines));
    }

    private function extractPdf(string $filePath): string
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('Server khong cho phep trich xuat PDF tu dong (shell_exec bi tat).');
        }

        $checkCommand = stripos(PHP_OS_FAMILY, 'Windows') === 0 ? 'where pdftotext' : 'command -v pdftotext';
        $toolPath = shell_exec($checkCommand);
        if (!is_string($toolPath) || trim($toolPath) === '') {
            throw new RuntimeException('Thieu cong cu pdftotext de trich xuat PDF.');
        }

        $escapedPath = escapeshellarg($filePath);
        $content = shell_exec("pdftotext -layout {$escapedPath} -");
        $text = $this->normalizeExtractedText((string) $content);

        if ($text === '') {
            throw new RuntimeException('Khong trich xuat duoc noi dung tu PDF.');
        }

        return $text;
    }

    private function extractDocxWithParagraphs(string $xml): string
    {
        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if ($loaded !== true) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphNodes = $xpath->query('//w:p');
        if ($paragraphNodes === false || $paragraphNodes->length === 0) {
            return '';
        }

        $lines = [];

        foreach ($paragraphNodes as $paragraphNode) {
            $textNodes = $xpath->query('.//w:t | .//w:tab | .//w:br | .//w:cr', $paragraphNode);
            if ($textNodes === false) {
                continue;
            }

            $parts = [];
            foreach ($textNodes as $textNode) {
                $localName = (string) $textNode->localName;

                if ($localName === 'tab') {
                    $parts[] = "\t";
                    continue;
                }

                if ($localName === 'br' || $localName === 'cr') {
                    $parts[] = "\n";
                    continue;
                }

                $parts[] = (string) $textNode->nodeValue;
            }

            $line = trim(implode('', $parts));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    private function extractDocxByTagStripping(string $xml): string
    {
        $withLineBreaks = preg_replace('/<\/w:(?:p|tr|tbl|tc|body)>/i', "\n", $xml) ?? $xml;
        $plainText = strip_tags($withLineBreaks);

        return html_entity_decode($plainText, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** @param array<int, string> $lines */
    private function appendDocxElementLine(object $element, array &$lines): void
    {
        $parts = $this->collectElementParts($element);
        $line = trim(implode('', $parts));
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    /**
     * @return array<int, string>
     */
    private function collectElementParts(object $element): array
    {
        $parts = [];

        if (method_exists($element, 'getText')) {
            $text = trim((string) $element->getText());
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        $imageTag = $this->imageTagFromElement($element);
        if ($imageTag !== '') {
            $parts[] = $imageTag;
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                if (!is_object($child)) {
                    continue;
                }
                foreach ($this->collectElementParts($child) as $part) {
                    $parts[] = $part;
                }
            }
        }

        if (method_exists($element, 'getRows')) {
            foreach ($element->getRows() as $row) {
                if (!is_object($row) || !method_exists($row, 'getCells')) {
                    continue;
                }
                foreach ($row->getCells() as $cell) {
                    if (!is_object($cell) || !method_exists($cell, 'getElements')) {
                        continue;
                    }
                    foreach ($cell->getElements() as $child) {
                        if (!is_object($child)) {
                            continue;
                        }
                        foreach ($this->collectElementParts($child) as $part) {
                            $parts[] = $part;
                        }
                    }
                }
            }
        }

        return $parts;
    }

    private function imageTagFromElement(object $element): string
    {
        $binary = $this->extractImageBinaryFromElement($element);
        if ($binary === null) {
            return '';
        }

        $saved = $this->saveQuizImageBinary($binary['content'], $binary['extension']);
        if ($saved === '') {
            return '';
        }

        return '<img src="' . $saved . '" class="img-fluid">';
    }

    /**
     * @return array{content: string, extension: string}|null
     */
    private function extractImageBinaryFromElement(object $element): ?array
    {
        if (method_exists($element, 'getImageStringData')) {
            try {
                $producer = $element->getImageStringData();
                if (is_callable($producer)) {
                    $content = (string) $producer();
                    if ($content !== '') {
                        $mime = method_exists($element, 'getImageType') ? (string) $element->getImageType() : '';
                        return [
                            'content' => $content,
                            'extension' => $this->extensionFromMime($mime),
                        ];
                    }
                }
            } catch (\Throwable) {
            }
        }

        if (!method_exists($element, 'getSource')) {
            return null;
        }

        $source = (string) $element->getSource();
        if ($source === '') {
            return null;
        }

        $content = @file_get_contents($source);
        if (!is_string($content) || $content === '') {
            return null;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $extension = 'png';
        }

        return [
            'content' => $content,
            'extension' => $extension,
        ];
    }

    private function extensionFromMime(string $mime): string
    {
        $normalized = strtolower(trim($mime));
        return match ($normalized) {
            'image/jpeg', 'jpeg', 'jpg' => 'jpg',
            'image/gif', 'gif' => 'gif',
            'image/webp', 'webp' => 'webp',
            default => 'png',
        };
    }

    private function saveQuizImageBinary(string $binary, string $extension): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/public/uploads/quiz_images';
        if (!$this->ensureQuizImageDirectory($uploadDir)) {
            return '';
        }

        try {
            $suffix = bin2hex(random_bytes(5));
        } catch (\Throwable) {
            $suffix = uniqid('', true);
        }
        $filename = 'quiz_' . date('Ymd_His') . '_' . $suffix . '.' . $extension;
        $absolutePath = $uploadDir . '/' . $filename;
        $written = @file_put_contents($absolutePath, $binary);
        if ($written === false) {
            return '';
        }

        return '/public/uploads/quiz_images/' . $filename;
    }

    private function ensureQuizImageDirectory(string $uploadDir): bool
    {
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return false;
        }

        @chmod($uploadDir, 0775);

        return is_writable($uploadDir);
    }

    private function normalizeExtractedText(string $content): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $normalized = preg_replace('/[ \t]+\n/u', "\n", $normalized) ?? $normalized;
        $normalized = preg_replace('/[ \t]{2,}/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace("/\n{3,}/u", "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
    }
}
