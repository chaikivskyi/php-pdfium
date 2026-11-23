--TEST--
While another thread is inside PDFium, a method that needs it throws BusyException and does nothing, addAttachment(), signatures(), signatureCount(), isTagged(), language(), setLanguage() and every form method included; Bitmap methods, an iterator's already loaded page and releasing objects never do
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
require __DIR__ . '/pdf.inc';
$target = sys_get_temp_dir() . '/pdfium-threads-busy-' . getmypid() . '.pdf';
@unlink($target);

$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$bitmap = $doc->page(0)->render(dpi: 72);
$it = $doc->pages();
$page = $it->current();
$adoc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$held = $adoc->addAttachment('held', 'h');
$sdoc = Pdfium\Document::fromString(sig_pdf(sig_field('<< /Contents <01> >>')));
$adoc->setLanguage('held');
require __DIR__ . '/form.inc';
$formDoc = Pdfium\Document::fromString(standard_form());
$formPage = $formDoc->page(0);
$fields = fields($formPage);
$fieldCount = count($formPage->formFields());
$pool = [];
for ($i = 0; $i < 60; $i++) {
    $pool[] = Pdfium\Document::fromString(standard_form())->page(0);
}
$snapshot = fn () => with_retry(fn () => [
    $fields['name']->value(),
    $fields['agree']->isChecked(),
    array_map(fn ($o) => $o->selected, $fields['country']->options()),
]);
$formOps = [
    'formType' => fn () => $formDoc->formType(),
    'formFields' => fn () => $formPage->formFields(),
    'formFieldAt' => fn () => $formPage->formFieldAt(150, 710),
    'name' => fn () => $fields['name']->name(),
    'value' => fn () => $fields['name']->value(),
    'isChecked' => fn () => $fields['agree']->isChecked(),
    'options' => fn () => $fields['country']->options(),
    'flags' => fn () => $fields['name']->flags(),
    'rect' => fn () => $fields['name']->rect(),
    'setValue' => fn () => $fields['name']->setValue('busy'),
    'setChecked' => fn () => $fields['agree']->setChecked(),
    'select' => fn () => $fields['country']->select(1),
];
foreach (['setValue', 'setChecked', 'select'] as $name) {
    $formOps[$name]();
}
$formBusy = [];
$known = $snapshot();
$flattenBusy = false;
$used = [];
$formUnchanged = $flattenUnchanged = true;

$renderer = renderer_start();

$busyMessage = $saveBusy = $addBusy = $addBusyChangedNothing = $sigBusy = $sigCountBusy = $langBusy = $langBusyChangedNothing = $tagBusy = $readBusy = false;
$noFileAfterSaveBusy = $bitmapNeverBusy = $releaseNeverBusy = $currentNeverBusy = $typeErrorNeverBusy = true;
$end = microtime(true) + 10;
while ((!$busyMessage || !$saveBusy || !$addBusy || !$sigBusy || !$sigCountBusy || !$langBusy || !$tagBusy || !$readBusy || count($formBusy) < count($formOps) || !$flattenBusy) && microtime(true) < $end) {
    try {
        $it->next();
        unset($page);
        $it->rewind();
    } catch (Pdfium\BusyException $e) {
        $releaseNeverBusy = false;
    }
    $page = with_retry(fn () => $it->current());
    try {
        $bitmap->width();
        $bitmap->toPng();
        $bitmap->toJpeg();
    } catch (Pdfium\BusyException $e) {
        $bitmapNeverBusy = false;
    }
    usleep(random_int(0, 5000));
    try {
        $it->current();
    } catch (Pdfium\BusyException $e) {
        $currentNeverBusy = false;
    }
    try {
        $doc->extractPages(['x']);
    } catch (TypeError $e) {
    } catch (Pdfium\BusyException $e) {
        $typeErrorNeverBusy = false;
    }
    try {
        $doc->pageCount();
    } catch (Pdfium\BusyException $e) {
        $busyMessage = $busyMessage || $e->getMessage() === 'PDFium is in use by another thread';
    }
    if (!$addBusy) {
        try {
            $added = $adoc->addAttachment('busy', 'x');
            with_retry(fn () => $adoc->deleteAttachment($added->index()));
            $held = with_retry(fn () => $adoc->attachment(0));
        } catch (Pdfium\BusyException $e) {
            $addBusy = true;
            $addBusyChangedNothing = with_retry(fn () => $adoc->attachmentCount()) === 1
                && with_retry(fn () => $held->name()) === 'held';
        }
    }
    for ($try = 0; $try < 20 && !($tagBusy && $readBusy); $try++) {
        try {
            $adoc->isTagged();
        } catch (Pdfium\BusyException $e) {
            $tagBusy = true;
        }
        try {
            $adoc->language();
        } catch (Pdfium\BusyException $e) {
            $readBusy = true;
        }
    }
    for ($try = 0; $try < 20 && !$langBusy; $try++) {
        $changed = false;
        try {
            $adoc->setLanguage('busy');
            $changed = true;
        } catch (Pdfium\BusyException $e) {
            $langBusy = true;
            $langBusyChangedNothing = with_retry(fn () => $adoc->language()) === 'held';
        }
        if ($changed) {
            with_retry(fn () => $adoc->setLanguage('held'));
        }
    }
    try {
        $sdoc->signatures();
    } catch (Pdfium\BusyException $e) {
        $sigBusy = true;
    }
    try {
        $sdoc->signatureCount();
    } catch (Pdfium\BusyException $e) {
        $sigCountBusy = true;
    }
    foreach ($formOps as $name => $op) {
        if (isset($formBusy[$name])) {
            continue;
        }
        try {
            $op();
            if (str_starts_with($name, 'set') || $name === 'select') {
                $known = $snapshot();
            }
        } catch (Pdfium\BusyException $e) {
            $formBusy[$name] = true;
            $formUnchanged = $formUnchanged && $snapshot() === $known;
        }
    }
    if (!$flattenBusy && $pool) {
        $flat = $used[] = array_pop($pool);
        try {
            $flat->flatten();
        } catch (Pdfium\BusyException $e) {
            $flattenBusy = true;
            $flattenUnchanged = count(with_retry(fn () => $flat->formFields())) > 0;
        }
    }
    try {
        $doc->save($target);
        unlink($target);
    } catch (Pdfium\BusyException $e) {
        $saveBusy = true;
        $noFileAfterSaveBusy = $noFileAfterSaveBusy && file_exists($target) === false;
    }
}

$renders = renderer_stop($renderer);
var_dump($renders > 0);
echo 'busy: ', $busyMessage ? 'yes' : 'no', "\n";
echo 'save busy left no file: ', $saveBusy && $noFileAfterSaveBusy ? 'yes' : 'no', "\n";
echo 'busy add changed nothing: ', $addBusy && $addBusyChangedNothing ? 'yes' : 'no', "\n";
echo 'signatures busy, then listed: ', $sigBusy && with_retry(fn () => $sdoc->signatures())[0]->contents === "\x01" ? 'yes' : 'no', "\n";
echo 'signature count busy, then counted: ', $sigCountBusy && with_retry(fn () => $sdoc->signatureCount()) === 1 ? 'yes' : 'no', "\n";
echo 'busy setLanguage changed nothing: ', $langBusy && $langBusyChangedNothing ? 'yes' : 'no', "\n";
echo 'isTagged and language busy: ', $tagBusy && $readBusy ? 'yes' : 'no', "\n";
echo 'form methods busy: ', count($formBusy) === count($formOps) ? 'yes' : 'no', "\n";
echo 'flatten busy: ', $flattenBusy ? 'yes' : 'no', "\n";
echo 'busy form calls changed nothing: ', $formUnchanged && $flattenUnchanged ? 'yes' : 'no', "\n";
$after = $snapshot();
echo 'form handles work: ', $after[0] === 'busy' && $after[1] === true && array_sum($after[2]) >= 1
    && with_retry(fn () => $formDoc->formType()) === Pdfium\FormType::AcroForm
    && count(with_retry(fn () => $formPage->formFields())) === $fieldCount ? 'yes' : 'no', "\n";
echo 'bitmap never busy: ', $bitmapNeverBusy ? 'yes' : 'no', "\n";
echo 'release never busy: ', $releaseNeverBusy ? 'yes' : 'no', "\n";
echo 'loaded current never busy: ', $currentNeverBusy ? 'yes' : 'no', "\n";
echo 'type error never busy: ', $typeErrorNeverBusy ? 'yes' : 'no', "\n";
?>
--EXPECT--
bool(true)
busy: yes
save busy left no file: yes
busy add changed nothing: yes
signatures busy, then listed: yes
signature count busy, then counted: yes
busy setLanguage changed nothing: yes
isTagged and language busy: yes
form methods busy: yes
flatten busy: yes
busy form calls changed nothing: yes
form handles work: yes
bitmap never busy: yes
release never busy: yes
loaded current never busy: yes
type error never busy: yes
