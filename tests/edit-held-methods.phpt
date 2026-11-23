--TEST--
Every Page and TextPage method throws the deleted-page message once the page is deleted, also after a failed edit, and the unknown-position message once a failed edit leaves a held page's position unknown; PageIterator::current() then loads the page at key() again
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function calls(Pdfium\Page $page, Pdfium\TextPage $text): array
{
    $rect = new Pdfium\Rect(0, 0, 612, 792);
    return [
        'Page::index' => fn () => $page->index(),
        'Page::width' => fn () => $page->width(),
        'Page::height' => fn () => $page->height(),
        'Page::rotation' => fn () => $page->rotation(),
        'Page::setRotation' => fn () => $page->setRotation(90),
        'Page::mediaBox' => fn () => $page->mediaBox(),
        'Page::setMediaBox' => fn () => $page->setMediaBox($rect),
        'Page::cropBox' => fn () => $page->cropBox(),
        'Page::setCropBox' => fn () => $page->setCropBox($rect),
        'Page::bleedBox' => fn () => $page->bleedBox(),
        'Page::setBleedBox' => fn () => $page->setBleedBox($rect),
        'Page::trimBox' => fn () => $page->trimBox(),
        'Page::setTrimBox' => fn () => $page->setTrimBox($rect),
        'Page::artBox' => fn () => $page->artBox(),
        'Page::setArtBox' => fn () => $page->setArtBox($rect),
        'Page::text' => fn () => $page->text(),
        'Page::render' => fn () => $page->render(),
        'Page::textPage' => fn () => $page->textPage(),
        'Page::openAction' => fn () => $page->openAction(),
        'Page::closeAction' => fn () => $page->closeAction(),
        'Page::links' => fn () => $page->links(),
        'Page::linkAt' => fn () => $page->linkAt(100, 100),
        'Page::annotationCount' => fn () => $page->annotationCount(),
        'Page::annotations' => fn () => $page->annotations(),
        'Page::annotation' => fn () => $page->annotation(0),
        'Page::addAnnotation' => fn () => $page->addAnnotation('Text', $rect),
        'Page::removeAnnotation' => fn () => $page->removeAnnotation(0),
        'Page::formFields' => fn () => $page->formFields(),
        'Page::formFieldAt' => fn () => $page->formFieldAt(100, 100),
        'Page::flatten' => fn () => $page->flatten(),
        'TextPage::text' => fn () => $text->text(),
        'TextPage::charCount' => fn () => $text->charCount(),
        'TextPage::chars' => fn () => $text->chars(),
        'TextPage::charIndexAt' => fn () => $text->charIndexAt(80, 710),
        'TextPage::textIn' => fn () => $text->textIn($rect),
        'TextPage::textInEach' => fn () => $text->textInEach([$rect]),
        'TextPage::search' => fn () => $text->search('A'),
        'TextPage::webLinks' => fn () => $text->webLinks(),
    ];
}
function refused(string $state, array $calls): void
{
    $messages = [];
    foreach ($calls as $name => $f) {
        try { $f(); $messages[] = "$name does not throw"; } catch (Pdfium\PdfiumException $e) { $messages[] = $e->getMessage(); }
    }
    echo "$state: ", implode(' | ', array_unique($messages)), "\n";
}
$doc = Pdfium\Document::fromString(loop_pdf());
$gone = $doc->page(4);
$deleted = calls($gone, $gone->textPage());
$doc->deletePage(4);
$page = $doc->page(0);
$lost = calls($page, $page->textPage());
$methods = [];
foreach ([Pdfium\Page::class, Pdfium\TextPage::class] as $class) {
    foreach (get_class_methods($class) as $method) {
        $methods[] = substr(strrchr($class, '\\'), 1) . "::$method";
    }
}
echo 'not called: ', json_encode(array_values(array_diff($methods, array_keys($deleted)))), "\n";
refused('deleted', $deleted);
$it = $doc->pages();
$it->rewind();
$held = $it->current();
try { $doc->movePages([0], 1); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
refused('position unknown', $lost);
refused('held by the iterator', ['Page::index' => fn () => $held->index()]);
refused('deleted, after the failed edit', $deleted);
$current = $it->current();
var_dump($current !== $held, $current->index());
?>
--EXPECT--
not called: []
deleted: The page was deleted from its document
Cannot move the pages: the document's page order is now undefined; load the document again
position unknown: The page's position is unknown after a failed edit of its document; load the document again
held by the iterator: The page's position is unknown after a failed edit of its document; load the document again
deleted, after the failed edit: The page was deleted from its document
bool(true)
int(0)
