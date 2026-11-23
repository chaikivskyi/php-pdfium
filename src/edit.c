#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include <fpdf_ppo.h>
#include <fpdf_transformpage.h>
#include "zend_exceptions.h"

static void edit_failed_as(pdfium_doc_handle *h, bool order_lost, zend_class_entry *ce, const char *failure)
{
	if (!order_lost) {
		zend_throw_exception(ce, failure, 0);
	} else {
		pdfium_pages_lost(h);
		zend_throw_exception_ex(ce, 0,
			"%s: the document's page order is now undefined; load the document again", failure);
	}
}

static void edit_failed(pdfium_doc_handle *h, bool order_lost, const char *failure)
{
	edit_failed_as(h, order_lost, pdfium_ce_exception, failure);
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
		pdfium_index_error(1, before, "a page index", "the document has no pages");
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

ZEND_METHOD(Pdfium_Document, insertPages)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	zval *zsource;
	HashTable *ht = NULL;
	zend_long index = 0;
	bool index_is_null = true;
	bool remove_security = false;

	ZEND_PARSE_PARAMETERS_START(1, 4)
		Z_PARAM_OBJECT_OF_CLASS(zsource, pdfium_ce_document)
		Z_PARAM_OPTIONAL
		Z_PARAM_ARRAY_HT_OR_NULL(ht)
		Z_PARAM_LONG_OR_NULL(index, index_is_null)
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	pdfium_doc_handle *src = pdfium_document_from_obj(Z_OBJ_P(zsource))->h;
	uint32_t n = 0;
	int *indexes = NULL;

	if (ht && !(indexes = pdfium_indexes_checked(ht, 2, &n))) {
		RETURN_THROWS();
	}
	if (!pdfium_enter()) {
		efree(indexes);
		RETURN_THROWS();
	}
	int source_count = FPDF_GetPageCount(src->doc);
	int before = FPDF_GetPageCount(h->doc);

	if (index_is_null) {
		index = before;
	}
	bool no_pages = !ht && !source_count;
	pdfium_indexes_status valid = ht ? pdfium_indexes_from(ht, source_count, indexes) : PDFIUM_INDEXES_OK;
	bool placed = in_range(index, before);
	bool refused = src != h && !remove_security && pdfium_is_encrypted(src);

	if (no_pages || valid != PDFIUM_INDEXES_OK || !placed || refused) {
		pdfium_leave();
		efree(indexes);
		if (no_pages) {
			zend_argument_value_error(1, "must have pages");
		} else if (valid != PDFIUM_INDEXES_OK) {
			pdfium_throw_indexes_error(valid, source_count, 2);
		} else if (!placed) {
			zend_argument_value_error(3, "must be between 0 and %d", before);
		} else {
			zend_throw_exception(pdfium_ce_unsupported_exception, "The source document is encrypted and its pages "
				"leave its encryption; pass removeSecurity: true to insert them", 0);
		}
		RETURN_THROWS();
	}
	bool imported, no_temporary = false;

	if (src != h) {
		imported = FPDF_ImportPagesByIndex(h->doc, src->doc, indexes, n, (int) index);
	} else {
		FPDF_DOCUMENT tmp = FPDF_CreateNewDocument();

		no_temporary = !tmp;
		imported = tmp && FPDF_ImportPagesByIndex(tmp, h->doc, indexes, n, 0)
			&& FPDF_ImportPagesByIndex(h->doc, tmp, NULL, 0, (int) index);
		if (tmp) {
			FPDF_CloseDocument(tmp);
		}
	}
	int after = FPDF_GetPageCount(h->doc);
	int wanted = ht ? (int) n : source_count;
	bool unread = src->file.read_failed || h->file.read_failed;

	if (unread && !h->file.read_failed) {
		for (int k = after - before; k > 0; k--) {
			FPDFPage_Delete(h->doc, (int) index);
		}
		after = FPDF_GetPageCount(h->doc);
	}
	pdfium_leave();
	efree(indexes);
	if (no_temporary) {
		zend_throw_exception(pdfium_ce_memory_exception,
			"Cannot create the temporary document for copying pages within this document", 0);
		RETURN_THROWS();
	}
	if (unread) {
		edit_failed_as(h, after != before, pdfium_ce_format_exception, pdfium_read_failed_message);
		RETURN_THROWS();
	}
	if (imported && after - before == wanted) {
		pdfium_pages_inserted(h, (int) index, wanted);
		return;
	}
	edit_failed(h, after != before, "Cannot import pages");
	RETURN_THROWS();
}

static int compare_moved(const void *a, const void *b)
{
	int x = ((const pdfium_moved_page *) a)->index, y = ((const pdfium_moved_page *) b)->index;

	return (x > y) - (x < y);
}

ZEND_METHOD(Pdfium_Document, movePages)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	HashTable *ht;
	zend_long to;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_ARRAY_HT(ht)
		Z_PARAM_LONG(to)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t n;
	int *indexes = pdfium_indexes_checked(ht, 1, &n);

	if (!indexes) {
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits((size_t) n * sizeof(pdfium_moved_page))) {
		efree(indexes);
		pdfium_indexes_memory_error();
		RETURN_THROWS();
	}
	pdfium_moved_page *sorted = safe_emalloc(n, sizeof(*sorted), 0);

	if (!pdfium_enter()) {
		efree(indexes);
		efree(sorted);
		RETURN_THROWS();
	}
	int page_count = FPDF_GetPageCount(h->doc);
	pdfium_indexes_status valid = pdfium_indexes_from(ht, page_count, indexes);
	bool twice = false;

	if (valid == PDFIUM_INDEXES_OK) {
		for (uint32_t k = 0; k < n; k++) {
			sorted[k].index = indexes[k];
			sorted[k].order = (int) k;
		}
		qsort(sorted, n, sizeof(*sorted), compare_moved);
		for (uint32_t k = 1; k < n && !twice; k++) {
			twice = sorted[k].index == sorted[k - 1].index;
		}
	}
	if (valid != PDFIUM_INDEXES_OK || twice || !in_range(to, page_count - (int) n)) {
		pdfium_leave();
		efree(indexes);
		efree(sorted);
		if (valid != PDFIUM_INDEXES_OK) {
			pdfium_throw_indexes_error(valid, page_count, 1);
		} else if (twice) {
			zend_argument_value_error(1, "must not contain an index twice");
		} else {
			zend_argument_value_error(2, "must be between 0 and %d", page_count - (int) n);
		}
		RETURN_THROWS();
	}
	bool moved = FPDF_MovePages(h->doc, indexes, n, (int) to) && FPDF_GetPageCount(h->doc) == page_count;

	pdfium_leave();
	efree(indexes);
	if (moved) {
		pdfium_pages_moved(h, sorted, (int) n, (int) to);
	} else {
		edit_failed(h, true, "Cannot move the pages");
	}
	efree(sorted);
}
