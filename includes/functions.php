<?php
/**
 * Global Helper Functions & Utilities
 *
 * Provides shared procedural helper routines for HTTP redirects, session flash banners,
 * date/time formatting, text truncation, URL slug generation, Bootstrap status badges,
 * asset/upload URL resolution with fallback hierarchies, pagination rendering,
 * JSON API responses, currency formatting, and dynamic faculty experience calculation.
 */

/**
 * Redirects the client to a specified URL and terminates script execution.
 *
 * @param string $url Target destination URL
 * @return void
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Sets a flash notification message in the session to be displayed after redirect.
 *
 * @param string $type Alert style ('success', 'danger', 'warning', 'info')
 * @param string $message Alert text message
 * @return void
 */
function setFlash(string $type, string $message): void
{
    Session::flash('alert_type', $type);
    Session::flash('alert_message', $message);
}

/**
 * Retrieves and clears the active flash alert from the session.
 *
 * @return array|null Array containing ['type' => ..., 'message' => ...] or null
 */
function getFlash(): ?array
{
    $type = Session::flash('alert_type');
    $message = Session::flash('alert_message');
    if ($type && $message) {
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Formats a database date string into a standard display date format.
 *
 * @param string|null $date Date string
 * @param string $format PHP date format string (default: 'd M Y')
 * @return string Formatted date or 'N/A'
 */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Formats a database datetime string into date and time with 12-hour AM/PM format.
 *
 * @param string|null $datetime DateTime string
 * @return string Formatted datetime string or 'N/A'
 */
function formatDateTime(?string $datetime): string
{
    if (!$datetime) return 'N/A';
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Truncates a string to a maximum length with an ellipsis appended.
 *
 * @param string $text Raw input text
 * @param int $length Maximum character limit (default: 150)
 * @return string Truncated text
 */
function truncate(string $text, int $length = 150): string
{
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Converts a string into a URL-friendly lowercase slug.
 *
 * @param string $text Source title/name
 * @return string URL safe slug
 */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

/**
 * Generates an HTML Bootstrap badge corresponding to a record's lifecycle status.
 *
 * @param string $status Status string (e.g. 'approved', 'pending', 'rejected', 'active')
 * @return string HTML span badge element
 */
function statusBadge(string $status): string
{
    $colors = [
        'pending' => 'warning', 'submitted' => 'info', 'under_review' => 'primary',
        'approved' => 'success', 'published' => 'success', 'rejected' => 'danger',
        'returned' => 'secondary', 'draft' => 'light', 'verified' => 'info',
        'active' => 'success', 'inactive' => 'secondary', 'unread' => 'danger', 'read' => 'success',
    ];
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

/**
 * Resolves the public web URL for an uploaded or static asset file.
 * Handles HTTP absolute URLs, relative disk paths, and checks a multi-folder fallback
 * hierarchy before falling back to category-specific SVG default placeholders.
 *
 * @param string $directory Primary expected subfolder (e.g. 'faculty', 'news', 'courses')
 * @param string|null $filename Stored filename or path
 * @return string Fully qualified web accessible URL
 */
function uploadUrl(string $directory, ?string $filename): string
{
    $dirKey = strtolower(trim($directory));
    $defaultPlaceholder = match($dirKey) {
        'faculty', 'placements', 'users' => APP_URL . '/assets/images/default_avatar.svg',
        'departments', 'facilities' => APP_URL . '/assets/images/default_building.svg',
        default => APP_URL . '/assets/images/default_banner.svg',
    };

    if (!$filename || trim($filename) === '') {
        return $defaultPlaceholder;
    }

    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }

    $cleanName = basename($filename);
    $root = dirname(__DIR__);

    // If filename was passed as a relative web path starting with uploads/, assets/, or docs/
    $trimmed = ltrim($filename, '/');
    if (str_starts_with($trimmed, 'uploads/') || str_starts_with($trimmed, 'assets/') || str_starts_with($trimmed, 'docs/')) {
        if (file_exists($root . '/' . $trimmed)) {
            return APP_URL . '/' . $trimmed;
        }
    }

    $checkPaths = [
        "uploads/{$directory}/{$cleanName}" => UPLOAD_URL . "{$directory}/{$cleanName}",
        "uploads/{$cleanName}" => UPLOAD_URL . "{$cleanName}",
        "uploads/settings/{$cleanName}" => UPLOAD_URL . "settings/{$cleanName}",
        "uploads/gallery/{$cleanName}" => UPLOAD_URL . "gallery/{$cleanName}",
        "uploads/courses/{$cleanName}" => UPLOAD_URL . "courses/{$cleanName}",
        "uploads/departments/{$cleanName}" => UPLOAD_URL . "departments/{$cleanName}",
        "uploads/faculty/{$cleanName}" => UPLOAD_URL . "faculty/{$cleanName}",
        "uploads/facilities/{$cleanName}" => UPLOAD_URL . "facilities/{$cleanName}",
        "uploads/logos/{$cleanName}" => UPLOAD_URL . "logos/{$cleanName}",
        "uploads/news/{$cleanName}" => UPLOAD_URL . "news/{$cleanName}",
        "uploads/blogs/{$cleanName}" => UPLOAD_URL . "blogs/{$cleanName}",
        "uploads/events/{$cleanName}" => UPLOAD_URL . "events/{$cleanName}",
        "uploads/placements/{$cleanName}" => UPLOAD_URL . "placements/{$cleanName}",
        "uploads/admissions/{$cleanName}" => UPLOAD_URL . "admissions/{$cleanName}",
        "uploads/documents/{$cleanName}" => UPLOAD_URL . "documents/{$cleanName}",
        "assets/uploads/{$directory}/{$cleanName}" => APP_URL . "/assets/uploads/{$directory}/{$cleanName}",
        "assets/uploads/{$cleanName}" => APP_URL . "/assets/uploads/{$cleanName}",
        "assets/images/branding/{$cleanName}" => APP_URL . "/assets/images/branding/{$cleanName}",
        "assets/images/placeholders/{$cleanName}" => APP_URL . "/assets/images/placeholders/{$cleanName}",
        "assets/images/facilities/{$cleanName}" => APP_URL . "/assets/images/facilities/{$cleanName}",
        "assets/images/recruiters/{$cleanName}" => APP_URL . "/assets/images/recruiters/{$cleanName}",
        "assets/images/{$directory}/{$cleanName}" => APP_URL . "/assets/images/{$directory}/{$cleanName}",
        "assets/images/{$cleanName}" => APP_URL . "/assets/images/{$cleanName}",
        "assets/faculty/{$cleanName}" => APP_URL . "/assets/faculty/{$cleanName}",
        "assets/documents/{$cleanName}" => APP_URL . "/assets/documents/{$cleanName}",
        "docs/brochures/{$cleanName}" => APP_URL . "/docs/brochures/{$cleanName}",
        "docs/booklets/{$cleanName}" => APP_URL . "/docs/booklets/{$cleanName}",
    ];

    foreach ($checkPaths as $diskRel => $webUrl) {
        if (file_exists($root . '/' . $diskRel)) {
            return $webUrl;
        }
    }

    return UPLOAD_URL . "{$directory}/{$cleanName}";
}

/**
 * Renders HTML Bootstrap 5 pagination navigation links.
 *
 * @param int $currentPage Current active page
 * @param int $totalPages Total calculated pages
 * @param string $baseUrl Base URL for pagination links
 * @return string HTML pagination markup
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center">';
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    for ($i = 1; $i <= $totalPages; $i++) {
        $html .= '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Emits a JSON response with HTTP status code and terminates script execution.
 *
 * @param array $data Data payload to encode
 * @param int $code HTTP status code (default: 200)
 * @return void
 */
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generates a unique admission application tracking number.
 * Format: SOET + CurrentYear + 6-character unique hex string.
 *
 * @return string Generated application number (e.g. 'SOET2026A1B2C3')
 */
function generateApplicationNumber(): string
{
    return 'SOET' . date('Y') . strtoupper(substr(uniqid(), -6));
}

/**
 * Converts a datetime into a human-readable relative time string (e.g. '5 min ago').
 *
 * @param string $datetime Past datetime string
 * @return string Relative time string
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($datetime);
}

/**
 * Alias for slugify().
 *
 * @param string $text Source text
 * @return string URL slug
 */
function generateSlug(string $text): string
{
    return slugify($text);
}

/**
 * Alias for truncate().
 *
 * @param string $text Raw text
 * @param int $length Character limit
 * @return string Truncated text
 */
function truncateText(string $text, int $length = 150): string
{
    return truncate($text, $length);
}

/**
 * Formats a monetary number into Indian Rupee currency format (₹).
 *
 * @param float|int|string $amount Numeric amount
 * @return string Formatted currency string (e.g. '₹1,50,000.00')
 */
function formatCurrency(float|int|string $amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Convenience helper to fetch a website setting by key.
 *
 * @param string $key Setting key name
 * @param string|null $default Fallback value if setting is not found
 * @return string|null Setting value
 */
function getSetting(string $key, ?string $default = ''): ?string
{
    $cms = new ContentManager();
    return $cms->getSetting($key, $default);
}

/**
 * Calculates real-time auto-updating faculty experience in years.
 * Dynamically computes elapsed years from base year to ensure experience
 * values update automatically each academic calendar year without manual editing.
 *
 * @param array|int|null $facultyOrYears Faculty database record array or integer base experience
 * @param int|null $baseYear Optional reference calendar year (defaults to 2026)
 * @return int Total computed years of experience
 */
function getFacultyExperience(array|int|null $facultyOrYears, ?int $baseYear = null): int
{
    $currentYear = (int)date('Y');

    if (is_array($facultyOrYears)) {
        $baseExp = (int)($facultyOrYears['experience_years'] ?? 0);
        $bYear = !empty($facultyOrYears['experience_base_year']) 
            ? (int)$facultyOrYears['experience_base_year'] 
            : (!empty($facultyOrYears['created_at']) ? (int)date('Y', strtotime($facultyOrYears['created_at'])) : 2026);
    } else {
        $baseExp = (int)($facultyOrYears ?? 0);
        $bYear = $baseYear ?: 2026;
    }

    $diff = max(0, $currentYear - $bYear);
    return max(0, $baseExp + $diff);
}

