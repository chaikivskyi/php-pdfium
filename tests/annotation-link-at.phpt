--TEST--
Once Page::linkAt() has looked at a page of a document, it keeps finding that page's link annotations as they were: a removed link is still found and an added one is not, on every Page for it, while links() and Annotation::link() follow the change; linkAt() first called after the change, on another page or on a reopened document sees the current links
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$pdf = annots_pdf('<< /Type /Annot /Subtype /Link /Rect [10 10 50 50] /A << /S /URI /URI (http://old) >> >>');
$uri = fn(?Pdfium\Link $l) => $l?->action?->uri ?? 'null';

echo "-- linkAt() before the change\n";
$doc = Pdfium\Document::fromString($pdf);
$page = $doc->page(0);
echo $uri($page->linkAt(20, 20)), "\n";
$page->removeAnnotation(0);
$added = $page->addAnnotation('Link', new Pdfium\Rect(100, 100, 150, 150));
$added->setUri('http://new');
echo count($page->links()), ' ', $uri($page->links()[0]), ' ', $uri($added->link()), "\n";
echo $uri($page->linkAt(20, 20)), ' ', $uri($page->linkAt(120, 120)), "\n";
$fresh = $doc->page(0);
echo $uri($fresh->linkAt(20, 20)), ' ', $uri($fresh->linkAt(120, 120)), "\n";
$reopened = Pdfium\Document::fromString($doc->toString())->page(0);
echo $uri($reopened->linkAt(20, 20)), ' ', $uri($reopened->linkAt(120, 120)), "\n";

echo "-- linkAt() first called after the change\n";
$doc = Pdfium\Document::fromString($pdf);
$doc->page(1)->linkAt(20, 20);
$page = $doc->page(0);
$page->removeAnnotation(0);
$page->addAnnotation('Link', new Pdfium\Rect(100, 100, 150, 150))->setUri('http://new');
echo $uri($page->linkAt(20, 20)), ' ', $uri($page->linkAt(120, 120)), "\n";
$page->addAnnotation('Link', new Pdfium\Rect(200, 200, 250, 250))->setUri('http://later');
echo $uri($page->linkAt(220, 220)), ' ', count($page->links()), "\n";

echo "-- a link linkAt() found, changed\n";
$doc = Pdfium\Document::fromString($pdf);
$page = $doc->page(0);
echo $uri($page->linkAt(20, 20)), "\n";
$link = $page->annotation(0);
$link->setUri('http://changed');
$link->setRect(new Pdfium\Rect(300, 300, 350, 350));
echo $uri($page->linkAt(20, 20)), ' ', $uri($page->linkAt(320, 320)), "\n";
$link->remove();
echo $uri($page->linkAt(320, 320)), ' ', count($page->links()), "\n";
unset($link, $page, $doc);
?>
--EXPECT--
-- linkAt() before the change
http://old
1 http://new http://new
http://old null
http://old null
null http://new
-- linkAt() first called after the change
null http://new
null 2
-- a link linkAt() found, changed
http://old
null http://changed
http://changed 0
