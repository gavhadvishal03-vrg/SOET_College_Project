<?php
/**
 * Server-Side Document Text Extraction Engine (.txt, .pdf, .docx)
 *
 * Extracts clean, raw plain-text content from uploaded documents:
 * 1. Plain Text (.txt): Direct stream reading.
 * 2. PDF Documents (.pdf): Parses binary PDF text objects (Tj operators) and strips unprintable characters.
 * 3. Microsoft Word (.docx): Unzips OpenXML package and extracts content from word/document.xml.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Services
 */

class DocumentExtractor
{
    /**
     * Extracts plain text from a given file path based on its extension and MIME type.
     *
     * @param string $filePath Absolute filesystem path to target document.
     * @param string $mimeType Upload MIME type.
     * @return string Extracted plain text content.
     */
    public static function extractText(string $filePath, string $mimeType): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'txt' || $mimeType === 'text/plain') {
            return file_get_contents($filePath) ?: '';
        }

        if ($ext === 'pdf' || $mimeType === 'application/pdf') {
            return self::extractPdf($filePath);
        }

        if ($ext === 'docx' || strpos($mimeType, 'wordprocessingml') !== false) {
            return self::extractDocx($filePath);
        }

        return file_get_contents($filePath) ?: '';
    }

    private static function extractPdf(string $filePath): string
    {
        // Simple PHP PDF text stream parser fallback
        $content = file_get_contents($filePath);
        if (!$content) return '';

        preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches);
        if (!empty($matches[1])) {
            return implode(' ', $matches[1]);
        }

        // Strip non-printable ASCII
        $clean = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $content);
        return trim(substr($clean, 0, 5000));
    }

    private static function extractDocx(string $filePath): string
    {
        if (!class_exists('ZipArchive')) {
            return 'DocxZipNotAvailable';
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlData = $zip->getFromIndex($index);
                $zip->close();
                return strip_tags($xmlData);
            }
            $zip->close();
        }

        return '';
    }
}
