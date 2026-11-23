--TEST--
Document::create() makes a document with no pages that addPage() fills
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::create();
var_dump($doc->pageCount(), $doc->bookmarks(), $doc->attachmentCount());
$bytes = $doc->toString();
var_dump(Pdfium\Document::fromString($bytes)->pageCount());
$doc->addPage(100, 200);
var_dump(Pdfium\Document::fromString($doc->toString())->pageCount());
?>
--EXPECT--
int(0)
array(0) {
}
int(0)
int(0)
int(1)
