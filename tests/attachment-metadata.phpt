--TEST--
metadata() reads the description, MIME subtype, AFRelationship, dates and a hex checksum; missing or unreadable entries are null
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(attach_pdf('(bare) 8 0 R (ext) 10 0 R (full) 6 0 R (literal) 11 0 R (short) 13 0 R', [
    file_spec('r.csv', 7, '/Desc (Monthly report) /AFRelationship /Data'),
    stream_obj('x,y', '/Type /EmbeddedFile /Subtype /text#2Fcsv /Params << /CreationDate (D:20260101120000Z) /ModDate (D:20260102120000Z) /CheckSum <0123456789ABCDEF0123456789ABCDEF> /Size 3 >> '),
    file_spec('n.txt', 9),
    stream_obj('n'),
    '<< /Type /Filespec /F (http://example.com/x) /Desc (Remote) >>',
    file_spec('l.txt', 12),
    stream_obj('l', '/Params << /CheckSum (abcdefghijklmnop) >> '),
    file_spec('s.txt', 14),
    stream_obj('s', '/Params << /CheckSum <ABCD> >> '),
]));
[$bare, $ext, $full, $literal, $short] = $doc->attachments();
var_dump($full->metadata());
var_dump($bare->metadata());
var_dump(array_filter((array) $ext->metadata()));
var_dump($literal->metadata()->checksum, $short->metadata()->checksum);
$m = $full->metadata();
var_dump(unserialize(serialize($m)) == $m, clone $m == $m);
?>
--EXPECTF--
object(Pdfium\AttachmentMetadata)#%d (6) {
  ["description"]=>
  string(14) "Monthly report"
  ["subtype"]=>
  string(8) "text/csv"
  ["afRelationship"]=>
  string(4) "Data"
  ["creationDate"]=>
  string(17) "D:20260101120000Z"
  ["modDate"]=>
  string(17) "D:20260102120000Z"
  ["checksum"]=>
  string(32) "0123456789abcdef0123456789abcdef"
}
object(Pdfium\AttachmentMetadata)#%d (6) {
  ["description"]=>
  NULL
  ["subtype"]=>
  NULL
  ["afRelationship"]=>
  NULL
  ["creationDate"]=>
  NULL
  ["modDate"]=>
  NULL
  ["checksum"]=>
  NULL
}
array(1) {
  ["description"]=>
  string(6) "Remote"
}
NULL
NULL
bool(true)
bool(true)
