--TEST--
An Annotation keeps its page and document alive, cannot be cloned or serialized, and can outlive the script
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$bytes = annots_pdf('<< /Type /Annot /Subtype /Square /Rect [10 20 30 40] >>');
$doc = Pdfium\Document::fromString($bytes);
$a = $doc->page(0)->annotation(0);
$len = strlen($bytes);
unset($doc, $bytes);
gc_collect_cycles();
$junk = str_repeat("\xFF", $len);
var_dump($a->type(), $a->rect()->top, $a->index());
attempt(fn() => clone $a);
attempt(fn() => serialize($a));
$GLOBALS['kept'] = Pdfium\Document::fromString(annots_pdf('<< /Type /Annot /Subtype /Text /Rect [0 0 1 1] >>'))->page(0)->annotation(0);
echo "end\n";
?>
--EXPECT--
string(6) "Square"
float(40)
int(0)
Error: Trying to clone an uncloneable object of class Pdfium\Annotation
Exception: Serialization of 'Pdfium\Annotation' is not allowed
end
