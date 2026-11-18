--TEST--
signatureCount() and signatures() return every top-level signature field with its contents, byte range, sub-filter, reason, time and DocMDP level
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(sig_pdf('6 0 R 8 0 R 10 0 R 11 0 R 12 0 R 13 0 R', [
    sig_field('7 0 R'),
    "<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached /ByteRange [0 100 200 300] /Contents <3082AB000000> "
        . "/Reason (I approve) /M (D:20261118120000+02'00') /Name (Hidden) /Location (Kyiv) /Reference [<< /Type /SigRef "
        . "/TransformMethod /DocMDP /TransformParams << /Type /TransformParams /P 2 /V /1.2 >> >>] >>",
    sig_field('9 0 R'),
    '<< /Type /Sig /SubFilter /ETSI.CAdES.detached /ByteRange [0 10 -5 7 99] /Contents <01> /Reason <FEFF04170433043E0434043D0430> >>',
    '<< /FT /Sig /T (Unsigned) >>',
    '<< /FT /Tx /T (Text) /V (hello) >>',
    '<< /T (Parent) /Kids [<< /FT /Sig /T (Nested) /V 7 0 R >>] >>',
    sig_field('<< /Type /Sig >>'),
]));
var_dump($doc->signatureCount());
$all = $doc->signatures();
var_dump(count($all), array_is_list($all));
[$a, $b, $unsigned, $c] = $all;
var_dump(bin2hex($a->contents), $a->byteRange === [[0, 100], [200, 300]], $a->subFilter, $a->reason, $a->time, $a->docMdpPermission);
var_dump($b->contents === "\x01", $b->byteRange === [[0, 10], [-5, 7]], $b->subFilter, $b->reason, $b->time, $b->docMdpPermission);
var_dump($unsigned, $c == $unsigned);
var_dump(unserialize(serialize($a)) == $a, clone $a == $a);
try { $a->reason = 'x'; } catch (Error $e) { echo $e->getMessage(), "\n"; }
foreach (['tiny.pdf', 'form.pdf'] as $f) {
    $d = Pdfium\Document::fromFile(__DIR__ . "/fixtures/$f");
    var_dump($d->signatureCount(), $d->signatures());
}
?>
--EXPECTF--
int(4)
int(4)
bool(true)
string(12) "3082ab000000"
bool(true)
string(19) "adbe.pkcs7.detached"
string(9) "I approve"
string(23) "D:20261118120000+02'00'"
int(2)
bool(true)
bool(true)
string(19) "ETSI.CAdES.detached"
string(12) "Згодна"
NULL
NULL
object(Pdfium\Signature)#%d (6) {
  ["contents"]=>
  NULL
  ["byteRange"]=>
  array(0) {
  }
  ["subFilter"]=>
  NULL
  ["reason"]=>
  NULL
  ["time"]=>
  NULL
  ["docMdpPermission"]=>
  NULL
}
bool(true)
bool(true)
bool(true)
Cannot modify readonly property Pdfium\Signature::$reason
int(0)
array(0) {
}
int(0)
array(0) {
}
