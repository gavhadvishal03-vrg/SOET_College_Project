<?php
/**
 * Response Formatting Engine
 * Converts raw markdown/text into clean HTML with code blocks, bullet points, tables, and source attribution badges.
 */

class ResponseFormatter
{
    public static function format(string $text, string $source = 'database', string $sourceUrl = ''): string
    {
        $clean = trim($text);

        // 1. Process Code Blocks ```code```
        $clean = preg_replace_callback('/```([a-zA-Z0-9_]*)\n?(.*?)```/s', function ($matches) {
            $lang = !empty($matches[1]) ? htmlspecialchars($matches[1]) : 'code';
            $code = htmlspecialchars(trim($matches[2]));
            return "<div class='code-block-wrapper my-2'><div class='code-block-header d-flex justify-content-between px-3 py-1 bg-dark text-white-50 rounded-top small font-monospace'><span>{$lang}</span><button class='btn btn-link btn-sm text-white-50 p-0 copy-code-btn' onclick='navigator.clipboard.writeText(this.parentNode.nextElementSibling.innerText)'><i class='fa-regular fa-copy me-1'></i>Copy</button></div><pre class='bg-dark text-light p-3 rounded-bottom mb-0 font-monospace' style='max-height:300px; overflow-y:auto;'><code>{$code}</code></pre></div>";
        }, $clean);

        // 2. Process Markdown Tables (| Header 1 | Header 2 |)
        $clean = preg_replace_callback('/((?:^\|.+?\|\r?\n)+)/m', function ($matches) {
            $lines = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
            if (count($lines) < 2) return $matches[0];

            $html = "<div class='table-responsive my-2'><table class='table table-sm table-bordered bg-white shadow-xs small mb-0'><thead>";
            $isHeader = true;
            $headerDone = false;

            foreach ($lines as $line) {
                // Check if separator line (|---|---|)
                if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                    $isHeader = false;
                    $headerDone = true;
                    $html .= "</thead><tbody>";
                    continue;
                }

                $cells = array_values(array_filter(array_map('trim', explode('|', $line)), function($c) { return $c !== ''; }));
                if (empty($cells)) continue;

                $html .= "<tr>";
                foreach ($cells as $cell) {
                    $tag = $isHeader ? 'th' : 'td';
                    $html .= "<{$tag}>" . htmlspecialchars($cell) . "</{$tag}>";
                }
                $html .= "</tr>";
            }

            if (!$headerDone) {
                $html .= "</thead><tbody>";
            }
            $html .= "</tbody></table></div>";
            return $html;
        }, $clean);

        // 3. Process Headers (### Title, ## Title)
        $clean = preg_replace('/^###\s+(.+)$/m', '<h6 class="fw-bold text-primary mt-2 mb-1">$1</h6>', $clean);
        $clean = preg_replace('/^##\s+(.+)$/m', '<h5 class="fw-bold text-primary mt-2 mb-1">$1</h5>', $clean);
        $clean = preg_replace('/^#\s+(.+)$/m', '<h5 class="fw-bold text-primary mt-2 mb-1">$1</h5>', $clean);

        // 4. Process Blockquotes (> quote)
        $clean = preg_replace_callback('/((?:^>\s*.*$\n?)+)/m', function ($matches) {
            $lines = array_map(function($l) { return preg_replace('/^>\s*/', '', trim($l)); }, explode("\n", trim($matches[1])));
            $content = implode("<br>", array_filter($lines));
            return "<blockquote class='border-start border-3 border-warning ps-3 text-muted small my-2'>{$content}</blockquote>";
        }, $clean);

        // 5. Process Bold (**bold**) and Italic (*italic*)
        $clean = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $clean);
        $clean = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $clean);

        // 6. Process Inline Code `code`
        $clean = preg_replace('/`([^`]+)`/', '<code class="bg-light text-danger px-1 py-0.5 rounded font-monospace small">$1</code>', $clean);

        // 7. Process Markdown Links [text](url)
        $clean = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener" class="text-primary font-semibold">$1 <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i></a>', $clean);

        // 8. Process Bullet Lists (* or -)
        $clean = preg_replace_callback('/((?:^[•\*\-]\s+.*$\n?)+)/m', function ($matches) {
            $items = preg_split('/\n/', trim($matches[1]));
            $html = "<ul class='ps-3 mb-2 small'>";
            foreach ($items as $item) {
                $line = preg_replace('/^[•\*\-]\s+/', '', trim($item));
                if (!empty($line)) {
                    $html .= "<li class='mb-1'>{$line}</li>";
                }
            }
            $html .= "</ul>";
            return $html;
        }, $clean);

        // 9. Process Numbered Lists (1., 2.)
        $clean = preg_replace_callback('/((?:^\d+\.\s+.*$\n?)+)/m', function ($matches) {
            $items = preg_split('/\n/', trim($matches[1]));
            $html = "<ol class='ps-3 mb-2 small'>";
            foreach ($items as $item) {
                $line = preg_replace('/^\d+\.\s+/', '', trim($item));
                if (!empty($line)) {
                    $html .= "<li class='mb-1'>{$line}</li>";
                }
            }
            $html .= "</ol>";
            return $html;
        }, $clean);

        // 10. Convert Newlines to Paragraphs
        $paragraphs = preg_split('/\n{2,}/', $clean);
        $formattedHtml = "";
        foreach ($paragraphs as $p) {
            $trimmed = trim($p);
            if (empty($trimmed)) continue;
            if (strpos($trimmed, '<ul') === false && 
                strpos($trimmed, '<ol') === false && 
                strpos($trimmed, '<div class=\'code-block-wrapper\'') === false &&
                strpos($trimmed, '<div class=\'table-responsive') === false &&
                strpos($trimmed, '<blockquote') === false &&
                strpos($trimmed, '<h5') === false &&
                strpos($trimmed, '<h6') === false) {
                $formattedHtml .= "<p class='mb-2'>" . nl2br($trimmed) . "</p>";
            } else {
                $formattedHtml .= $trimmed;
            }
        }

        // 11. Navigation Link (Clean, no internal database queries/AI models mentioned)
        $footer = "";
        if (!empty($sourceUrl)) {
            $footer = "<div class='mt-2 pt-1 border-top border-light text-end'><a href='" . APP_URL . $sourceUrl . "' class='text-primary small text-decoration-none fw-semibold' target='_blank'>View Details <i class='fa-solid fa-arrow-up-right-from-square ms-1' style='font-size:10px;'></i></a></div>";
        }

        return $formattedHtml . $footer;
    }
}
