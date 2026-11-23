--TEST--
Document::nUp() puts the pages on sheets in a new document, left to right then top to bottom, each scaled to fit its cell with its proportions and centred, without links, form fields or other annotations, and checks its arguments; with one column and one row PDFium copies the pages whole, ignoring the sheet size
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(5));
$out = $doc->nUp(842, 595, 2, 1);
var_dump($out->pageCount(), $out->page(0)->width(), $out->page(0)->height());
echo str_replace(["\r", "\n", ' '], '', $out->page(0)->text()), '|', trim($out->page(2)->text()), "\n";
var_dump($doc->nUp(612, 792, 1, 1)->pageCount(), $doc->pageCount());
$cells = array_filter($doc->nUp(1000, 400, 2, 2)->page(0)->textPage()->chars(), fn ($c) => trim($c->text) !== '');
echo implode(', ', array_map(fn ($c) => sprintf('%s %.1f %.1f', $c->text, $c->x, $c->y), $cells)), "\n";
$one = $doc->nUp(100, 100, 1, 1)->page(0);
var_dump($one->width(), $one->height());
unset($doc);
var_dump(Pdfium\Document::fromString($out->toString())->pageCount());

$doc = Pdfium\Document::fromString(letters_pdf(5));
foreach ([[0, 100, 1, 1], [100, -1, 1, 1], [100, 100, 0, 1], [100, 100, 1, -1]] as $args) {
    try { $doc->nUp(...$args); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump($doc->nUp(1e300, 100, 2, 1)->page(0)->width());
try { Pdfium\Document::create()->nUp(100, 100, 2, 2); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }

$enc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user');
try { $enc->nUp(100, 100, 1, 1); } catch (Pdfium\UnsupportedException $e) { echo $e->getMessage(), "\n"; }
var_dump($enc->nUp(100, 100, 1, 1, true) instanceof Pdfium\Document);

try {
    var_dump($doc->nUp(100, 100, PHP_INT_MAX, PHP_INT_MAX) instanceof Pdfium\Document);
} catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }

$linked = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [100 700 200 720] /A << /S /URI /URI (https://example.com/) >> >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
));
$form = Pdfium\Document::fromFile(__DIR__ . '/fixtures/form.pdf');
$square = Pdfium\Document::fromString(annot_pdf());
$drawn = fn (Pdfium\Document $d) => $d->page(0)->render(dpi: 72)->toPng() !== $d->page(0)->render(dpi: 72, annotations: false)->toPng();
foreach ([[2, 1], [1, 1]] as [$columns, $rows]) {
    echo "$columns x $rows: links ", count($linked->nUp(1224, 792, $columns, $rows)->page(0)->links()),
        ', form fields drawn ', json_encode($drawn($form->nUp(1224, 792, $columns, $rows))),
        ', square drawn ', json_encode($drawn($square->nUp(200, 100, $columns, $rows))), "\n";
}
?>
--EXPECT--
int(3)
float(842)
float(595)
AB|E
int(5)
int(5)
A 190.9 376.8, B 690.9 376.8, C 190.9 176.8, D 690.9 176.8
float(612)
float(792)
int(3)
Pdfium\Document::nUp(): Argument #1 ($width) must be finite and greater than 0
Pdfium\Document::nUp(): Argument #2 ($height) must be finite and greater than 0
Pdfium\Document::nUp(): Argument #3 ($columns) must be at least 1
Pdfium\Document::nUp(): Argument #4 ($rows) must be at least 1
float(3.4028235E+38)
Cannot put the pages on sheets
The document is encrypted and PDFium cannot encrypt the result; pass removeSecurity: true to put its pages on sheets unencrypted
bool(true)
Cannot put the pages on sheets
2 x 1: links 0, form fields drawn false, square drawn false
1 x 1: links 1, form fields drawn true, square drawn true
