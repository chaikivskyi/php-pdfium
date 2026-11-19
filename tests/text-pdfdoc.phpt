--TEST--
The byte order mark rule matches what PDFium stores as single bytes: for every character, behind each of the three byte order mark prefixes in turn, a value is refused exactly when PDFium would store it as single bytes, and is read back unchanged when accepted, also after saving where saving could change it
--EXTENSIONS--
pdfium
--FILE--
<?php
function utf8(int $cp): string
{
    if ($cp < 0x80) {
        return chr($cp);
    }
    if ($cp < 0x800) {
        return chr(0xC0 | $cp >> 6) . chr(0x80 | $cp & 0x3F);
    }
    return chr(0xE0 | $cp >> 12) . chr(0x80 | $cp >> 6 & 0x3F) . chr(0x80 | $cp & 0x3F);
}
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$refused = 0;
$escaped = [0x0A, 0x0D, 0x28, 0x29, 0x5C];
$wronglyAccepted = $wronglyRefused = [];
for ($cp = 1; $cp <= 0xFFFF; $cp++) {
    if ($cp === 0x1B || ($cp >= 0xD800 && $cp <= 0xDFFF)) {
        continue;
    }
    $c = utf8($cp);
    $mark = ["\u{FE}\u{FF}", "\u{FF}\u{FE}", "\u{EF}\u{BB}\u{BF}"][$cp % 3];
    try {
        $doc->setLanguage("$mark$c");
        $saved = $cp < 0x300 || in_array($cp >> 8, $escaped, true) || in_array($cp & 0xFF, $escaped, true);
        if ($doc->language() !== "$mark$c"
            || ($saved && Pdfium\Document::fromString($doc->toString())->language() !== "$mark$c")) {
            $wronglyAccepted[] = sprintf('%04X', $cp);
        }
    } catch (ValueError $e) {
        $refused++;
        $doc->setLanguage("a$c");
        if (!preg_match('/\/Lang\s*\(a/', $doc->toString())) {
            $wronglyRefused[] = sprintf('%04X', $cp);
        }
    }
}
var_dump($refused, implode(' ', $wronglyAccepted), implode(' ', $wronglyRefused));
?>
--EXPECT--
int(252)
string(0) ""
string(0) ""
