--TEST--
A TextPage freed after its Page at shutdown, or the other way round, closes PDFium's objects in a safe order
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$early = new stdClass();
$doc = Pdfium\Document::fromString(lines_pdf('shutdown'));
$page = $doc->page(0);
unset($early);
$tp = $page->textPage();
$tp2 = $doc->page(0)->textPage();
unset($doc);
echo $tp->text(), ' ', $tp2->text(), "\n";
$GLOBALS['cycle'] = new stdClass();
$GLOBALS['cycle']->self = $GLOBALS['cycle'];
$GLOBALS['cycle']->tp = $tp;
echo "done\n";
?>
--EXPECT--
shutdown shutdown
done
