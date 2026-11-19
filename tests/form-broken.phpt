--TEST--
Broken forms (loops and 60 levels in the /Parent chain, no /FT, no /AcroForm or /Fields, odd /Annots entries, bad /Opt, /MaxLen and /Rect, comb, password and rich-text fields) neither crash nor hang any form method
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';

function show(mixed $v): string
{
    return match (true) {
        $v instanceof UnitEnum => $v->name,
        $v instanceof Pdfium\FormFieldFlags => implode(',', array_keys(array_filter(get_object_vars($v)))),
        $v instanceof Pdfium\Rect => sprintf('%g,%g,%g,%g', $v->left, $v->bottom, $v->right, $v->top),
        is_array($v) => '[' . implode('|', array_map(fn($o) => $o->label . ($o->selected ? '*' : ''), $v)) . ']',
        default => json_encode($v),
    };
}

function try_call(callable $f): string
{
    try {
        return show($f());
    } catch (Throwable $e) {
        return get_class($e) . ': ' . $e->getMessage();
    }
}

function exercise(string $name, string $pdf): void
{
    echo "$name\n";
    $doc = Pdfium\Document::fromString($pdf);
    echo '  formType: ', try_call(fn() => $doc->formType()), "\n";
    $page = $doc->page(0);
    $fields = $page->formFields();
    echo '  fields: ', count($fields), "\n";
    echo '  formFieldAt: ', try_call(fn() => $page->formFieldAt(200, 710)?->name()), "\n";
    foreach ($fields as $i => $f) {
        echo "  [$i]\n";
        foreach (['name', 'alternateName', 'type', 'value', 'exportValue', 'isChecked', 'options', 'flags', 'rect', 'controlIndex', 'controlCount'] as $m) {
            echo "    $m: ", try_call(fn() => $f->$m()), "\n";
        }
        echo '    setValue: ', try_call(fn() => $f->setValue('x') ?? 'ok'), "\n";
        echo '    setChecked: ', try_call(fn() => $f->setChecked() ?? 'ok'), "\n";
        echo '    select: ', try_call(fn() => $f->select(0) ?? 'ok'), "\n";
    }
    echo '  flatten: ', try_call(fn() => $page->flatten()), "\n";
    echo '  after: ', try_call(fn() => count($page->formFields())), "\n";
    echo '  saved: ', try_call(fn() => count(Pdfium\Document::fromString($doc->toString())->page(0)->formFields())), "\n";
}

$w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4';
$r = '/Rect [100 700 300 720]';
$text = fn(string $more = '', string $name = 'a') => "<< $w /FT /Tx /T ($name) $r $more >>";

exercise('parent loop', form_pdf([
    8 => "<< $w /Parent 9 0 R /FT /Tx /T (a) $r >>",
    9 => '<< /T (b) /Parent 8 0 R /Kids [8 0 R] >>',
]));

$deep = [8 => "<< $w /Parent 9 0 R $r >>"];
for ($i = 9; $i < 68; $i++) {
    $deep[$i] = '<< /T (p' . $i . ') /Parent ' . ($i + 1) . ' 0 R /Kids [' . ($i - 1) . ' 0 R] >>';
}
$deep[68] = '<< /T (top) /FT /Tx /Kids [67 0 R] >>';
exercise('parent chain of 60', form_pdf($deep, [4 => '<< /Fields [68 0 R] /DA (/Helv 12 Tf 0 g) >>']));

exercise('no FT', form_pdf([8 => "<< $w /T (a) $r >>"]));

exercise('widget outside /Fields', form_pdf([8 => $text()], [4 => '<< /Fields [] /DA (/Helv 12 Tf 0 g) >>']));
exercise('no /Fields', form_pdf([8 => $text()], [4 => '<< /DA (/Helv 12 Tf 0 g) >>']));
exercise('no /AcroForm', form_pdf([8 => $text()], [1 => '<< /Type /Catalog /Pages 2 0 R >>']));

exercise('odd /Annots', form_pdf([8 => $text(name: 'first'), 9 => $text(name: 'second')], [
    3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [8 0 R 42 null 99 0 R 9 0 R] >>',
    4 => '<< /Fields [8 0 R 9 0 R] /DA (/Helv 12 Tf 0 g) >>',
]));

$choice = fn(string $ff, string $opt) => "<< $w /FT /Ch /Ff $ff /T (c) /Opt $opt $r >>";
exercise('/Opt with a number', form_pdf([8 => $choice('131072', '[5 (ok) 7]')]));
exercise('/Opt nested wrongly', form_pdf([8 => $choice('2097152', '[[(a)] [(a) (b) (c)] [] (ok)]')]));
exercise('/Opt not an array', form_pdf([8 => $choice('131072', '(x)')]));
exercise('/MaxLen 0', form_pdf([8 => $text('/MaxLen 0 /V (abc)')]));
exercise('/MaxLen -1', form_pdf([8 => $text('/MaxLen -1 /V (abc)')]));
exercise('/Rect of three numbers', form_pdf([8 => "<< $w /FT /Tx /T (a) /Rect [100 700 300] >>"]));
exercise('comb', form_pdf([8 => $text('/Ff 16777216 /MaxLen 4 /V (ab)')]));
exercise('password', form_pdf([8 => $text('/Ff 8192 /V (secret)')]));
exercise('rich text', form_pdf([8 => $text('/Ff 33554432 /V (plain) /RV (<body><p>rich</p></body>)')]));
?>
--EXPECT--
parent loop
  formType: AcroForm
  fields: 1
  formFieldAt: "b.a"
  [0]
    name: "b.a"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
parent chain of 60
  formType: AcroForm
  fields: 1
  formFieldAt: null
  [0]
    name: ""
    alternateName: null
    type: Unknown
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: Pdfium\PdfiumException: Cannot read the field's place in its group
    controlCount: Pdfium\PdfiumException: Cannot read the field's place in its group
    setValue: Pdfium\PdfiumException: Cannot set the text of an Unknown field
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck an Unknown field
    select: Pdfium\PdfiumException: Cannot select an option of an Unknown field
  flatten: true
  after: 0
  saved: 0
no FT
  formType: AcroForm
  fields: 1
  formFieldAt: null
  [0]
    name: ""
    alternateName: null
    type: Unknown
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: Pdfium\PdfiumException: Cannot read the field's place in its group
    controlCount: Pdfium\PdfiumException: Cannot read the field's place in its group
    setValue: Pdfium\PdfiumException: Cannot set the text of an Unknown field
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck an Unknown field
    select: Pdfium\PdfiumException: Cannot select an option of an Unknown field
  flatten: true
  after: 0
  saved: 0
widget outside /Fields
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
no /Fields
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
no /AcroForm
  formType: None
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
odd /Annots
  formType: AcroForm
  fields: 2
  formFieldAt: "second"
  [0]
    name: "first"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  [1]
    name: "second"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
/Opt with a number
  formType: AcroForm
  fields: 1
  formFieldAt: "c"
  [0]
    name: "c"
    alternateName: null
    type: ComboBox
    value: ""
    exportValue: null
    isChecked: false
    options: [|ok|]
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: Pdfium\PdfiumException: Cannot set the text of a ComboBox field that is not editable
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a ComboBox field
    select: "ok"
  flatten: true
  after: 0
  saved: 0
/Opt nested wrongly
  formType: AcroForm
  fields: 1
  formFieldAt: "c"
  [0]
    name: "c"
    alternateName: null
    type: ListBox
    value: ""
    exportValue: null
    isChecked: false
    options: [|b||ok]
    flags: multiSelect
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: Pdfium\PdfiumException: Cannot set the text of a ListBox field
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a ListBox field
    select: "ok"
  flatten: true
  after: 0
  saved: 0
/Opt not an array
  formType: AcroForm
  fields: 1
  formFieldAt: "c"
  [0]
    name: "c"
    alternateName: null
    type: ComboBox
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: Pdfium\PdfiumException: Cannot set the text of a ComboBox field that is not editable
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a ComboBox field
    select: ValueError: Pdfium\FormField::select(): Argument #1 ($option) must be an option index, but the field has no options
  flatten: true
  after: 0
  saved: 0
/MaxLen 0
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: "abc"
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
/MaxLen -1
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: "abc"
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
/Rect of three numbers
  formType: AcroForm
  fields: 1
  formFieldAt: null
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: ""
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 0,0,0,0
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
comb
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: "ab"
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
password
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: "secret"
    exportValue: null
    isChecked: false
    options: []
    flags: password
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
rich text
  formType: AcroForm
  fields: 1
  formFieldAt: "a"
  [0]
    name: "a"
    alternateName: null
    type: Text
    value: "plain"
    exportValue: null
    isChecked: false
    options: []
    flags: 
    rect: 100,700,300,720
    controlIndex: 0
    controlCount: 1
    setValue: "ok"
    setChecked: Pdfium\PdfiumException: Cannot check or uncheck a Text field
    select: Pdfium\PdfiumException: Cannot select an option of a Text field
  flatten: true
  after: 0
  saved: 0
