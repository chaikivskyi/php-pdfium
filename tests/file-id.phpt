--TEST--
Document::permanentId() and changingId() return the trailer's /ID entries as raw bytes, null without one
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf(trailer: '/ID [<0123456789abcdef> <fedcba9876543210>] '));
var_dump(bin2hex($doc->permanentId()), bin2hex($doc->changingId()));
$none = Pdfium\Document::fromString(nav_pdf());
var_dump($none->permanentId(), $none->changingId());
$empty = Pdfium\Document::fromString(nav_pdf(trailer: '/ID [<> <>] '));
var_dump($empty->permanentId(), $empty->changingId());
?>
--EXPECT--
string(16) "0123456789abcdef"
string(16) "fedcba9876543210"
NULL
NULL
NULL
NULL
