#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_annot.h>
#include <fpdf_flatten.h>
#include "zend_enum.h"
#include "zend_exceptions.h"

typedef struct {
	pdfium_page_handle *ph;
	FPDF_FORMHANDLE form;
	FPDF_ANNOTATION annot;
	int option;
} field_span;

static const char *const form_type_cases[] = {"None", "AcroForm", "XfaFull", "XfaForeground"};

static const char *const field_type_cases[] = {
	"Unknown", "PushButton", "Checkbox", "RadioButton", "ComboBox", "ListBox", "Text", "Signature",
};

static unsigned long name_get(void *source, unsigned short *buf, unsigned long len)
{
	field_span *s = source;

	return FPDFAnnot_GetFormFieldName(s->form, s->annot, buf, len);
}

static unsigned long alternate_name_get(void *source, unsigned short *buf, unsigned long len)
{
	field_span *s = source;

	return FPDFAnnot_GetFormFieldAlternateName(s->form, s->annot, buf, len);
}

static unsigned long value_get(void *source, unsigned short *buf, unsigned long len)
{
	field_span *s = source;

	return FPDFAnnot_GetFormFieldValue(s->form, s->annot, buf, len);
}

static unsigned long export_value_get(void *source, unsigned short *buf, unsigned long len)
{
	field_span *s = source;

	return FPDFAnnot_GetFormFieldExportValue(s->form, s->annot, buf, len);
}

static unsigned long option_label_get(void *source, unsigned short *buf, unsigned long len)
{
	field_span *s = source;

	return FPDFAnnot_GetOptionLabel(s->form, s->annot, s->option, buf, len);
}

ZEND_METHOD(Pdfium_FormField, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_FormFieldOption, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_FormFieldFlags, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_form_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_form_field_option, "label selected")
		&& pdfium_slots_named(pdfium_ce_form_field_flags, "readOnly required noExport multiline password editable multiSelect");
}

static zend_object *field_new(pdfium_page *p, int index)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_form_field);
	pdfium_form_field *f = pdfium_form_field_from_obj(Z_OBJ(zv));

	f->ph = p->ph;
	f->page_index = p->index;
	f->index = index;
	pdfium_page_handle_addref(p->ph);
	return Z_OBJ(zv);
}

static FPDF_PAGE fill_get_page(FPDF_FORMFILLINFO *info, FPDF_DOCUMENT doc, int index)
{
	pdfium_doc_handle *h = (pdfium_doc_handle *) ((char *) info - XtOffsetOf(pdfium_doc_handle, form_info));
	pdfium_fill *fill = &h->fill;

	if (index == fill->page_index) {
		return fill->page;
	}
	if (!fill->editing) {
		return NULL;
	}
	if (!fill->pages) {
		int count = FPDF_GetPageCount(h->doc);

		if (count <= 0 || !pdfium_memory_fits((size_t) count * sizeof(FPDF_PAGE))) {
			return NULL;
		}
		fill->pages = calloc((size_t) count, sizeof(FPDF_PAGE));
		if (!fill->pages) {
			return NULL;
		}
		fill->count = count;
	}
	if (index < 0 || index >= fill->count) {
		return NULL;
	}
	if (!fill->pages[index]) {
		FPDF_PAGE page = FPDF_LoadPage(h->doc, index);

		if (!page) {
			return NULL;
		}
		fill->pages[index] = page;
		FORM_OnAfterLoadPage(page, h->form);
	}
	return fill->pages[index];
}

static bool field_enter(zval *self, field_span *s)
{
	pdfium_form_field *f = pdfium_form_field_from_obj(Z_OBJ_P(self));

	s->ph = f->ph;
	if (!pdfium_enter()) {
		return false;
	}
	s->form = pdfium_page_form(f->ph);
	s->annot = FPDFPage_GetAnnot(f->ph->page, f->index);
	if (s->annot && FPDFAnnot_GetSubtype(s->annot) != FPDF_ANNOT_WIDGET) {
		FPDFPage_CloseAnnot(s->annot);
		s->annot = NULL;
	}
	if (!s->annot) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "The form field is gone; its page may have been flattened", 0);
		return false;
	}
	f->ph->h->fill = (pdfium_fill) {f->ph->page, f->page_index, NULL, 0, false};
	f->ph->h->form_info.FFI_GetPage = fill_get_page;
	return true;
}

static void field_leave(field_span *s)
{
	pdfium_doc_handle *h = s->ph->h;
	pdfium_fill fill = h->fill;

	h->form_info.FFI_GetPage = NULL;
	h->fill = (pdfium_fill) {0};
	for (int i = 0; i < fill.count; i++) {
		if (fill.pages[i]) {
			FORM_OnBeforeClosePage(fill.pages[i], h->form);
			FPDF_ClosePage(fill.pages[i]);
		}
	}
	free(fill.pages);
	FPDFPage_CloseAnnot(s->annot);
	pdfium_leave();
}

static const char *case_name(const char *const *names, size_t count, int value)
{
	return value >= 0 && (size_t) value < count ? names[value] : names[0];
}

#define CASE_NAME(cases, value) case_name(cases, sizeof(cases) / sizeof(cases[0]), value)

static unsigned short *units_take(pdfium_utf16le_getter get, void *source, size_t *count, size_t *used, bool *oom)
{
	unsigned long bytes = get(source, NULL, 0);
	unsigned short *units = NULL;

	*count = 0;
	if (bytes <= 2) {
		return NULL;
	}
	*used += (size_t) bytes + (size_t) bytes / 2 * 3;
	if (!pdfium_memory_fits(*used) || !(units = malloc(bytes))) {
		*oom = true;
		return NULL;
	}
	*count = pdfium_utf16le_read(get, source, units, bytes);
	return units;
}

static zend_string *units_finish(unsigned short *units, size_t count)
{
	zend_string *s = count ? pdfium_utf16le_to_utf8(units, count) : NULL;

	free(units);
	return s;
}

static void string_method(INTERNAL_FUNCTION_PARAMETERS, pdfium_utf16le_getter get, bool nullable)
{
	field_span s;
	size_t count;
	size_t used = 0;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	unsigned short *units = units_take(get, &s, &count, &used, &oom);

	field_leave(&s);
	if (oom) {
		zend_throw_exception(pdfium_ce_memory_exception, "The form field needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	zend_string *str = units_finish(units, count);

	if (str) {
		RETURN_STR(str);
	}
	if (nullable) {
		RETURN_NULL();
	}
	RETURN_EMPTY_STRING();
}

ZEND_METHOD(Pdfium_Document, formType)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int type = FPDF_GetFormType(pdfium_this_document(ZEND_THIS)->doc);

	pdfium_leave();
	RETURN_OBJ_COPY(zend_enum_get_case_cstr(pdfium_ce_form_type, CASE_NAME(form_type_cases, type)));
}

ZEND_METHOD(Pdfium_Page, formFields)
{
	pdfium_page *p = pdfium_page_from_obj(Z_OBJ_P(ZEND_THIS));
	pdfium_page_handle *ph = p->ph;
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	if (!pdfium_page_form(ph)) {
		pdfium_leave();
		RETURN_EMPTY_ARRAY();
	}
	int count = FPDFPage_GetAnnotCount(ph->page);
	int *widgets = NULL;
	int n = 0;

	if (count < 0) {
		count = 0;
	}
	if (!pdfium_memory_fits((size_t) count * (sizeof(int) + sizeof(pdfium_form_field) + sizeof(zval)))
		|| (count && !(widgets = malloc((size_t) count * sizeof(int))))) {
		oom = "The form fields need";
	} else {
		for (int i = 0; i < count; i++) {
			FPDF_ANNOTATION a = FPDFPage_GetAnnot(ph->page, i);

			if (!a) {
				continue;
			}
			if (FPDFAnnot_GetSubtype(a) == FPDF_ANNOT_WIDGET) {
				widgets[n++] = i;
			}
			FPDFPage_CloseAnnot(a);
		}
	}
	pdfium_leave_or_throw(return_value, oom);
	if (!oom) {
		array_init_size(return_value, (uint32_t) n);
		for (int i = 0; i < n; i++) {
			zval zv;

			ZVAL_OBJ(&zv, field_new(p, widgets[i]));
			add_next_index_zval(return_value, &zv);
		}
	}
	free(widgets);
}

ZEND_METHOD(Pdfium_Page, formFieldAt)
{
	pdfium_page *p = pdfium_page_from_obj(Z_OBJ_P(ZEND_THIS));
	pdfium_page_handle *ph = p->ph;
	double x, y;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_DOUBLE(x)
		Z_PARAM_DOUBLE(y)
	ZEND_PARSE_PARAMETERS_END();

	if (!zend_finite(x) || !zend_finite(y)) {
		zend_argument_value_error(zend_finite(x) ? 2 : 1, "must be finite");
		RETURN_THROWS();
	}
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	FPDF_FORMHANDLE form = pdfium_page_form(ph);
	int index = -1;

	if (form) {
		FS_POINTF point = {pdfium_float_side(x), pdfium_float_side(y)};
		FPDF_ANNOTATION a = FPDFAnnot_GetFormFieldAtPoint(form, ph->page, &point);

		if (a) {
			index = FPDFPage_GetAnnotIndex(ph->page, a);
			FPDFPage_CloseAnnot(a);
		}
	}
	pdfium_leave();
	if (index >= 0) {
		RETURN_OBJ(field_new(p, index));
	}
	RETURN_NULL();
}

ZEND_METHOD(Pdfium_Page, flatten)
{
	bool for_print = false;
	pdfium_page *p = pdfium_page_from_obj(Z_OBJ_P(ZEND_THIS));
	FPDF_PAGE fresh = NULL;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(for_print)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	pdfium_page_handle *ph = p->ph;

	if (FPDF_GetFormType(ph->h->doc) != FORMTYPE_NONE) {
		pdfium_page_form(ph);
	}
	if (ph->form_loaded) {
		FORM_OnBeforeClosePage(ph->page, ph->h->form);
		ph->form_loaded = false;
	}
	int result = FPDFPage_Flatten(ph->page, for_print ? FLAT_PRINT : FLAT_NORMALDISPLAY);

	if (result == FLATTEN_SUCCESS) {
		fresh = FPDF_LoadPage(ph->h->doc, p->index);
	}
	pdfium_leave();
	if (result == FLATTEN_NOTHINGTODO) {
		RETURN_FALSE;
	}
	if (result != FLATTEN_SUCCESS) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot flatten page %d", p->index);
		RETURN_THROWS();
	}
	pdfium_page_swap_handle(p, fresh);
	if (!fresh) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot load page %d", p->index);
		RETURN_THROWS();
	}
	RETURN_TRUE;
}

ZEND_METHOD(Pdfium_FormField, flags)
{
	field_span s;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);
	int bits = FPDFAnnot_GetFormFieldFlags(s.form, s.annot);

	field_leave(&s);
	bool values[] = {
		bits & FPDF_FORMFLAG_READONLY,
		bits & FPDF_FORMFLAG_REQUIRED,
		bits & FPDF_FORMFLAG_NOEXPORT,
		type == FPDF_FORMFIELD_TEXTFIELD && (bits & FPDF_FORMFLAG_TEXT_MULTILINE),
		type == FPDF_FORMFIELD_TEXTFIELD && (bits & FPDF_FORMFLAG_TEXT_PASSWORD),
		type == FPDF_FORMFIELD_COMBOBOX && (bits & FPDF_FORMFLAG_CHOICE_EDIT),
		type == FPDF_FORMFIELD_LISTBOX && (bits & FPDF_FORMFLAG_CHOICE_MULTI_SELECT),
	};

	object_init_ex(return_value, pdfium_ce_form_field_flags);
	for (size_t i = 0; i < sizeof(values) / sizeof(values[0]); i++) {
		ZVAL_BOOL(pdfium_slot(Z_OBJ_P(return_value), (int) i), values[i]);
	}
}

typedef struct { unsigned short *units; size_t count; bool selected; } option_label;

ZEND_METHOD(Pdfium_FormField, options)
{
	field_span s;
	option_label *labels = NULL;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);
	int count = type == FPDF_FORMFIELD_COMBOBOX || type == FPDF_FORMFIELD_LISTBOX ? FPDFAnnot_GetOptionCount(s.form, s.annot) : 0;

	if (count < 0) {
		count = 0;
	}
	size_t used = (size_t) count * (sizeof(option_label) + sizeof(zval) + sizeof(zend_object) + 2 * sizeof(zval));

	if (!pdfium_memory_fits(used) || (count && !(labels = calloc((size_t) count, sizeof(option_label))))) {
		oom = true;
	} else {
		for (s.option = 0; s.option < count && !oom; s.option++) {
			option_label *l = &labels[s.option];

			l->units = units_take(option_label_get, &s, &l->count, &used, &oom);
			l->selected = FPDFAnnot_IsOptionSelected(s.form, s.annot, s.option);
		}
	}
	field_leave(&s);
	if (oom) {
		for (int i = 0; labels && i < count; i++) {
			free(labels[i].units);
		}
		free(labels);
		zend_throw_exception(pdfium_ce_memory_exception, "The form field options need more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	array_init_size(return_value, (uint32_t) count);
	for (int i = 0; i < count; i++) {
		zend_string *label = units_finish(labels[i].units, labels[i].count);
		zval zv;

		object_init_ex(&zv, pdfium_ce_form_field_option);
		ZVAL_STR(pdfium_slot(Z_OBJ(zv), 0), label ? label : ZSTR_EMPTY_ALLOC());
		ZVAL_BOOL(pdfium_slot(Z_OBJ(zv), 1), labels[i].selected);
		add_next_index_zval(return_value, &zv);
	}
	free(labels);
}

static void wrong_type(const char *action, int type, const char *tail)
{
	const char *name = CASE_NAME(field_type_cases, type);

	zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot %s %s %s field%s", action, strchr("AEIOU", name[0]) ? "an" : "a",
		name, tail);
}

ZEND_METHOD(Pdfium_FormField, setValue)
{
	zend_string *value;
	field_span s;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(value)
	ZEND_PARSE_PARAMETERS_END();

	unsigned short *units = pdfium_utf16_arg(value, 1, "The form field needs");

	if (!units) {
		RETURN_THROWS();
	}
	if (!field_enter(ZEND_THIS, &s)) {
		efree(units);
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);
	bool combo = type == FPDF_FORMFIELD_COMBOBOX;
	bool takes_text = type == FPDF_FORMFIELD_TEXTFIELD || (combo && (FPDFAnnot_GetFormFieldFlags(s.form, s.annot) & FPDF_FORMFLAG_CHOICE_EDIT));
	const char *fail = NULL;

	if (takes_text) {
		s.ph->h->fill.editing = true;
		if (FORM_SetFocusedAnnot(s.form, s.annot) && FORM_SelectAllText(s.form, s.ph->page)
			&& FORM_OnChar(s.form, s.ph->page, '\b', 0)) {
			FORM_ReplaceSelection(s.form, s.ph->page, units);
		} else {
			fail = "PDFium refused to edit the field";
		}
		FORM_ForceToKillFocus(s.form);
	}
	field_leave(&s);
	efree(units);
	if (!takes_text) {
		wrong_type("set the text of", type, combo ? " that is not editable" : "");
		RETURN_THROWS();
	}
	if (fail) {
		zend_throw_exception(pdfium_ce_exception, fail, 0);
	}
}

ZEND_METHOD(Pdfium_FormField, setChecked)
{
	bool checked = true;
	field_span s;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(checked)
	ZEND_PARSE_PARAMETERS_END();

	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);
	bool button = type == FPDF_FORMFIELD_CHECKBOX || type == FPDF_FORMFIELD_RADIOBUTTON;
	const char *fail = NULL;

	if (button && (bool) FPDFAnnot_IsChecked(s.form, s.annot) != checked) {
		s.ph->h->fill.editing = true;
		if (!FORM_SetFocusedAnnot(s.form, s.annot) || !FORM_OnChar(s.form, s.ph->page, ' ', 0)) {
			fail = "PDFium refused to edit the field";
		}
		FORM_ForceToKillFocus(s.form);
		s.ph->h->fill.editing = false;
		if (!fail && (bool) FPDFAnnot_IsChecked(s.form, s.annot) != checked) {
			fail = FPDFAnnot_GetFormFieldFlags(s.form, s.annot) & FPDF_FORMFLAG_READONLY
				? "PDFium did not change the field; it is read-only" : "PDFium did not change the field";
		}
	}
	field_leave(&s);
	if (!button) {
		wrong_type("check or uncheck", type, "");
		RETURN_THROWS();
	}
	if (fail) {
		zend_throw_exception(pdfium_ce_exception, fail, 0);
	}
}

ZEND_METHOD(Pdfium_FormField, select)
{
	zend_long option;
	bool selected = true;
	field_span s;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_LONG(option)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(selected)
	ZEND_PARSE_PARAMETERS_END();

	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);
	bool choice = type == FPDF_FORMFIELD_COMBOBOX || type == FPDF_FORMFIELD_LISTBOX;
	int count = choice ? FPDFAnnot_GetOptionCount(s.form, s.annot) : 0;
	bool in_range = option >= 0 && option < count;
	const char *fail = NULL;

	if (in_range) {
		s.ph->h->fill.editing = true;
		if (!FORM_SetFocusedAnnot(s.form, s.annot)) {
			fail = "PDFium refused to edit the field";
		} else if (!FORM_SetIndexSelected(s.form, s.ph->page, (int) option, selected)) {
			fail = "PDFium did not change the selection";
		}
		FORM_ForceToKillFocus(s.form);
	}
	field_leave(&s);
	if (!choice) {
		wrong_type("select an option of", type, "");
		RETURN_THROWS();
	}
	if (!in_range) {
		if (count > 0) {
			zend_argument_value_error(1, "must be between 0 and %d", count - 1);
		} else {
			zend_argument_value_error(1, "must be an option index, but the field has no options");
		}
		RETURN_THROWS();
	}
	if (fail) {
		zend_throw_exception(pdfium_ce_exception, fail, 0);
	}
}

ZEND_METHOD(Pdfium_FormField, name)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, name_get, false);
}

ZEND_METHOD(Pdfium_FormField, alternateName)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, alternate_name_get, true);
}

ZEND_METHOD(Pdfium_FormField, value)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, value_get, false);
}

ZEND_METHOD(Pdfium_FormField, exportValue)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, export_value_get, true);
}

ZEND_METHOD(Pdfium_FormField, type)
{
	field_span s;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(s.form, s.annot);

	field_leave(&s);
	RETURN_OBJ_COPY(zend_enum_get_case_cstr(pdfium_ce_form_field_type, CASE_NAME(field_type_cases, type)));
}

ZEND_METHOD(Pdfium_FormField, isChecked)
{
	field_span s;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	bool checked = FPDFAnnot_IsChecked(s.form, s.annot);

	field_leave(&s);
	RETURN_BOOL(checked);
}

ZEND_METHOD(Pdfium_FormField, rect)
{
	field_span s;
	FS_RECTF r = {0};

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	FPDFAnnot_GetRect(s.annot, &r);
	field_leave(&s);
	pdfium_rect_init(return_value, r.left, r.bottom, r.right, r.top);
}

static void control_method(INTERNAL_FUNCTION_PARAMETERS, bool count)
{
	field_span s;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!field_enter(ZEND_THIS, &s)) {
		RETURN_THROWS();
	}
	int n = count ? FPDFAnnot_GetFormControlCount(s.form, s.annot) : FPDFAnnot_GetFormControlIndex(s.form, s.annot);

	field_leave(&s);
	if (n < 0) {
		zend_throw_exception(pdfium_ce_exception, "Cannot read the field's place in its group", 0);
		RETURN_THROWS();
	}
	RETURN_LONG(n);
}

ZEND_METHOD(Pdfium_FormField, controlIndex)
{
	control_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
}

ZEND_METHOD(Pdfium_FormField, controlCount)
{
	control_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
}

void pdfium_form_field_free_obj(zend_object *obj)
{
	pdfium_form_field *f = pdfium_form_field_from_obj(obj);

	if (f->ph) {
		pdfium_page_handle_release(f->ph);
	}
	zend_object_std_dtor(obj);
}
