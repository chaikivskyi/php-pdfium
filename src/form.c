#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_annot.h>
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
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(ph->h);

	if (!form) {
		array_init(return_value);
		pdfium_leave();
		return;
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
	if (!pdfium_enter()) {
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

static int field_flags(FPDF_FORMHANDLE form, FPDF_ANNOTATION a)
{
	return FPDFAnnot_GetFormFieldFlags(form, a);
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
	int bits = field_flags(form, a);

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
