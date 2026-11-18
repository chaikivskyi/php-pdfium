--TEST--
An Attachment fetched before an add or delete throws instead of reading another or a freed entry, even for an inline filespec; a failed add or another document's change leaves it usable
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(attach_pdf(
    '(d.txt) << /Type /Filespec /F (d.txt) /EF << /F 6 0 R >> >> (e.txt) 7 0 R',
    [stream_obj('direct'), file_spec('e.txt', 8), stream_obj('e')],
));
$old = $doc->attachment(0);
$keep = $doc->attachment(1);
$doc->deleteAttachment(0);
foreach (['name', 'contents', 'metadata'] as $m) {
    try { $old->$m(); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', $e->getMessage(), "\n"; }
}
try { $old->index(); } catch (Pdfium\PdfiumException $e) { echo 'index: ', $e->getMessage(), "\n"; }
try { $keep->name(); } catch (Pdfium\PdfiumException $e) { echo "keep: ", $e->getMessage(), "\n"; }

$before = $doc->attachment(0);
$added = $doc->addAttachment('a.txt', 'a');
try { $before->contents(); } catch (Pdfium\PdfiumException $e) { echo "before add: stale\n"; }
var_dump($added->contents(), $doc->attachment(1)->contents());

$fresh = $doc->attachment(0);
try { $doc->addAttachment('a.txt', 'again'); } catch (Pdfium\PdfiumException $e) { echo "duplicate refused\n"; }
var_dump($fresh->contents());

$other = Pdfium\Document::fromString(nav_pdf());
$other->addAttachment('x', 'x');
var_dump($fresh->name());
?>
--EXPECT--
name: The document's attachments changed since this Attachment was fetched; fetch it again
contents: The document's attachments changed since this Attachment was fetched; fetch it again
metadata: The document's attachments changed since this Attachment was fetched; fetch it again
index: The document's attachments changed since this Attachment was fetched; fetch it again
keep: The document's attachments changed since this Attachment was fetched; fetch it again
before add: stale
string(1) "a"
string(1) "e"
duplicate refused
string(1) "a"
string(5) "a.txt"
