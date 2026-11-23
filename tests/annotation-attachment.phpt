--TEST--
Annotation::attachment() and setAttachment() read and replace the file of a FileAttachment annotation; its Attachment keeps the annotation alive, has no index, is separate from the document's list even when that names the same file, reads the annotation's current file after setAttachment() or setStringValue('FS') on it, is not affected by a change to another annotation's file, and throws once the annotation is removed or has no file; extractPages() and merge() keep the annotations and the file
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$rect = new Pdfium\Rect(100, 650, 120, 670);
$a = $page->addAnnotation('FileAttachment', $rect);
var_dump($a->attachment());
$f = $a->setAttachment('note.txt', 'hello', 'a note');
var_dump(get_class($f), $f->name(), $f->contents(), $f->metadata()->description);
try { $f->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($doc->attachmentCount(), $doc->attachments());
$docFile = $doc->addAttachment('d.txt', 'd');
var_dump($f->contents(), $a->attachment()->name());
$g = $a->setAttachment('other.txt', 'bye');
var_dump($f->name(), $f->contents(), $f->metadata()->description);
try { $f->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($docFile->contents(), $g->contents(), $g->metadata()->description, $a->attachment()->name());
var_dump($doc->attachmentCount(), $doc->attachment(0)->name());
$doc->deleteAttachment(0);
var_dump($g->name());

$re = Pdfium\Document::fromString($doc->toString())->page(0)->annotation(0)->attachment();
var_dump($re->name(), $re->contents());

$b = $page->addAnnotation('FileAttachment', $rect);
$h = $b->setAttachment('b.txt', 'b');
$a->setAttachment('third.txt', '3');
var_dump($h->name(), $h->contents());
var_dump($b->attachment()->contents());
$t = $a->attachment();
var_dump($t->name());

$a->remove();
try { $t->name(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
try { $a->attachment(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
try { $a->setAttachment('x', 'y'); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$c = $page->addAnnotation('FileAttachment', $rect);
$u = $c->setAttachment('c.txt', 'c');
$c->remove();
$page->addAnnotation('FileAttachment', $rect)->setAttachment('e.txt', 'e');
try { $u->name(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
try { $c->attachment(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }

try { $b->setAttachment('', 'x'); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
var_dump($b->attachment()->name());

function lone(): Pdfium\Attachment
{
    $doc = Pdfium\Document::fromString(nav_pdf());
    $page = $doc->page(0);
    $a = $page->addAnnotation('FileAttachment', new Pdfium\Rect(0, 0, 10, 10));
    return $a->setAttachment('lone.txt', 'alone');
}
$lone = lone();
gc_collect_cycles();
var_dump($lone->name(), $lone->contents());

$fixture = Pdfium\Document::fromString(nav_pdf(
    extra: [
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 0 10 10] /FS 7 0 R >>',
        file_spec('fix.txt', 8, '/Desc (fixture file)'),
        stream_obj('fixture'),
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 20 10 30] /FS ' . file_spec('direct.txt', 8) . ' >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 40 10 50] >>',
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 60 10 70] >>',
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 80 10 90] /FS (plain.txt) >>',
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 100 10 110] /FS 42 >>',
    ],
    pageExtra: ['/Annots [6 0 R 9 0 R 10 0 R 11 0 R 12 0 R 13 0 R]', '', ''],
));
[$byRef, $direct, $text, $none, $plain, $number] = $fixture->page(0)->annotations();
$r = $byRef->attachment();
var_dump($r->name(), $r->contents(), $r->metadata()->description);
$d = $direct->attachment();
var_dump($d->name(), $d->contents());
var_dump($text->attachment(), $none->attachment(), $fixture->attachmentCount());
try { $text->setAttachment('x.txt', 'x'); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($text->hasKey('FS'), $r->name());
$d = $direct->attachment();
$direct->setAttachment('new.txt', 'new');
var_dump($d->name(), $d->contents());
var_dump($direct->attachment()->name(), $direct->attachment()->contents(), $byRef->attachment()->contents());
$n = $none->setAttachment('none.txt', '');
var_dump($n->name(), $n->contents());
foreach ([$plain, $number] as $x) {
    $f = $x->attachment();
    var_dump($f->name(), $f->contents(), $f->metadata()->description);
}
$s = $none->attachment();
$none->setStringValue('FS', 'string.txt');
var_dump($s->name(), $s->contents());
var_dump($none->attachment()->name(), $none->attachment()->contents());
$x = $byRef->attachment();
$byRef->setStringValue('Subtype', 'X');
try { $x->name(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($byRef->type(), $byRef->valueType('Subtype'), $byRef->attachment());
$replaced = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /FileAttachment /Rect [0 0 10 10] /FS 7 0 R >>', file_spec('old.txt', 8), stream_obj('old file', '/Mark /OLD-FILE-BYTES ')],
    pageExtra: ['/Annots [6 0 R]', '', ''],
));
$annotation = $replaced->page(0)->annotation(0);
$annotation->setAttachment('first.txt', '1');
$annotation->setAttachment('second.txt', '2');
$bytes = $replaced->toString();
foreach (['old.txt', 'OLD-FILE-BYTES', 'first.txt', 'second.txt'] as $marker) {
    echo $marker, ': ', str_contains($bytes, $marker) ? 'kept' : 'gone', "\n";
}
$shared = Pdfium\Document::fromString(nav_pdf(
    '/Names << /EmbeddedFiles << /Names [(shared.txt) 6 0 R] >> >>',
    [file_spec('shared.txt', 7), stream_obj('shared'), '<< /Type /Annot /Subtype /FileAttachment /Rect [0 0 10 10] /FS 6 0 R >>'],
    ['/Annots [8 0 R]', '', ''],
));
$listed = $shared->attachment(0);
$sharedAnnotation = $shared->page(0)->annotation(0);
$own = $sharedAnnotation->attachment();
var_dump($shared->attachmentCount(), $listed->name(), $own->name(), $own->contents(), $listed->index());
try { $own->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$sharedAnnotation->setAttachment('new.txt', 'new');
var_dump($listed->name(), $listed->contents(), $sharedAnnotation->attachment()->name(), $shared->attachmentCount());
$source = Pdfium\Document::fromString(nav_pdf());
$sourcePage = $source->page(0);
$sourcePage->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30))->setStringValue('Contents', 'added');
$sourcePage->addAnnotation('FileAttachment', new Pdfium\Rect(40, 10, 60, 30))->setAttachment('copied.txt', 'copied');
foreach ([$source->extractPages([0]), Pdfium\Document::merge([$source])] as $copy) {
    [$note, $clip] = $copy->page(0)->annotations();
    var_dump($copy->attachmentCount(), $note->stringValue('Contents'), $clip->attachment()->name(), $clip->attachment()->contents());
}
$held = $sourcePage->annotation(1)->attachment();
$sourcePage->annotation(0)->setStringValue('FS', 'text.txt');
var_dump($held->name(), $held->contents());
?>
--EXPECT--
NULL
string(17) "Pdfium\Attachment"
string(8) "note.txt"
string(5) "hello"
string(6) "a note"
The file belongs to an annotation and has no index
int(0)
array(0) {
}
string(5) "hello"
string(8) "note.txt"
string(9) "other.txt"
string(3) "bye"
NULL
The file belongs to an annotation and has no index
string(1) "d"
string(3) "bye"
NULL
string(9) "other.txt"
int(1)
string(5) "d.txt"
string(9) "other.txt"
string(9) "other.txt"
string(3) "bye"
string(5) "b.txt"
string(1) "b"
string(1) "b"
string(9) "third.txt"
The annotation was removed from its page
The annotation was removed from its page
The annotation was removed from its page
The annotation was removed from its page
The annotation was removed from its page
Pdfium\Annotation::setAttachment(): Argument #1 ($name) must not be empty
string(5) "b.txt"
string(8) "lone.txt"
string(5) "alone"
string(7) "fix.txt"
string(7) "fixture"
string(12) "fixture file"
string(10) "direct.txt"
string(7) "fixture"
NULL
NULL
int(0)
Cannot attach a file to a Text annotation
bool(false)
string(7) "fix.txt"
string(7) "new.txt"
string(3) "new"
string(7) "new.txt"
string(3) "new"
string(7) "fixture"
string(8) "none.txt"
string(0) ""
string(9) "plain.txt"
NULL
NULL
string(0) ""
NULL
NULL
string(10) "string.txt"
NULL
string(10) "string.txt"
NULL
Cannot read the annotation's file
string(7) "Unknown"
string(6) "String"
NULL
old.txt: gone
OLD-FILE-BYTES: gone
first.txt: kept
second.txt: kept
int(1)
string(10) "shared.txt"
string(10) "shared.txt"
string(6) "shared"
int(0)
The file belongs to an annotation and has no index
string(10) "shared.txt"
string(6) "shared"
string(7) "new.txt"
int(1)
int(0)
string(5) "added"
string(10) "copied.txt"
string(6) "copied"
int(0)
string(5) "added"
string(10) "copied.txt"
string(6) "copied"
string(10) "copied.txt"
string(6) "copied"
