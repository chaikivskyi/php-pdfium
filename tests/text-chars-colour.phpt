--TEST--
TextPage::chars() reports each character's fill and stroke colours and its text object's matrix
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$content = 'BT /F1 12 Tf 72 700 Td (k) Tj ET '
    . 'BT /F1 12 Tf 1 0 0 rg 0 0 1 RG 2 Tr 72 600 Td (rb) Tj ET '
    . 'q /Half gs BT /F1 10 Tf 0 1 0 rg 0 1 -1 0 300 300 Tm (h) Tj ET Q '
    . 'BT /F1 12 Tf 1 0 0 0 k 0.5 G 72 500 Td (c) Tj ET';
$page = Pdfium\Document::fromString(page_pdf(612, 792, $content, '/ExtGState << /Half << /ca 0.5 >> >>'))->page(0)->textPage();
$chars = array_values(array_filter($page->chars(), fn($c) => !$c->generated));
foreach ($chars as $c) {
    $f = $c->fillColor;
    $s = $c->strokeColor;
    $m = $c->matrix;
    echo $c->text, " fill=$f->red,$f->green,$f->blue,$f->alpha stroke=$s->red,$s->green,$s->blue,$s->alpha",
        " matrix=$m->a,$m->b,$m->c,$m->d,$m->e,$m->f\n";
}
var_dump($chars[1]->fillColor === $chars[2]->fillColor, $chars[1]->matrix == $chars[2]->matrix);
var_dump($chars[0]->fillColor);
?>
--EXPECTF--
k fill=0,0,0,255 stroke=0,0,0,255 matrix=1,0,0,1,72,700
r fill=255,0,0,255 stroke=0,0,255,255 matrix=1,0,0,1,72,600
b fill=255,0,0,255 stroke=0,0,255,255 matrix=1,0,0,1,72,600
h fill=0,255,0,128 stroke=0,0,255,255 matrix=0,1,-1,0,300,300
c fill=0,174,239,255 stroke=128,128,128,255 matrix=1,0,0,1,72,500
bool(true)
bool(true)
object(Pdfium\Color)#%d (4) {
  ["red"]=>
  int(0)
  ["green"]=>
  int(0)
  ["blue"]=>
  int(0)
  ["alpha"]=>
  int(255)
}
