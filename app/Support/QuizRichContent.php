<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rich quiz fields: optional pasted images as markdown ![alt](data:image/...;base64,...)
 * and safe HTML output + KaTeX-friendly text spans.
 */
final class QuizRichContent
{
    /** ~2.5M chars base64 ≈ <2MB nhị phân — LONGTEXT cho phép lớn hơn nhiều */
    private const MAX_DATA_URI_CHARS = 2500000;

    public static function sanitizeForStorage(string $content): string
    {
        $content = self::htmlImagesToMarkdown($content);
        $content = strip_tags($content, '<img>');
        $content = self::htmlImagesToMarkdown($content);
        $content = self::normalizeMarkdownImageDataUris($content);
        $content = self::clampOversizedDataUris($content);

        return trim($content);
    }

    /**
     * Đáp án trắc nghiệm: chỉ chữ / công thức — bỏ ảnh nhúng (markdown, HTML, data-URI thô).
     */
    public static function sanitizePlainAnswerForStorage(string $content): string
    {
        $content = self::htmlImagesToMarkdown($content);
        $content = strip_tags($content);
        $content = self::replaceMarkdownImagesPlain($content, '');
        $content = self::stripStandaloneDataImageUris($content);

        return trim($content);
    }

    public static function toHtml(string $content): string
    {
        if ($content === '') {
            return '';
        }

        return self::htmlFromMarkdownImagesLinear($content);
    }

    public static function plainTextPreview(string $content, int $maxLen = 150): string
    {
        $flat = self::replaceMarkdownImagesPlain($content, '[Ảnh]');
        $flat = strip_tags($flat);
        if (mb_strlen($flat, 'UTF-8') <= $maxLen) {
            return $flat;
        }

        return mb_substr($flat, 0, $maxLen, 'UTF-8') . '…';
    }

    public static function toPlainTextForExport(string $content): string
    {
        $flat = self::replaceMarkdownImagesPlain($content, '[Hình ảnh]');

        return trim(strip_tags($flat));
    }

    private static function escapeChunk(string $text): string
    {
        return nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * Linear scan: avoids PCRE limits on very long base64 (preg_split/preg_match can fail).
     */
    private static function htmlFromMarkdownImagesLinear(string $content): string
    {
        $out = '';
        $len = strlen($content);
        $i = 0;

        while ($i < $len) {
            $pos = strpos($content, '![', $i);
            if ($pos === false) {
                $out .= self::escapeChunk(substr($content, $i));
                break;
            }

            $out .= self::escapeChunk(substr($content, $i, $pos - $i));
            $altStart = $pos + 2;
            $altEnd = strpos($content, ']', $altStart);
            if ($altEnd === false) {
                $out .= self::escapeChunk(substr($content, $pos, 2));
                $i = $pos + 2;
                continue;
            }

            if ($altEnd + 1 >= $len || $content[$altEnd + 1] !== '(') {
                $out .= self::escapeChunk(substr($content, $pos, $altEnd - $pos + 1));
                $i = $altEnd + 1;
                continue;
            }

            $uriStart = $altEnd + 2;
            $uriEnd = strpos($content, ')', $uriStart);
            if ($uriEnd === false) {
                $out .= self::escapeChunk(substr($content, $pos));
                break;
            }

            $uri = substr($content, $uriStart, $uriEnd - $uriStart);
            $alt = substr($content, $altStart, $altEnd - $altStart);

            if (self::isSafeImageUri($uri)) {
                $out .= '<img class="img-fluid quiz-inline-img" src="'
                    . htmlspecialchars($uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '" alt="'
                    . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '" loading="lazy">';
            } else {
                $out .= self::escapeChunk(substr($content, $pos, $uriEnd - $pos + 1));
            }

            $i = $uriEnd + 1;
        }

        return $out;
    }

    private static function stripStandaloneDataImageUris(string $content): string
    {
        $marker = 'data:image/';
        $offset = 0;

        while (($start = stripos($content, $marker, $offset)) !== false) {
            $afterMarker = $start + strlen($marker);
            $semi = stripos($content, ';base64,', $afterMarker);
            if ($semi === false) {
                $offset = $start + 1;
                continue;
            }

            $mime = strtolower(substr($content, $afterMarker, $semi - $afterMarker));
            if (! in_array($mime, ['png', 'jpeg', 'jpg', 'gif', 'webp'], true)) {
                $offset = $start + 1;
                continue;
            }

            $payloadStart = $semi + strlen(';base64,');
            $end = $payloadStart;
            $len = strlen($content);
            while ($end < $len) {
                $c = $content[$end];
                if (
                    ($c >= 'A' && $c <= 'Z')
                    || ($c >= 'a' && $c <= 'z')
                    || ($c >= '0' && $c <= '9')
                    || $c === '+' || $c === '/' || $c === '=' || $c === '-' || $c === '_'
                    || $c === "\r" || $c === "\n" || $c === ' ' || $c === "\t"
                ) {
                    $end++;
                    continue;
                }
                break;
            }

            $content = substr_replace($content, '', $start, $end - $start);
            $offset = $start;
        }

        return $content;
    }

    private static function replaceMarkdownImagesPlain(string $content, string $replacement): string
    {
        $len = strlen($content);
        $i = 0;
        $out = '';

        while ($i < $len) {
            $pos = strpos($content, '![', $i);
            if ($pos === false) {
                $out .= substr($content, $i);
                break;
            }

            $out .= substr($content, $i, $pos - $i);
            $altStart = $pos + 2;
            $altEnd = strpos($content, ']', $altStart);
            if ($altEnd === false) {
                $out .= '![';
                $i = $pos + 2;
                continue;
            }

            if ($altEnd + 1 >= $len || $content[$altEnd + 1] !== '(') {
                $out .= substr($content, $pos, $altEnd - $pos + 1);
                $i = $altEnd + 1;
                continue;
            }

            $uriStart = $altEnd + 2;
            $uriEnd = strpos($content, ')', $uriStart);
            if ($uriEnd === false) {
                $out .= substr($content, $pos);
                break;
            }

            if (self::isSafeImageUri(substr($content, $uriStart, max(0, $uriEnd - $uriStart)))) {
                $out .= $replacement;
            } else {
                $out .= substr($content, $pos, $uriEnd - $pos + 1);
            }

            $i = $uriEnd + 1;
        }

        return $out;
    }

    private static function htmlImagesToMarkdown(string $content): string
    {
        $len = strlen($content);
        $i = 0;
        $out = '';

        while ($i < $len) {
            $p = stripos($content, '<img', $i);
            if ($p === false) {
                $out .= substr($content, $i);
                break;
            }

            $out .= substr($content, $i, $p - $i);
            $gt = strpos($content, '>', $p);
            if ($gt === false) {
                $out .= substr($content, $p);
                break;
            }

            $tag = substr($content, $p, $gt - $p + 1);
            $src = null;
            if (preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $tag, $m) === 1) {
                $src = $m[2];
            }

            if ($src !== null && self::isSafeImageUri($src)) {
                $out .= '![](' . $src . ')';
            } else {
                $out .= $tag;
            }

            $i = $gt + 1;
        }

        return $out;
    }

    /**
     * Gộp base64 (bỏ xuống dòng/khoảng trắng thừa) để URI hợp lệ và nhỏ gọn hơn trong src.
     *
     * @return string
     */
    private static function normalizeMarkdownImageDataUris(string $content): string
    {
        $len = strlen($content);
        $i = 0;
        $out = '';

        while ($i < $len) {
            $pos = strpos($content, '![', $i);
            if ($pos === false) {
                $out .= substr($content, $i);
                break;
            }

            $out .= substr($content, $i, $pos - $i);
            $altStart = $pos + 2;
            $altEnd = strpos($content, ']', $altStart);
            if ($altEnd === false) {
                $out .= '![';
                $i = $pos + 2;
                continue;
            }

            if ($altEnd + 1 >= $len || $content[$altEnd + 1] !== '(') {
                $out .= substr($content, $pos, $altEnd - $pos + 1);
                $i = $altEnd + 1;
                continue;
            }

            $uriStart = $altEnd + 2;
            $uriEnd = strpos($content, ')', $uriStart);
            if ($uriEnd === false) {
                $out .= substr($content, $pos);
                break;
            }

            $uri = substr($content, $uriStart, $uriEnd - $uriStart);
            $uri = self::compactDataImageUri($uri);

            $out .= '!['
                . substr($content, $altStart, $altEnd - $altStart)
                . '](' . $uri . ')';

            $i = $uriEnd + 1;
        }

        return $out;
    }

    private static function clampOversizedDataUris(string $content): string
    {
        $len = strlen($content);
        $i = 0;
        $out = '';

        while ($i < $len) {
            $pos = strpos($content, '![', $i);
            if ($pos === false) {
                $out .= substr($content, $i);
                break;
            }

            $out .= substr($content, $i, $pos - $i);
            $altStart = $pos + 2;
            $altEnd = strpos($content, ']', $altStart);
            if ($altEnd === false) {
                $out .= '![';
                $i = $pos + 2;
                continue;
            }

            if ($altEnd + 1 >= $len || $content[$altEnd + 1] !== '(') {
                $out .= substr($content, $pos, $altEnd - $pos + 1);
                $i = $altEnd + 1;
                continue;
            }

            $uriStart = $altEnd + 2;
            $uriEnd = strpos($content, ')', $uriStart);
            if ($uriEnd === false) {
                $out .= substr($content, $pos);
                break;
            }

            $full = substr($content, $pos, $uriEnd - $pos + 1);
            $uri = substr($content, $uriStart, $uriEnd - $uriStart);

            if (
                substr_compare($uri, 'data:image/', 0, 11, true) === 0
                && strlen($uri) > self::MAX_DATA_URI_CHARS
            ) {
                $out .= '[Ảnh đã bỏ: vượt dung lượng cho phép — hãy chèn ảnh nhỏ hơn hoặc giảm độ phân giải]';
            } else {
                $out .= $full;
            }

            $i = $uriEnd + 1;
        }

        return $out;
    }

    /**
     * Gộp payload base64 (không dùng preg trên toàn bộ chuỗi — tránh giới hạn PCRE).
     */
    private static function compactDataImageUri(string $uri): string
    {
        if (substr_compare($uri, 'data:image/', 0, 11, true) !== 0) {
            return $uri;
        }

        $rest = substr($uri, 11);
        $marker = ';base64,';
        $semiPos = stripos($rest, $marker);
        if ($semiPos === false) {
            return $uri;
        }

        $mime = strtolower(substr($rest, 0, $semiPos));
        if (! in_array($mime, ['png', 'jpeg', 'jpg', 'gif', 'webp'], true)) {
            return $uri;
        }

        $payloadStart = 11 + $semiPos + strlen($marker);
        $payload = substr($uri, $payloadStart);
        $payload = str_replace(["\r", "\n", "\t", ' '], '', $payload);

        return 'data:image/' . $mime . ';base64,' . $payload;
    }

    private static function isSafeDataUri(string $uri): bool
    {
        if ($uri === '' || strlen($uri) > self::MAX_DATA_URI_CHARS) {
            return false;
        }

        if (substr_compare($uri, 'data:image/', 0, 11, true) !== 0) {
            return false;
        }

        $rest = substr($uri, 11);
        $marker = ';base64,';
        $semiPos = stripos($rest, $marker);
        if ($semiPos === false) {
            return false;
        }

        $mime = strtolower(substr($rest, 0, $semiPos));
        if (! in_array($mime, ['png', 'jpeg', 'jpg', 'gif', 'webp'], true)) {
            return false;
        }

        $payloadStart = 11 + $semiPos + strlen($marker);
        $payload = substr($uri, $payloadStart);
        $payload = str_replace(["\r", "\n", "\t", ' '], '', $payload);

        return $payload !== '' && self::isValidBase64Payload($payload);
    }

    private static function isSafeImageUri(string $uri): bool
    {
        $trimmed = trim($uri);
        if ($trimmed === '') {
            return false;
        }

        if (str_starts_with(strtolower($trimmed), 'data:image/')) {
            return self::isSafeDataUri($trimmed);
        }

        if (preg_match('#^(?:/|https?://)[^\s<>"\']+\.(?:png|jpe?g|gif|webp)(?:\?[^\s<>"\']*)?$#iu', $trimmed) === 1) {
            return true;
        }

        return false;
    }

    private static function isValidBase64Payload(string $payload): bool
    {
        $n = strlen($payload);
        for ($i = 0; $i < $n; $i++) {
            $c = $payload[$i];
            if (
                ($c >= 'A' && $c <= 'Z')
                || ($c >= 'a' && $c <= 'z')
                || ($c >= '0' && $c <= '9')
                || $c === '+' || $c === '/' || $c === '-' || $c === '_' || $c === '='
            ) {
                continue;
            }

            return false;
        }

        return true;
    }
}
