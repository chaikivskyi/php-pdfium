--TEST--
setLanguage() changes the loaded document at once and is written out by toString(); the empty string clears it; invalid UTF-8, NUL and values PDFium would read back differently are refused; extracted and merged documents do not carry it
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$bytes = nav_pdf('/MarkInfo << /Marked true >> /Lang (en-US)');
$doc = Pdfium\Document::fromString($bytes);
var_dump($doc->setLanguage('fr'));
$doc->setLanguage('uk-UA');
var_dump($doc->language());
var_dump(Pdfium\Document::fromString($doc->toString())->language());
var_dump(Pdfium\Document::fromString($bytes)->language());
$doc->setLanguage("мова-\u{1F600}");
var_dump(Pdfium\Document::fromString($doc->toString())->language() === "мова-\u{1F600}");
$long = str_repeat('x', 1 << 20);
$doc->setLanguage($long);
var_dump(Pdfium\Document::fromString($doc->toString())->language() === $long);
$doc->setLanguage('');
var_dump($doc->language(), Pdfium\Document::fromString($doc->toString())->language());
$doc->setLanguage('pl');
foreach (["a\xFFb", "a\0b", "a\x1Bb", "\u{FE}\u{FF}en", "\u{FF}\u{FE}en", "\u{EF}\u{BB}\u{BF}en", "\u{FE}\u{FF}\u{2022}\u{131}"] as $bad) {
    try { $doc->setLanguage($bad); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
foreach (["a\u{FE}\u{FF}", "\u{FEFF}en", "\u{FE}", "\u{EF}\u{BB}", "\u{FE}\u{FF}\u{44F}", "\u{FF}\u{FE}en\u{A0}", "\u{EF}\u{BB}\u{BF}\u{1F600}"] as $fine) {
    $doc->setLanguage($fine);
    var_dump($doc->language() === $fine);
}
$doc->setLanguage('pl');
var_dump($doc->language(), $doc->isTagged());
$part = $doc->extractPages([0]);
var_dump($part->language(), $part->isTagged());
var_dump(Pdfium\Document::merge([$doc, $doc])->language());
$plain = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$plain->setLanguage('en');
var_dump(Pdfium\Document::fromString($plain->toString())->language());
?>
--EXPECT--
NULL
string(5) "uk-UA"
string(5) "uk-UA"
string(5) "en-US"
bool(true)
bool(true)
NULL
NULL
Pdfium\Document::setLanguage(): Argument #1 ($language) must be valid UTF-8
Pdfium\Document::setLanguage(): Argument #1 ($language) must not contain any null bytes
Pdfium\Document::setLanguage(): Argument #1 ($language) must not contain the escape character (U+001B)
Pdfium\Document::setLanguage(): Argument #1 ($language) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::setLanguage(): Argument #1 ($language) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::setLanguage(): Argument #1 ($language) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
Pdfium\Document::setLanguage(): Argument #1 ($language) must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in PDFDocEncoding, as a PDF reader would read that start as a byte order mark
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
string(2) "pl"
bool(true)
NULL
bool(false)
NULL
string(2) "en"
