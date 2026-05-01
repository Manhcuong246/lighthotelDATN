<?php

namespace App\Support;

/**
 * Sửa chữ Việt / UTF-8 bị vỡ do import hoặc charset sai (Latin1 / CP1252 / double-encoding).
 */
final class MojibakeUtf8Repair
{
    /** Ký tự “rác” thường gặp khi UTF-8 đọc sai */
    private const MOJIBAKE_CHARS_REGEX = '/ß|¼|½|¾|┐|└|┘|├|╗|║|╣|╚|╔|╩|╦|╠|═|╬|¦|§|¨|©|ª|«|¬|®|¯|°|±|²|³|µ|¶|·|¸|¹|º|»|¿|×|Þ|Ÿ|‰|€|™|‹|›|Š|Œ|Ž|š|œ|ž/u';

    private const LATIN_EXTENDED_RUN = '/[\x{00C0}-\x{024F}]{3,}/u';

    private function __construct() {}

    /** Trùng mẫu rõ (box drawing, ký tự lạ từ CP1252…) */
    public static function looksSuspicious(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (preg_match(self::MOJIBAKE_CHARS_REGEX, $value)) {
            return true;
        }

        if (preg_match('/[\x{2500}-\x{257F}\x{2550}-\x{256C}]/u', $value)) {
            return true;
        }

        if (preg_match('/Ã|Â|Ä|Å|Ð|Ý|ý|Þ/u', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Chuỗi có khả năng là UTF-8 “đội” / đọc sai (không cần có ╗ hay box drawing).
     */
    public static function looksProbableMojibake(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (self::looksSuspicious($value)) {
            return true;
        }

        // Dấu nháy / gạch ngang kiểu Windows-1252 hiểu nhầm UTF-8
        if (preg_match('/â€™|â€œ|â€”|â€“|â€˜|â€œ|â€|Â©|Â®|Ã—|Ã©|Ã¨|Ã¬|Ã³|Ãº|Ã¢|Ãª|Ã´|Ã¡|Ã£|Ãµ|Ã¼/u', $value)) {
            return true;
        }

        // Hai-byte classic: Ã + ký tự (UTF-8 đọc sai)
        if (preg_match('/Ã./us', $value) || preg_match('/Â./us', $value)) {
            return true;
        }

        // Chuỗi Latin mở rộng dài mà gần như không có chữ Việt → thử sửa (nhiều tên địa phương / mô tả vỡ kiểu khác)
        if (mb_strlen($value) >= 12
            && preg_match(self::LATIN_EXTENDED_RUN, $value)
            && self::countVietnameseLetters($value) === 0
            && preg_match('/[^\x00-\x7F]/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Cột thường chứa tiếng Việt tự nhiên — thử repair khi có ký tự non-ASCII.
     */
    public static function columnLikelyNaturalLanguage(string $column): bool
    {
        $c = strtolower($column);

        if (preg_match('/slug|token|password|_hash$|^hash$|path|url|^email$|mime|extension|filename|username|nickname$/i', $c)) {
            return false;
        }

        if ($c === 'name' || preg_match('/_name$/', $c)) {
            return true;
        }

        foreach ([
            'title', 'description', 'notes', 'address', 'content', 'body', 'comment', 'message',
            'reason', 'detail', 'bio', 'introduction', 'headline', 'subtitle', 'caption', 'label',
            'city', 'district', 'ward', 'province', 'country', 'hotel_name',
        ] as $w) {
            if ($c === $w || str_ends_with($c, '_'.$w) || str_starts_with($c, $w.'_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return non-empty-string|null
     */
    public static function repair(string $value, bool $tryEvenIfNotProbable = false): ?string
    {
        if ($value === '' || ! mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        if (! $tryEvenIfNotProbable && ! self::looksProbableMojibake($value)) {
            return null;
        }

        $originalScore = self::scoreText($value);
        $origPen = self::mojibakePenalty($value);
        $origViet = self::countVietnameseLetters($value);

        $candidates = self::collectCandidatesDeep($value);
        $best = null;

        foreach ($candidates as $cand) {
            if ($cand === '' || $cand === $value || ! mb_check_encoding($cand, 'UTF-8')) {
                continue;
            }
            if (preg_match('/\x{FFFD}/u', $cand)) {
                continue;
            }

            $sc = self::scoreText($cand);
            $pen = self::mojibakePenalty($cand);
            $viet = self::countVietnameseLetters($cand);

            $acceptable = $sc > $originalScore
                || ($viet > $origViet && $pen <= $origPen + 2)
                || ($pen < $origPen && $viet >= max(0, $origViet - 2));

            if (! $acceptable) {
                continue;
            }

            if ($best === null || self::candidateDominates($cand, $best)) {
                $best = $cand;
            }
        }

        return ($best !== null && $best !== $value) ? $best : null;
    }

    private static function candidateDominates(string $a, string $b): bool
    {
        $sa = self::scoreText($a);
        $sb = self::scoreText($b);
        if ($sa !== $sb) {
            return $sa > $sb;
        }

        return self::mojibakePenalty($a) < self::mojibakePenalty($b);
    }

    /**
     * Giống repair nhưng áp dụng gate theo cột (natural language).
     *
     * @return non-empty-string|null
     */
    public static function repairForColumn(string $value, string $columnName, bool $forceAllCells): ?string
    {
        $try = $forceAllCells
            || self::looksProbableMojibake($value)
            || (self::columnLikelyNaturalLanguage($columnName) && preg_match('/[^\x00-\x7F]/', $value));

        if (! $try) {
            return null;
        }

        return self::repair($value, true);
    }

    /**
     * @return list<string>
     */
    private static function collectCandidatesDeep(string $root): array
    {
        $out = [];
        $seen = [];
        $queue = [$root];
        $maxNodes = 400;

        for ($wave = 0; $wave < 5 && count($out) < $maxNodes; $wave++) {
            $next = [];
            foreach ($queue as $node) {
                foreach (self::candidateStrings($node) as $c) {
                    $k = md5($c);
                    if (isset($seen[$k])) {
                        continue;
                    }
                    $seen[$k] = true;
                    $out[] = $c;
                    $next[] = $c;
                    if (count($out) >= $maxNodes) {
                        break 2;
                    }
                }
            }
            $queue = $next;
            if ($queue === []) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function candidateStrings(string $value): array
    {
        $encodings = ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'CP1258'];

        $out = [];
        foreach ($encodings as $encoding) {
            $t = @mb_convert_encoding($value, 'UTF-8', $encoding);
            if ($t !== false && $t !== '') {
                $out[] = $t;
            }
        }

        $step = @mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
        if ($step !== false && $step !== '') {
            $t = @mb_convert_encoding($step, 'UTF-8', 'ISO-8859-1');
            if ($t !== false && $t !== '') {
                $out[] = $t;
            }
        }

        $step2 = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        if ($step2 !== false && $step2 !== '') {
            $t = @mb_convert_encoding($step2, 'UTF-8', 'Windows-1252');
            if ($t !== false && $t !== '') {
                $out[] = $t;
            }
        }

        $step3 = @mb_convert_encoding($value, 'CP1258', 'UTF-8');
        if ($step3 !== false && $step3 !== '') {
            $t = @mb_convert_encoding($step3, 'UTF-8', 'CP1258');
            if ($t !== false && $t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    private static function scoreText(string $s): int
    {
        $viet = self::countVietnameseLetters($s);
        $penalty = self::mojibakePenalty($s);

        return $viet * 22 - $penalty * 14 + min(mb_strlen($s), 120);
    }

    private static function countVietnameseLetters(string $s): int
    {
        $n = preg_match_all(
            '/[ăâđêôơưáàảãạấầẩẫậắằẳẵặéèẻẽẹếềểễệíìỉĩịóòỏõọốồổỗộớờởỡợúùủũụứừửữựýỳỷỹỵĂÂĐÊÔƠƯÁÀẢÃẠẤẦẨẪẬẮẰẲẴẶÉÈẺẼẸẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌỐỒỔỖỘỚỜỞỠỢÚÙỦŨỤỨỪỬỮỰÝỲỶỸỴ]/u',
            $s
        );

        return is_int($n) ? $n : 0;
    }

    private static function mojibakePenalty(string $s): int
    {
        $pen = 0;
        if (preg_match_all(self::MOJIBAKE_CHARS_REGEX, $s, $m)) {
            $pen += count($m[0]);
        }
        $bd = preg_match_all('/[\x{2500}-\x{257F}\x{2550}-\x{256C}]/u', $s);
        if (is_int($bd)) {
            $pen += $bd * 2;
        }
        if (preg_match_all('/Ã.|Â./us', $s, $m2)) {
            $pen += min(count($m2[0]), 20);
        }

        return $pen;
    }
}
