--TEST--
Page edits on an encrypted document are saved with its encryption kept: the result opens only with the password, shows every edit and reports the same permissions; permissions that forbid editing are not enforced
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user');
$permissions = json_encode($doc->permissions());
$doc->deletePage(0);
$doc->addPage(200, 300);
$doc->movePages([1], 0);
$doc->page(0)->setRotation(90);
$doc->page(1)->setCropBox(new Pdfium\Rect(10, 20, 300, 400));
var_dump(json_encode($doc->permissions()) === $permissions);
$kept = $doc->toString();
try { Pdfium\Document::fromString($kept); echo "opened\n"; } catch (Pdfium\PasswordException) { echo "PasswordException\n"; }
$back = Pdfium\Document::fromString($kept, password: 'user');
foreach ($back as $i => $page) {
    $box = $page->cropBox();
    echo $i, ': ', json_encode(trim($page->text())), ' ', $page->width(), 'x', $page->height(), ' ', $page->rotation(),
        ' ', $box ? "$box->left $box->bottom $box->right $box->top" : 'no crop box', "\n";
}
var_dump(json_encode($back->permissions()) === $permissions);
$owner = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-owner.pdf');
var_dump($owner->permissions()->modify, $owner->permissions()->assemble);
$permissions = json_encode($owner->permissions());
$owner->deletePage(0);
$owner->movePages([1], 0);
$owner->page(0)->setRotation(180);
$back = Pdfium\Document::fromString($owner->toString());
echo $back->pageCount(), ' ', json_encode(trim($back->page(0)->text())), ' ', $back->page(0)->rotation(), "\n";
var_dump(json_encode($owner->permissions()) === $permissions, json_encode($back->permissions()) === $permissions);
?>
--EXPECT--
bool(true)
PasswordException
0: "Page 3 gamma" 792x612 90 no crop box
1: "Page 2 beta" 290x380 0 10 20 300 400
2: "" 200x300 0 no crop box
bool(true)
bool(false)
bool(false)
2 "Page 3 gamma" 180
bool(true)
bool(true)
