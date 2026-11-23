--TEST--
Page::annotations() and Annotation::quadPoints(), inkStrokes(), vertices(), appearance(), addInkStroke(), setStringValue() and setAppearance() throw MemoryException, not a fatal error, when the result would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$points = str_repeat('1 2 ', 200000);
$quads = str_repeat('1 2 ', 400000);
$annots = Pdfium\Document::fromString(nav_pdf(
    extra: [
        "<< /Type /Annot /Subtype /Ink /Rect [0 0 10 10] /InkList [[$points]] >>",
        "<< /Type /Annot /Subtype /Polygon /Rect [0 0 10 10] /Vertices [$points] >>",
        '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /AP << /N 10 0 R >> >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] >>',
        form_xobject(str_repeat('0 g ', 1 << 20), 10, 10),
        "<< /Type /Annot /Subtype /Highlight /Rect [0 0 10 10] /QuadPoints [$quads] >>",
    ],
    pageExtra: ['/Annots [6 0 R 7 0 R 8 0 R 9 0 R 11 0 R]', '', ''],
))->page(0)->annotations();
$many = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] >>'],
    pageExtra: ['/Annots [' . str_repeat('6 0 R ', 200000) . ']', '', ''],
))->page(0);
unset($points, $quads);
$stroke = array_fill(0, 3 << 20, new Pdfium\Point(1, 2));
$long = str_repeat('x', 12 << 20);
ini_set('memory_limit', (string) (memory_get_usage(true) + (16 << 20)));
try { $many->annotations(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[4]->quadPoints(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[0]->inkStrokes(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[1]->vertices(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[2]->appearance(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[0]->addInkStroke($stroke); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[3]->setStringValue('Contents', $long); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annots[3]->setAppearance($long); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($annots[3]->stringValue('Contents'), $annots[3]->appearance());
echo $annots[3]->type(), ' ', $many->annotationCount(), "\n";
?>
--EXPECT--
The annotations need more memory than memory_limit leaves
The quad points need more memory than memory_limit leaves
The ink strokes need more memory than memory_limit leaves
The vertices need more memory than memory_limit leaves
The appearance needs more memory than memory_limit leaves
The ink strokes need more memory than memory_limit leaves
The annotation value needs more memory than memory_limit leaves
The appearance needs more memory than memory_limit leaves
NULL
NULL
Text 200000
