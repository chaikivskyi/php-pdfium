#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
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

	if (index < 0 || index >= before) {
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
