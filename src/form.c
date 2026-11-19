#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_annot.h>
#include <fpdf_flatten.h>
#include "zend_enum.h"
#include "zend_exceptions.h"

typedef struct { FPDF_FORMHANDLE form; FPDF_ANNOTATION annot; } field_source;

static const char *const form_type_cases[] = {"None", "AcroForm", "XfaFull", "XfaForeground"};

static const char *const field_type_cases[] = {
	"Unknown", "PushButton", "Checkbox", "RadioButton", "ComboBox", "ListBox", "Text", "Signature",
};

static unsigned long name_get(void *source, unsigned short *buf, unsigned long len)
{
	field_source *s = source;

	return FPDFAnnot_GetFormFieldName(s->form, s->annot, buf, len);
}

static unsigned long alternate_name_get(void *source, unsigned short *buf, unsigned long len)
{
	field_source *s = source;

	return FPDFAnnot_GetFormFieldAlternateName(s->form, s->annot, buf, len);
}

static unsigned long value_get(void *source, unsigned short *buf, unsigned long len)
{
	field_source *s = source;

	return FPDFAnnot_GetFormFieldValue(s->form, s->annot, buf, len);
}

static unsigned long export_value_get(void *source, unsigned short *buf, unsigned long len)
{
	field_source *s = source;

	return FPDFAnnot_GetFormFieldExportValue(s->form, s->annot, buf, len);
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

static void page_form_load(pdfium_page_handle *ph, FPDF_FORMHANDLE form)
{
	if (ph->page && !ph->form_loaded) {
		FORM_OnAfterLoadPage(ph->page, form);
		ph->form_loaded = true;
	}
}

static FPDF_ANNOTATION field_enter(zval *self, pdfium_form_field **f, FPDF_FORMHANDLE *form)
{
	pdfium_form_field *ff = pdfium_form_field_from_obj(Z_OBJ_P(self));

	*f = ff;
	if (!pdfium_enter()) {
		return NULL;
	}
	FPDF_FORMHANDLE fh = pdfium_doc_handle_form(ff->ph->h);

	if (!fh) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "PDFium cannot set up form support for this document", 0);
		return NULL;
	}
	page_form_load(ff->ph, fh);

	FPDF_ANNOTATION a = ff->ph->page ? FPDFPage_GetAnnot(ff->ph->page, ff->index) : NULL;

	if (a && FPDFAnnot_GetSubtype(a) != FPDF_ANNOT_WIDGET) {
		FPDFPage_CloseAnnot(a);
		a = NULL;
	}
	if (!a) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "The form field is gone; its page may have been flattened", 0);
		return NULL;
	}
	if (form) {
		*form = fh;
	}
	return a;
}

static void field_leave(FPDF_ANNOTATION a)
{
	FPDFPage_CloseAnnot(a);
	pdfium_leave();
}

static const char *case_name(const char *const *names, size_t count, int value)
{
	return value >= 0 && (size_t) value < count ? names[value] : names[0];
}

static void enum_case(zval *return_value, zend_class_entry *ce, const char *const *names, size_t count, int value)
{
	RETURN_OBJ_COPY(zend_enum_get_case_cstr(ce, case_name(names, count, value)));
}

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

static void string_method(INTERNAL_FUNCTION_PARAMETERS, pdfium_utf16le_getter get, bool nullable, bool buttons_only)
{
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;
	unsigned short *units = NULL;
	size_t count = 0;
	size_t used = 0;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	field_source src = {form, a};
	int type = buttons_only ? FPDFAnnot_GetFormFieldType(form, a) : FPDF_FORMFIELD_UNKNOWN;

	if (!buttons_only || type == FPDF_FORMFIELD_CHECKBOX || type == FPDF_FORMFIELD_RADIOBUTTON) {
		units = units_take(get, &src, &count, &used, &oom);
	}
	field_leave(a);
	if (oom) {
		zend_throw_exception(pdfium_ce_memory_exception, "The form field needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	zend_string *s = pdfium_utf16le_finish(units, count);

	if (s) {
		RETURN_STR(s);
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
	enum_case(return_value, pdfium_ce_form_type, form_type_cases, sizeof(form_type_cases) / sizeof(form_type_cases[0]), type);
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
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(ph->h);

	if (!form) {
		pdfium_leave();
		RETURN_EMPTY_ARRAY();
	}
	page_form_load(ph, form);

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
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(ph->h);
	int index = -1;

	if (form && ph->page) {
		page_form_load(ph, form);

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
	FPDF_FORMHANDLE form = FPDF_GetFormType(ph->h->doc) != FORMTYPE_NONE ? pdfium_doc_handle_form(ph->h) : NULL;

	if (form) {
		page_form_load(ph, form);
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
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	int bits = FPDFAnnot_GetFormFieldFlags(form, a);

	field_leave(a);
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

typedef struct { FPDF_FORMHANDLE form; FPDF_ANNOTATION annot; int index; } option_source;

typedef struct { unsigned short *units; size_t count; bool selected; } option_label;

static unsigned long option_label_get(void *source, unsigned short *buf, unsigned long len)
{
	option_source *s = source;

	return FPDFAnnot_GetOptionLabel(s->form, s->annot, s->index, buf, len);
}

ZEND_METHOD(Pdfium_FormField, options)
{
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;
	option_label *labels = NULL;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	int count = type == FPDF_FORMFIELD_COMBOBOX || type == FPDF_FORMFIELD_LISTBOX ? FPDFAnnot_GetOptionCount(form, a) : 0;

	if (count < 0) {
		count = 0;
	}
	size_t used = (size_t) count * (sizeof(option_label) + sizeof(zval) + sizeof(zend_object) + 2 * sizeof(zval));

	if (!pdfium_memory_fits(used) || (count && !(labels = calloc((size_t) count, sizeof(option_label))))) {
		oom = true;
	} else {
		option_source src = {form, a, 0};

		for (int i = 0; i < count && !oom; i++) {
			src.index = i;
			labels[i].units = units_take(option_label_get, &src, &labels[i].count, &used, &oom);
			labels[i].selected = FPDFAnnot_IsOptionSelected(form, a, i);
		}
	}
	field_leave(a);
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
		zend_string *label = pdfium_utf16le_finish(labels[i].units, labels[i].count);
		zval zv;

		object_init_ex(&zv, pdfium_ce_form_field_option);
		ZVAL_STR(pdfium_slot(Z_OBJ(zv), 0), label ? label : ZSTR_EMPTY_ALLOC());
		ZVAL_BOOL(pdfium_slot(Z_OBJ(zv), 1), labels[i].selected);
		add_next_index_zval(return_value, &zv);
	}
	free(labels);
}

struct pdfium_fill {
	pdfium_form_field *f;
	FPDF_PAGE *pages;
	int count;
};

static struct pdfium_fill fill_state;

static FPDF_PAGE fill_get_page(FPDF_FORMFILLINFO *info, FPDF_DOCUMENT doc, int index)
{
	pdfium_doc_handle *h = (pdfium_doc_handle *) ((char *) info - XtOffsetOf(pdfium_doc_handle, form_info));
	struct pdfium_fill *fill = h->fill;

	if (!fill || doc != h->doc) {
		return NULL;
	}
	if (index == fill->f->page_index) {
		return fill->f->ph->page;
	}
	if (!fill->pages) {
		int count = FPDF_GetPageCount(h->doc);

		if (count <= 0 || !pdfium_memory_fits((size_t) count * sizeof(FPDF_PAGE))) {
			return NULL;
		}
		fill->pages = ecalloc((size_t) count, sizeof(FPDF_PAGE));
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

static bool fill_begin(pdfium_form_field *f, FPDF_FORMHANDLE form, FPDF_ANNOTATION a)
{
	pdfium_doc_handle *h = f->ph->h;

	page_form_load(f->ph, form);
	fill_state = (struct pdfium_fill) {f, NULL, 0};
	h->fill = &fill_state;
	h->form_info.FFI_GetPage = fill_get_page;
	return FORM_SetFocusedAnnot(form, a);
}

static void fill_end(pdfium_form_field *f, FPDF_FORMHANDLE form)
{
	pdfium_doc_handle *h = f->ph->h;
	struct pdfium_fill *fill = h->fill;

	FORM_ForceToKillFocus(form);
	h->form_info.FFI_GetPage = NULL;
	h->fill = NULL;
	for (int i = 0; i < fill->count; i++) {
		if (fill->pages[i]) {
			FORM_OnBeforeClosePage(fill->pages[i], form);
			FPDF_ClosePage(fill->pages[i]);
		}
	}
	if (fill->pages) {
		efree(fill->pages);
	}
	*fill = (struct pdfium_fill) {0};
}

static const char *field_type_name(int type)
{
	return case_name(field_type_cases, sizeof(field_type_cases) / sizeof(field_type_cases[0]), type);
}

static const char *field_type_article(int type)
{
	const char *name = field_type_name(type);

	return name[0] && strchr("AEIOU", name[0]) ? "an" : "a";
}

ZEND_METHOD(Pdfium_FormField, setValue)
{
	zend_string *value;
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;
	size_t count;
	const char *bad;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(value)
	ZEND_PARSE_PARAMETERS_END();

	if ((bad = pdfium_utf8_to_utf16le(value, NULL, &count))) {
		zend_argument_value_error(1, "%s", bad);
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits((count + 1) * sizeof(unsigned short))) {
		zend_throw_exception(pdfium_ce_memory_exception, "The form field needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	unsigned short *units = pdfium_utf8_to_utf16le_alloc(value);
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		efree(units);
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	bool combo = type == FPDF_FORMFIELD_COMBOBOX;
	bool takes_text = type == FPDF_FORMFIELD_TEXTFIELD || (combo && (FPDFAnnot_GetFormFieldFlags(form, a) & FPDF_FORMFLAG_CHOICE_EDIT));
	bool refused = false;

	if (takes_text) {
		refused = !fill_begin(f, form, a) || !FORM_SelectAllText(form, f->ph->page);
		if (!refused) {
			FORM_ReplaceSelection(form, f->ph->page, units);
		}
		fill_end(f, form);
	}
	field_leave(a);
	efree(units);
	if (!takes_text) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, combo ? "Cannot set the text of %s %s field that is not editable"
			: "Cannot set the text of %s %s field", field_type_article(type), field_type_name(type));
		RETURN_THROWS();
	}
	if (refused) {
		zend_throw_exception(pdfium_ce_exception, "PDFium refused to edit the field", 0);
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_FormField, setChecked)
{
	bool checked = true;
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(checked)
	ZEND_PARSE_PARAMETERS_END();

	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	bool button = type == FPDF_FORMFIELD_CHECKBOX || type == FPDF_FORMFIELD_RADIOBUTTON;
	bool refused = false;
	bool unchanged = false;
	bool read_only = false;

	if (button && (bool) FPDFAnnot_IsChecked(form, a) != checked) {
		refused = !fill_begin(f, form, a) || !FORM_OnChar(form, f->ph->page, ' ', 0);
		fill_end(f, form);
		unchanged = (bool) FPDFAnnot_IsChecked(form, a) != checked;
		read_only = FPDFAnnot_GetFormFieldFlags(form, a) & FPDF_FORMFLAG_READONLY;
	}
	field_leave(a);
	if (!button) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot check or uncheck %s %s field", field_type_article(type),
			field_type_name(type));
		RETURN_THROWS();
	}
	if (refused) {
		zend_throw_exception(pdfium_ce_exception, "PDFium refused to edit the field", 0);
		RETURN_THROWS();
	}
	if (unchanged) {
		zend_throw_exception(pdfium_ce_exception, read_only ? "PDFium did not change the field; it is read-only"
			: "PDFium did not change the field", 0);
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_FormField, select)
{
	zend_long option;
	bool selected = true;
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_LONG(option)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(selected)
	ZEND_PARSE_PARAMETERS_END();

	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	bool choice = type == FPDF_FORMFIELD_COMBOBOX || type == FPDF_FORMFIELD_LISTBOX;
	int count = choice ? FPDFAnnot_GetOptionCount(form, a) : 0;
	bool in_range = option >= 0 && option < count;
	bool refused = false;
	bool changed = true;

	if (in_range) {
		refused = !fill_begin(f, form, a);
		if (!refused) {
			changed = FORM_SetIndexSelected(form, f->ph->page, (int) option, selected);
		}
		fill_end(f, form);
	}
	field_leave(a);
	if (!choice) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot select an option of %s %s field", field_type_article(type),
			field_type_name(type));
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
	if (refused) {
		zend_throw_exception(pdfium_ce_exception, "PDFium refused to edit the field", 0);
		RETURN_THROWS();
	}
	if (!changed) {
		zend_throw_exception(pdfium_ce_exception, "PDFium did not change the selection", 0);
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_FormField, name)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, name_get, false, false);
}

ZEND_METHOD(Pdfium_FormField, alternateName)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, alternate_name_get, true, false);
}

ZEND_METHOD(Pdfium_FormField, value)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, value_get, false, false);
}

ZEND_METHOD(Pdfium_FormField, exportValue)
{
	string_method(INTERNAL_FUNCTION_PARAM_PASSTHRU, export_value_get, true, true);
}

ZEND_METHOD(Pdfium_FormField, type)
{
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);

	field_leave(a);
	enum_case(return_value, pdfium_ce_form_field_type, field_type_cases, sizeof(field_type_cases) / sizeof(field_type_cases[0]), type);
}

ZEND_METHOD(Pdfium_FormField, isChecked)
{
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int type = FPDFAnnot_GetFormFieldType(form, a);
	bool checked = (type == FPDF_FORMFIELD_CHECKBOX || type == FPDF_FORMFIELD_RADIOBUTTON) && FPDFAnnot_IsChecked(form, a);

	field_leave(a);
	RETURN_BOOL(checked);
}

ZEND_METHOD(Pdfium_FormField, rect)
{
	pdfium_form_field *f;
	FS_RECTF r = {0};

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, NULL);

	if (!a) {
		RETURN_THROWS();
	}
	FPDFAnnot_GetRect(a, &r);
	field_leave(a);
	pdfium_rect_init(return_value, r.left, r.bottom, r.right, r.top);
}

static void control_method(INTERNAL_FUNCTION_PARAMETERS, bool count)
{
	pdfium_form_field *f;
	FPDF_FORMHANDLE form;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION a = field_enter(ZEND_THIS, &f, &form);

	if (!a) {
		RETURN_THROWS();
	}
	int n = count ? FPDFAnnot_GetFormControlCount(form, a) : FPDFAnnot_GetFormControlIndex(form, a);

	field_leave(a);
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
