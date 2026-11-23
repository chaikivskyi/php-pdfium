--TEST--
deleteAttachment() removes an entry in place; toString() writes the shorter list, leaves out a deleted file that came with the loaded document unless an annotation still points to it, and keeps, unused, one added since; several go by picking indexes first and deleting from the highest down
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

$both = Pdfium\Document::fromString(attach_pdf('(loaded.txt) 6 0 R', [file_spec('loaded.txt', 7), stream_obj('loaded', '/Mark /LOADED-FILE ')]));
$both->addAttachment('added.txt', 'added');
echo $names($both), "\n";
$both->deleteAttachment(1);
$both->deleteAttachment(0);
$saved = $both->toString();
foreach (['loaded.txt', 'LOADED-FILE', 'added.txt'] as $marker) {
    echo $marker, ': ', str_contains($saved, $marker) ? 'kept' : 'gone', "\n";
}
var_dump(substr_count($saved, 'endstream'), Pdfium\Document::fromString($saved)->attachmentCount());
$shared = Pdfium\Document::fromString(nav_pdf(
    '/Names << /EmbeddedFiles << /Names [(shared.txt) 6 0 R] >> >>',
    [file_spec('shared.txt', 7), stream_obj('shared', '/Mark /SHARED-FILE '), '<< /Type /Annot /Subtype /FileAttachment /Rect [0 0 10 10] /FS 6 0 R >>'],
    ['/Annots [8 0 R]', '', ''],
));
$shared->deleteAttachment(0);
$saved = $shared->toString();
echo 'SHARED-FILE: ', str_contains($saved, 'SHARED-FILE') ? 'kept' : 'gone', "\n";
var_dump(Pdfium\Document::fromString($saved)->page(0)->annotation(0)->attachment()->contents());
?>
--EXPECT--
a=a,c=c
a=a,c=c
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be between 0 and 1
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be between 0 and 1
int(0)
Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
b=b,d=d
added.txt=added,loaded.txt=loaded
loaded.txt: gone
LOADED-FILE: gone
added.txt: kept
int(1)
int(0)
SHARED-FILE: kept
string(6) "shared"
