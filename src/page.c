#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include <fpdf_text.h>
#include "zend_exceptions.h"
#include "zend_strtod.h"

ZEND_METHOD(Pdfium_Page, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_PageIterator, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

static double points(float value)
{
	char buf[32];

	for (int digits = 1; digits <= 9; digits++) {
		double parsed = zend_strtod(zend_gcvt(value, digits, '.', 'e', buf), NULL);

		if ((float) parsed == value) {
			return parsed;
		}
	}
	return value;
}

static pdfium_page *this_page(zval *self)
{
	return pdfium_page_from_obj(Z_OBJ_P(self));
}

ZEND_METHOD(Pdfium_Page, index)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(this_page(ZEND_THIS)->index);
}

ZEND_METHOD(Pdfium_Page, width)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	float width = FPDF_GetPageWidthF(this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_DOUBLE(points(width));
}

ZEND_METHOD(Pdfium_Page, height)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	float height = FPDF_GetPageHeightF(this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_DOUBLE(points(height));
}

ZEND_METHOD(Pdfium_Page, rotation)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int rotation = FPDFPage_GetRotation(this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_LONG(rotation * 90);
}

ZEND_METHOD(Pdfium_Page, text)
{
	FPDF_PAGE page = this_page(ZEND_THIS)->page;
	FPDF_TEXTPAGE tp;
	unsigned short *buf;
	int count, got;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	tp = FPDFText_LoadPage(page);
	if (!tp) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_format_exception, "Cannot load page text", 0);
		RETURN_THROWS();
	}
	count = FPDFText_CountChars(tp);
	if (count <= 0) {
		FPDFText_ClosePage(tp);
		pdfium_leave();
		RETURN_EMPTY_STRING();
	}
	if (!pdfium_memory_fits((size_t) count * 8)) {
		FPDFText_ClosePage(tp);
		pdfium_leave();
		zend_throw_exception(pdfium_ce_memory_exception, "Page text needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	buf = safe_emalloc((size_t) count + 1, sizeof(*buf), 0);
	got = FPDFText_GetText(tp, 0, count, buf);
	FPDFText_ClosePage(tp);
	pdfium_leave();
	RETVAL_STR(pdfium_utf16le_to_utf8(buf, got > 0 ? (size_t) got - 1 : 0));
	efree(buf);
}

zend_object *pdfium_page_load(pdfium_doc_handle *h, zend_long index, int *page_count)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_page);
	zend_object *obj = Z_OBJ(zv);
	pdfium_page *p = pdfium_page_from_obj(obj);
	p->index = (int) index;
	p->h = h;
	pdfium_doc_handle_addref(h);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		return NULL;
	}
	*page_count = FPDF_GetPageCount(h->doc);
	bool in_range = index >= 0 && index < *page_count;
	FPDF_PAGE page = in_range ? FPDF_LoadPage(h->doc, (int) index) : NULL;

	pdfium_leave();
	if (!page) {
		OBJ_RELEASE(obj);
		if (in_range) {
			zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot load page %d", (int) index);
		}
		return NULL;
	}
	p->page = page;
	return obj;
}

zend_object *pdfium_page_iterator_create(pdfium_doc_handle *h)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_page_iterator);
	pdfium_page_iterator_from_obj(Z_OBJ(zv))->h = h;
	pdfium_doc_handle_addref(h);
	return Z_OBJ(zv);
}

static pdfium_page_iterator *this_iterator(zval *self)
{
	return pdfium_page_iterator_from_obj(Z_OBJ_P(self));
}

static void iterator_drop_current(pdfium_page_iterator *it)
{
	zval_ptr_dtor(&it->current);
	ZVAL_UNDEF(&it->current);
}

ZEND_METHOD(Pdfium_PageIterator, current)
{
	pdfium_page_iterator *it = this_iterator(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!Z_ISUNDEF(it->current)) {
		RETURN_COPY(&it->current);
	}
	int page_count;
	zend_object *obj = pdfium_page_load(it->h, it->index, &page_count);

	if (!obj) {
		if (!EG(exception)) {
			zend_throw_exception(pdfium_ce_exception, "Iterator is past the last page", 0);
		}
		RETURN_THROWS();
	}
	ZVAL_OBJ(&it->current, obj);
	RETURN_COPY(&it->current);
}

ZEND_METHOD(Pdfium_PageIterator, key)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(this_iterator(ZEND_THIS)->index);
}

ZEND_METHOD(Pdfium_PageIterator, next)
{
	pdfium_page_iterator *it = this_iterator(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	iterator_drop_current(it);
	it->index++;
}

ZEND_METHOD(Pdfium_PageIterator, rewind)
{
	pdfium_page_iterator *it = this_iterator(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	iterator_drop_current(it);
	it->index = 0;
}

ZEND_METHOD(Pdfium_PageIterator, valid)
{
	pdfium_page_iterator *it = this_iterator(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	bool valid = it->index < FPDF_GetPageCount(it->h->doc);
	pdfium_leave();
	RETURN_BOOL(valid);
}

void pdfium_page_free_obj(zend_object *obj)
{
	pdfium_page *p = pdfium_page_from_obj(obj);

	if (p->page) {
		pdfium_enter_wait();
		if (p->form_loaded) {
			FORM_OnBeforeClosePage(p->page, p->h->form);
		}
		FPDF_ClosePage(p->page);
		pdfium_leave();
	}
	if (p->h) {
		pdfium_doc_handle_release(p->h);
	}
	zend_object_std_dtor(obj);
}

void pdfium_page_iterator_free_obj(zend_object *obj)
{
	pdfium_page_iterator *it = pdfium_page_iterator_from_obj(obj);

	zval_ptr_dtor(&it->current);
	if (it->h) {
		pdfium_doc_handle_release(it->h);
	}
	zend_object_std_dtor(obj);
}
