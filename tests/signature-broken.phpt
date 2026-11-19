--TEST--
Malformed signature dictionaries and forms neither crash nor list anything that is not there
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function show(string $label, string $pdf): void
{
    $doc = Pdfium\Document::fromString($pdf);
    $all = $doc->signatures();
    echo $label, ': ', $doc->signatureCount(), count($all) === $doc->signatureCount() ? '' : ' (listed ' . count($all) . ')';
    foreach ($all as $s) {
        echo ' {', json_encode([
            $s->contents === null ? null : bin2hex($s->contents), $s->byteRange, $s->subFilter, $s->reason, $s->time, $s->docMdpPermission,
        ]), '}';
    }
    echo "\n";
}
$mdp = fn (string $p) => sig_pdf(sig_field("<< /Reference [<< /TransformMethod /DocMDP /TransformParams << $p >> >>] >>"));

show('no fields', nav_pdf('/AcroForm << /SigFlags 3 >>'));
show('fields a number', nav_pdf('/AcroForm << /Fields 7 >>'));
show('form a number', nav_pdf('/AcroForm 7'));
show('not dictionaries', sig_pdf('42 (text) null 99 0 R'));
show('type by string', sig_pdf('<< /FT (Sig) /V << /Contents <01> >> >>'));
show('value a string', sig_pdf(sig_field('(not a dictionary)')));
show('wrong types', sig_pdf(sig_field('<< /Contents 5 /ByteRange (x) /SubFilter 3 /Reason /Name /M 7 /Reference (x) >>')));
show('all empty', sig_pdf(sig_field('<< /Contents <> /ByteRange [] /Reason () /M () /Reference [] >>')));
show('sub-filter a string', sig_pdf(sig_field('<< /SubFilter (adbe.pkcs7.detached) >>')));
show('odd range', sig_pdf(sig_field('<< /ByteRange [1.9 (a) /b 4] /Contents <ABC> >>')));
show('one number', sig_pdf(sig_field('<< /ByteRange [5] >>')));
show('huge numbers', sig_pdf(sig_field('<< /ByteRange [2147483647 2147483648 -2147483648 99999999999] >>')));
show('nul bytes', sig_pdf(sig_field('<< /Contents <00410000> /M <44003A> /Reason <410042> >>')));
foreach (['/P 0', '/P 4', '/P -1', '/P 2.5', '/P (2)', '/P 1', '/P 3', ''] as $p) {
    show("docmdp [$p]", $mdp($p));
}
show('ur3', sig_pdf(sig_field('<< /Reference [<< /TransformMethod /UR3 /TransformParams << /P 2 >> >>] >>')));
show('second reference', sig_pdf(sig_field('<< /Reference [7 << /TransformMethod /UR3 >> << /TransformMethod /DocMDP /TransformParams << /P 3 >> >>] >>')));
show('listed twice', sig_pdf('6 0 R 6 0 R', [sig_field('<< /Contents <01> >>')]));
show('value shared', sig_pdf(sig_field('6 0 R') . ' ' . sig_field('6 0 R'), ['<< /Contents <02> >>']));
show('value loops', sig_pdf('6 0 R', ['<< /FT /Sig /V 6 0 R >>']));
show('signed below', sig_pdf('<< /FT /Sig /T (Top) /Kids [<< /T (Kid) /V << /Contents <01> >> >>] >>'));
?>
--EXPECT--
no fields: 0
fields a number: 0
form a number: 0
not dictionaries: 0
type by string: 0
value a string: 1 {[null,[],null,null,null,null]}
wrong types: 1 {["35",[],null,null,null,null]}
all empty: 1 {[null,[],null,null,null,null]}
sub-filter a string: 1 {[null,[],null,null,null,null]}
odd range: 1 {["abc0",[[1,0],[0,4]],null,null,null,null]}
one number: 1 {[null,[],null,null,null,null]}
huge numbers: 1 {[null,[[2147483647,-2147483648],[-2147483648,0]],null,null,null,null]}
nul bytes: 1 {["00410000",[],null,"A","D\u0000:",null]}
docmdp [/P 0]: 1 {[null,[],null,null,null,null]}
docmdp [/P 4]: 1 {[null,[],null,null,null,null]}
docmdp [/P -1]: 1 {[null,[],null,null,null,null]}
docmdp [/P 2.5]: 1 {[null,[],null,null,null,2]}
docmdp [/P (2)]: 1 {[null,[],null,null,null,null]}
docmdp [/P 1]: 1 {[null,[],null,null,null,1]}
docmdp [/P 3]: 1 {[null,[],null,null,null,3]}
docmdp []: 1 {[null,[],null,null,null,2]}
ur3: 1 {[null,[],null,null,null,null]}
second reference: 1 {[null,[],null,null,null,3]}
listed twice: 2 {["01",[],null,null,null,null]} {["01",[],null,null,null,null]}
value shared: 2 {["02",[],null,null,null,null]} {["02",[],null,null,null,null]}
value loops: 1 {[null,[],null,null,null,null]}
signed below: 1 {[null,[],null,null,null,null]}
