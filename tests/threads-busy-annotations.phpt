--TEST--
While another thread is inside PDFium, the annotation methods throw BusyException and do nothing; releasing an Annotation or an annotation's Attachment never does
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
require __DIR__ . '/pdf.inc';
$apage = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [10 10 30 30] /Popup 7 0 R /Contents (note) >>',
    '<< /Type /Annot /Subtype /Popup /Rect [40 10 90 30] /Parent 6 0 R >>',
    '<< /Type /Annot /Subtype /Link /Rect [10 40 30 60] /A << /S /URI /URI (https://example.com/) >> >>',
    '<< /Type /Annot /Subtype /FileAttachment /Rect [10 70 30 90] >>',
))->page(0);
[$note, , $link, $clip] = $apage->annotations();
$clip->setAttachment('held.txt', 'held');
$heldFile = $clip->attachment();
$victim = null;
$four = fn() => with_retry(fn() => $apage->annotationCount()) === 4;
$annotationOps = [
    'annotations()' => [fn() => $apage->annotations(), $four],
    'addAnnotation()' => [
        function () use ($apage) {
            $added = $apage->addAnnotation('Square', new Pdfium\Rect(0, 0, 5, 5));
            with_retry(fn() => $added->remove());
        },
        $four,
    ],
    'type()' => [fn() => $note->type(), fn() => with_retry(fn() => $note->type()) === 'Text' && $four()],
    'remove()' => [
        function () use ($apage, &$victim) {
            $victim = with_retry(fn() => $apage->addAnnotation('Square', new Pdfium\Rect(0, 0, 5, 5)));
            usleep(random_int(0, 5000));
            $victim->remove();
        },
        function () use ($apage, &$victim) {
            $kept = with_retry(fn() => $apage->annotationCount()) === 5 && with_retry(fn() => $victim->index()) === 4;
            with_retry(fn() => $victim->remove());
            return $kept && with_retry(fn() => $apage->annotationCount()) === 4;
        },
    ],
    'linkedAnnotation()' => [
        fn() => $note->linkedAnnotation('Popup'),
        fn() => with_retry(fn() => $note->linkedAnnotation('Popup')->type()) === 'Popup',
    ],
    'link()' => [fn() => $link->link(), fn() => with_retry(fn() => $link->link()->action->uri) === 'https://example.com/'],
    'setUri()' => [
        function () use ($link) {
            $link->setUri('https://example.com/busy');
            with_retry(fn() => $link->setUri('https://example.com/'));
        },
        fn() => with_retry(fn() => $link->link()->action->uri) === 'https://example.com/',
    ],
    'attachment()' => [fn() => $clip->attachment(), fn() => with_retry(fn() => $clip->attachment()->name()) === 'held.txt'],
    'setAttachment()' => [
        function () use ($clip, &$heldFile) {
            $clip->setAttachment('busy.txt', 'b');
            with_retry(fn() => $clip->setAttachment('held.txt', 'held'));
            $heldFile = with_retry(fn() => $clip->attachment());
        },
        function () use (&$heldFile) {
            return with_retry(fn() => $heldFile->contents()) === 'held';
        },
    ],
    'setFlags()' => [
        function () use ($note) {
            $note->setFlags(Pdfium\Annotation::FLAG_HIDDEN);
            with_retry(fn() => $note->setFlags(0));
        },
        fn() => with_retry(fn() => $note->flags()) === 0,
    ],
];
$annotationBusy = [];

$renderer = renderer_start();

$releaseNeverBusy = true;
$end = microtime(true) + 10;
while (count($annotationBusy) < count($annotationOps) && microtime(true) < $end) {
    $released = with_retry(fn () => $apage->annotation(0));
    $releasedFile = with_retry(fn () => $apage->annotation(3)->attachment());
    usleep(random_int(0, 5000));
    try {
        unset($released, $releasedFile);
    } catch (Pdfium\BusyException $e) {
        $releaseNeverBusy = false;
    }
    foreach ($annotationOps as $name => [$try, $changedNothing]) {
        if (isset($annotationBusy[$name])) {
            continue;
        }
        usleep(random_int(0, 5000));
        try {
            $try();
        } catch (Pdfium\BusyException $e) {
            $annotationBusy[$name] = $changedNothing();
        }
    }
}

$renders = renderer_stop($renderer);
var_dump($renders > 0);
foreach (array_keys($annotationOps) as $name) {
    echo "busy $name changed nothing: ", ($annotationBusy[$name] ?? false) ? 'yes' : 'no', "\n";
}
echo 'release never busy: ', $releaseNeverBusy ? 'yes' : 'no', "\n";
?>
--EXPECT--
bool(true)
busy annotations() changed nothing: yes
busy addAnnotation() changed nothing: yes
busy type() changed nothing: yes
busy remove() changed nothing: yes
busy linkedAnnotation() changed nothing: yes
busy link() changed nothing: yes
busy setUri() changed nothing: yes
busy attachment() changed nothing: yes
busy setAttachment() changed nothing: yes
busy setFlags() changed nothing: yes
release never busy: yes
