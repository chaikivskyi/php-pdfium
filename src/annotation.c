#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_annot.h>
#include "zend_exceptions.h"

static const char *const type_names[] = {
	"Unknown", "Text", "Link", "FreeText", "Line", "Square", "Circle", "Polygon", "PolyLine", "Highlight",
	"Underline", "Squiggly", "StrikeOut", "Stamp", "Caret", "Ink", "Popup", "FileAttachment", "Sound", "Movie",
	"Widget", "Screen", "PrinterMark", "TrapNet", "Watermark", "3D", "RichMedia", "XFAWidget", "Redact",
};

static const char *name_at(const char *const *names, size_t count, int index)
{
	return index >= 0 && (size_t) index < count ? names[index] : names[0];
}

static const char *type_name(FPDF_ANNOTATION_SUBTYPE subtype)
{
	return name_at(type_names, sizeof(type_names) / sizeof(type_names[0]), subtype);
}

ZEND_METHOD(Pdfium_Annotation, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

static zend_object *annotation_new(pdfium_page_handle *ph, FPDF_ANNOTATION annot)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_annotation);
	pdfium_annotation *a = pdfium_annotation_from_obj(Z_OBJ(zv));
	a->ph = ph;
	a->annot = annot;
	a->removals = ph->h->annotation_removals;
	pdfium_page_handle_addref(ph);
	return Z_OBJ(zv);
}

static pdfium_annotation *annotation_enter_at(zend_object *obj, int *index)
{
	pdfium_annotation *a = pdfium_annotation_from_obj(obj);

	if (!pdfium_enter()) {
		return NULL;
	}
	if (!a->removed && (index || a->removals != a->ph->h->annotation_removals)) {
		int at = FPDFPage_GetAnnotIndex(a->ph->page, a->annot);

		a->removals = a->ph->h->annotation_removals;
		a->removed = at < 0;
		if (index) {
			*index = at;
		}
	}
	if (a->removed) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "The annotation was removed from its page", 0);
		return NULL;
	}
	return a;
}

static pdfium_annotation *annotation_enter(zend_object *obj)
{
	return annotation_enter_at(obj, NULL);
}

static int annotation_count(FPDF_PAGE page)
{
	int count = FPDFPage_GetAnnotCount(page);

	return count > 0 ? count : 0;
}

static void annotation_index_error(int count)
{
	pdfium_index_error(1, count, "an annotation index", "the page has no annotations");
}

ZEND_METHOD(Pdfium_Page, annotationCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(pdfium_this_page(ZEND_THIS)->page);

	pdfium_leave();
	RETURN_LONG(count);
}

ZEND_METHOD(Pdfium_Page, annotations)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(ph->page);

	if (!pdfium_memory_fits((size_t) count * (sizeof(pdfium_annotation) + sizeof(Bucket)))) {
		oom = "The annotations need";
	} else {
		array_init_size(return_value, (uint32_t) count);
		for (int i = 0; i < count; i++) {
			FPDF_ANNOTATION annot = FPDFPage_GetAnnot(ph->page, i);

			if (annot) {
				zval zv;

				ZVAL_OBJ(&zv, annotation_new(ph, annot));
				add_index_zval(return_value, i, &zv);
			}
		}
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Page, annotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(ph->page);
	bool in_range = index >= 0 && index < count;
	FPDF_ANNOTATION annot = in_range ? FPDFPage_GetAnnot(ph->page, (int) index) : NULL;

	pdfium_leave();
	if (!in_range) {
		annotation_index_error(count);
		RETURN_THROWS();
	}
	if (!annot) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot read annotation %d", (int) index);
		RETURN_THROWS();
	}
	RETURN_OBJ(annotation_new(ph, annot));
}

ZEND_METHOD(Pdfium_Annotation, index)
{
	int index;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!annotation_enter_at(Z_OBJ_P(ZEND_THIS), &index)) {
		RETURN_THROWS();
	}
	pdfium_leave();
	RETURN_LONG(index);
}

ZEND_METHOD(Pdfium_Annotation, type)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE subtype = FPDFAnnot_GetSubtype(a->annot);

	pdfium_leave();
	RETURN_STRING(type_name(subtype));
}

ZEND_METHOD(Pdfium_Annotation, rect)
{
	FS_RECTF r;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetRect(a->annot, &r);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	pdfium_rect_init(return_value, r.left, r.bottom, r.right, r.top);
}

ZEND_METHOD(Pdfium_Annotation, flags)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	int flags = FPDFAnnot_GetFlags(a->annot);

	pdfium_leave();
	RETURN_LONG((uint32_t) flags);
}

void pdfium_annotation_free_obj(zend_object *obj)
{
	pdfium_annotation *a = pdfium_annotation_from_obj(obj);

	if (a->annot) {
		pdfium_enter_wait();
		FPDFPage_CloseAnnot(a->annot);
		pdfium_leave();
	}
	if (a->ph) {
		pdfium_page_handle_release(a->ph);
	}
	zend_object_std_dtor(obj);
}
