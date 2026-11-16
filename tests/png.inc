<?php
function png_decode(string $png): array
{
    $pos = 8;
    $idat = '';
    while ($pos < strlen($png)) {
        $len = unpack('N', $png, $pos)[1];
        $type = substr($png, $pos + 4, 4);
        $data = substr($png, $pos + 8, $len);
        if ($type === 'IHDR') {
            ['w' => $width, 'h' => $height, 'type' => $colorType] = unpack('Nw/Nh/Cdepth/Ctype', $data);
        } elseif ($type === 'IDAT') {
            $idat .= $data;
        }
        $pos += 12 + $len;
    }
    $channels = [0 => 1, 2 => 3, 4 => 2, 6 => 4][$colorType];
    $raw = gzuncompress($idat);
    $stride = $width * $channels;
    $rows = [];
    $prev = str_repeat("\0", $stride);
    for ($y = 0; $y < $height; $y++) {
        $filter = ord($raw[$y * ($stride + 1)]);
        $line = substr($raw, $y * ($stride + 1) + 1, $stride);
        $out = '';
        for ($i = 0; $i < $stride; $i++) {
            $a = $i >= $channels ? ord($out[$i - $channels]) : 0;
            $b = ord($prev[$i]);
            $c = $i >= $channels ? ord($prev[$i - $channels]) : 0;
            $pred = match ($filter) {
                0 => 0,
                1 => $a,
                2 => $b,
                3 => intdiv($a + $b, 2),
                4 => png_paeth($a, $b, $c),
            };
            $out .= chr((ord($line[$i]) + $pred) & 0xFF);
        }
        $rows[] = $prev = $out;
    }
    return ['width' => $width, 'height' => $height, 'colorType' => $colorType, 'channels' => $channels, 'rows' => $rows];
}

function png_paeth(int $a, int $b, int $c): int
{
    $p = $a + $b - $c;
    [$pa, $pb, $pc] = [abs($p - $a), abs($p - $b), abs($p - $c)];
    return $pa <= $pb && $pa <= $pc ? $a : ($pb <= $pc ? $b : $c);
}

/** @return list<int> */
function png_pixel(array $img, int $x, int $y): array
{
    return array_values(unpack('C*', substr($img['rows'][$y], $x * $img['channels'], $img['channels'])));
}
