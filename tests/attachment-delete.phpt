--TEST--
deleteAttachment() removes an entry in place; toString() writes the shorter list; several go by picking indexes first and deleting from the highest down
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf());
foreach (['a', 'b', 'c'] as $n) {
    $doc->addAttachment($n, $n);
}
$doc->deleteAttachment(1);
$names = fn ($d) => implode(',', array_map(fn ($a) => $a->name() . '=' . $a->contents(), $d->attachments()));
echo $names($doc), "\n";
echo $names(Pdfium\Document::fromString($doc->toString())), "\n";
foreach ([2, -1] as $i) {
    try { $doc->deleteAttachment($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$doc->deleteAttachment(1);
$doc->deleteAttachment(0);
var_dump($doc->attachmentCount());
try { $doc->deleteAttachment(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
foreach (['a.tmp', 'b', 'c.tmp', 'd'] as $n) {
    $doc->addAttachment($n, $n);
}
$doomed = [];
foreach ($doc->attachments() as $index => $a) {
    if (str_ends_with($a->name(), '.tmp')) {
        $doomed[] = $index;
    }
}
foreach (array_reverse($doomed) as $index) {
    $doc->deleteAttachment($index);
}
echo $names($doc), "\n";
?>
--EXPECT--
a=a,c=c
a=a,c=c
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be between 0 and 1
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be between 0 and 1
int(0)
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
b=b,d=d
