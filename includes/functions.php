<?php
/**
 * Helper Functions
 */

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function setFlash(string $type, string $message): void
{
    Session::flash('alert_type', $type);
    Session::flash('alert_message', $message);
}

function getFlash(): ?array
{
    $type = Session::flash('alert_type');
    $message = Session::flash('alert_message');
    if ($type && $message) {
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

function formatDateTime(?string $datetime): string
{
    if (!$datetime) return 'N/A';
    return date('d M Y, h:i A', strtotime($datetime));
}

function truncate(string $text, int $length = 150): string
{
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

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

function uploadUrl(string $directory, ?string $filename): string
{
    if (!$filename) return APP_URL . '/assets/images/placeholder.png';
    return UPLOAD_URL . $directory . '/' . $filename;
}

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

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateApplicationNumber(): string
{
    return 'SOET' . date('Y') . strtoupper(substr(uniqid(), -6));
}

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

function generateSlug(string $text): string
{
    return slugify($text);
}

function truncateText(string $text, int $length = 150): string
{
    return truncate($text, $length);
}

function formatCurrency(float|int|string $amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

function getSetting(string $key, ?string $default = ''): ?string
{
    $cms = new ContentManager();
    return $cms->getSetting($key, $default);
}
