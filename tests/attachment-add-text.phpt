--TEST--
addAttachment() refuses a name or description PDFium would read back differently: one with the escape character, or starting with characters a PDF reader takes as a byte order mark when PDFium would store them as single bytes
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf());
foreach ([["a\x1Bb", null], ["\u{FE}\u{FF}n.txt", null], ["\u{EF}\u{BB}\u{BF}n.txt", null], ["\u{FF}\u{FE}n.txt", null], ['ok', "a\x1Bb"], ['ok', "\u{FE}\u{FF}d"], ['ok', "\u{FF}\u{FE}d"], ['ok', "\u{EF}\u{BB}\u{BF}d"]] as [$n, $d]) {
    try { $doc->addAttachment($n, 'x', $d); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump($doc->attachmentCount());
$a = $doc->addAttachment("a\u{FE}\u{FF}.txt", 'x', "\u{FEFF}d \u{FE}");
var_dump($a->name() === "a\u{FE}\u{FF}.txt", $a->metadata()->description === "\u{FEFF}d \u{FE}");
$b = $doc->addAttachment("\u{FF}\u{FE}-\u{437}\u{432}\u{456}\u{442}.txt", 'x', "\u{FE}\u{FF}\u{43E}\u{43F}\u{438}\u{441}");
var_dump($b->name() === "\u{FF}\u{FE}-\u{437}\u{432}\u{456}\u{442}.txt", $b->metadata()->description === "\u{FE}\u{FF}\u{43E}\u{43F}\u{438}\u{441}");
?>
--EXPECT--
Pdfium\Document::addAttachment(): Argument #1 ($name) must not contain the escape character (U+001B)
Pdfium\Document::addAttachment(): Argument #1 ($name) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::addAttachment(): Argument #1 ($name) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::addAttachment(): Argument #1 ($name) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::addAttachment(): Argument #3 ($description) must not contain the escape character (U+001B)
Pdfium\Document::addAttachment(): Argument #3 ($description) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::addAttachment(): Argument #3 ($description) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::addAttachment(): Argument #3 ($description) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
int(0)
bool(true)
bool(true)
bool(true)
bool(true)
