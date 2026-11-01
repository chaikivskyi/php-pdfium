--TEST--
Pages and iterators alive at shutdown do not crash
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$it = $doc->pages();
$it->rewind();
$it->current();
$GLOBALS['page'] = $doc->page(0);
$o = new stdClass;
$o->self = $o;
$o->page = $doc->page(1);
unset($doc);
echo "end\n";
exit(0);
?>
--EXPECT--
end
