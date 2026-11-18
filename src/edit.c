#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include <fpdf_transformpage.h>
#include "zend_exceptions.h"

static void edit_failed(pdfium_doc_handle *h, bool order_lost, const char *failure)
{
	if (!order_lost) {
		zend_throw_exception(pdfium_ce_exception, failure, 0);
	} else {
		pdfium_pages_lost(h);
		zend_throw_exception_ex(pdfium_ce_exception, 0,
			"%s: the document's page order is now undefined; load the document again", failure);
	}
}

static bool in_range(zend_long value, int max)
{
	return value >= 0 && value <= max;
}

ZEND_METHOD(Pdfium_Document, deletePage)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int before = FPDF_GetPageCount(h->doc);

	if (!in_range(index, before - 1)) {
		pdfium_leave();
		pdfium_index_error(1, before, "a page", "pages");
		RETURN_THROWS();
	}
	FPDFPage_Delete(h->doc, (int) index);
	int after = FPDF_GetPageCount(h->doc);

	pdfium_leave();
	if (after == before - 1) {
		pdfium_pages_deleted(h, (int) index);
		return;
	}
	char failure[64];

	snprintf(failure, sizeof(failure), "Cannot delete the page at index %d", (int) index);
	edit_failed(h, after != before, failure);
	RETURN_THROWS();
}

ZEND_METHOD(Pdfium_Document, addPage)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	double size[2];
	float sides[2];
	zend_long index = 0;
	bool index_is_null = true;

	ZEND_PARSE_PARAMETERS_START(2, 3)
		Z_PARAM_DOUBLE(size[0])
		Z_PARAM_DOUBLE(size[1])
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG_OR_NULL(index, index_is_null)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_page_size_args(size, sides)) {
		RETURN_THROWS();
	}
	zend_object *obj = pdfium_page_new(h);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	int before = FPDF_GetPageCount(h->doc);

	if (index_is_null) {
		index = before;
	}
	if (!in_range(index, before)) {
		pdfium_leave();
		OBJ_RELEASE(obj);
		zend_argument_value_error(3, "must be between 0 and %d", before);
		RETURN_THROWS();
	}
	FPDF_PAGE page = FPDFPage_New(h->doc, (int) index, sides[0], sides[1]);
	int after = FPDF_GetPageCount(h->doc);
	bool added = page && after == before + 1;

	if (page && !added) {
		FPDF_ClosePage(page);
	}
	if (added && (FPDF_GetPageWidthF(page) != sides[0] || FPDF_GetPageHeightF(page) != sides[1])) {
		FPDFPage_SetCropBox(page, 0, 0, sides[0], sides[1]);
	}
	pdfium_leave();
	if (!added) {
		OBJ_RELEASE(obj);
		edit_failed(h, page || after != before, "Cannot add a page");
		RETURN_THROWS();
	}
	pdfium_pages_inserted(h, (int) index, 1);
	RETURN_OBJ(pdfium_page_attach(obj, page, (int) index));
}
