--TEST--
isTagged() reports the catalog's /MarkInfo /Marked and language() the catalog's /Lang as UTF-8, null when missing or empty; wrong types are not errors, and a stream is read as its text
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$open = fn (string $catalog) => Pdfium\Document::fromString(nav_pdf($catalog));
foreach ([
    'plain' => '',
    'tagged' => '/MarkInfo << /Marked true >> /Lang (en-US)',
    'marked false' => '/MarkInfo << /Marked false >> /Lang (de)',
    'utf16' => '/Lang <FEFF0443043A>',
    'empty' => '/Lang ()',
    'markinfo number' => '/MarkInfo 5',
    'marked int' => '/MarkInfo << /Marked 1 >>',
    'marked name' => '/MarkInfo << /Marked /Yes >>',
    'lang name' => '/Lang /en',
    'lang number' => '/Lang 7',
] as $label => $catalog) {
    $doc = $open($catalog);
    echo $label, ': ';
    var_dump($doc->isTagged(), $doc->language());
}
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user')->language());
echo 'lang stream: ';
var_dump(Pdfium\Document::fromString(nav_pdf('/Lang 6 0 R', [stream_obj('en-GB')]))->language());
?>
--EXPECT--
plain: bool(false)
NULL
tagged: bool(true)
string(5) "en-US"
marked false: bool(false)
string(2) "de"
utf16: bool(false)
string(4) "ук"
empty: bool(false)
NULL
markinfo number: bool(false)
NULL
marked int: bool(true)
NULL
marked name: bool(false)
NULL
lang name: bool(false)
string(2) "en"
lang number: bool(false)
NULL
NULL
lang stream: string(5) "en-GB"
