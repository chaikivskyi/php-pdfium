#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include <fpdf_text.h>
#include "zend_exceptions.h"

ZEND_METHOD(Pdfium_Page, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_PageIterator, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
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
	RETURN_DOUBLE(FPDF_GetPageWidthF(this_page(ZEND_THIS)->page));
}

ZEND_METHOD(Pdfium_Page, height)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_DOUBLE(FPDF_GetPageHeightF(this_page(ZEND_THIS)->page));
}

ZEND_METHOD(Pdfium_Page, rotation)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(FPDFPage_GetRotation(this_page(ZEND_THIS)->page) * 90);
}

ZEND_METHOD(Pdfium_Page, text)
{
	FPDF_PAGE page = this_page(ZEND_THIS)->page;
	FPDF_TEXTPAGE tp;
	unsigned short *buf;
	int count, got;

	ZEND_PARSE_PARAMETERS_NONE();
	tp = FPDFText_LoadPage(page);
	if (!tp) {
		zend_throw_exception(pdfium_ce_format_exception, "Cannot load page text", 0);
		RETURN_THROWS();
	}
	count = FPDFText_CountChars(tp);
	if (count <= 0) {
		FPDFText_ClosePage(tp);
		RETURN_EMPTY_STRING();
	}
	buf = safe_emalloc((size_t) count + 1, sizeof(*buf), 0);
	got = FPDFText_GetText(tp, 0, count, buf);
	FPDFText_ClosePage(tp);
	RETVAL_STR(pdfium_utf16le_to_utf8(buf, got > 0 ? (size_t) got - 1 : 0));
	efree(buf);
}

zend_object *pdfium_page_create(pdfium_doc_handle *h, int index)
{
	FPDF_PAGE page = FPDF_LoadPage(h->doc, index);
	zval zv;

	if (!page) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot load page %d", index);
		return NULL;
	}
	object_init_ex(&zv, pdfium_ce_page);
	pdfium_page *p = pdfium_page_from_obj(Z_OBJ(zv));
	p->page = page;
	p->index = index;
	p->h = h;
	pdfium_doc_handle_addref(h);
	return Z_OBJ(zv);
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
	if (it->index >= FPDF_GetPageCount(it->h->doc)) {
		zend_throw_exception(pdfium_ce_exception, "Iterator is past the last page", 0);
		RETURN_THROWS();
	}
	if (Z_ISUNDEF(it->current)) {
		zend_object *page = pdfium_page_create(it->h, it->index);

		if (!page) {
			RETURN_THROWS();
		}
		ZVAL_OBJ(&it->current, page);
	}
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
	RETURN_BOOL(it->index < FPDF_GetPageCount(it->h->doc));
}

void pdfium_page_free_obj(zend_object *obj)
{
	pdfium_page *p = pdfium_page_from_obj(obj);

	if (p->page) {
		if (p->form_loaded) {
			FORM_OnBeforeClosePage(p->page, p->h->form);
		}
		FPDF_ClosePage(p->page);
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
