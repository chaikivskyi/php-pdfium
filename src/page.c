#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
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

static bool round_trips(float value, int digits, double *parsed)
{
	char buf[32];

	*parsed = zend_strtod(zend_gcvt(value, digits, '.', 'e', buf), NULL);
	return (float) *parsed == value;
}

double pdfium_points(float value)
{
	double shortest = value, parsed;
	int low = 1, high = 10;

	if (value > -16777216.0f && value < 16777216.0f && value == (float) (int) value) {
		return value;
	}
	while (low < high) {
		int digits = (low + high) / 2;

		if (round_trips(value, digits, &parsed)) {
			shortest = parsed;
			high = digits;
		} else {
			low = digits + 1;
		}
	}
	return shortest;
}

ZEND_METHOD(Pdfium_Page, index)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(pdfium_page_from_obj(Z_OBJ_P(ZEND_THIS))->index);
}

ZEND_METHOD(Pdfium_Page, width)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	float width = FPDF_GetPageWidthF(pdfium_this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_DOUBLE(pdfium_points(width));
}

ZEND_METHOD(Pdfium_Page, height)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	float height = FPDF_GetPageHeightF(pdfium_this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_DOUBLE(pdfium_points(height));
}

ZEND_METHOD(Pdfium_Page, rotation)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int rotation = FPDFPage_GetRotation(pdfium_this_page(ZEND_THIS)->page);
	pdfium_leave();
	RETURN_LONG(rotation * 90);
}

zend_object *pdfium_page_load(pdfium_doc_handle *h, zend_long index, int *page_count)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_page);
	zend_object *obj = Z_OBJ(zv);
	pdfium_page *p = pdfium_page_from_obj(obj);
	p->index = (int) index;
	p->ph = ecalloc(1, sizeof(*p->ph));
	p->ph->h = h;
	p->ph->refs = 1;
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
	p->ph->page = page;
	return obj;
}

void pdfium_page_handle_addref(pdfium_page_handle *ph)
{
	ph->refs++;
}

static void form_view_unload(pdfium_page_handle *ph)
{
	if (ph->form_loaded) {
		FORM_OnBeforeClosePage(ph->page, ph->h->form);
		ph->form_loaded = false;
	}
}

void pdfium_page_handle_release(pdfium_page_handle *ph)
{
	if (--ph->refs) {
		return;
	}
	if (ph->page) {
		pdfium_enter_wait();
		form_view_unload(ph);
		FPDF_ClosePage(ph->page);
		pdfium_leave();
	}
	pdfium_doc_handle_release(ph->h);
	efree(ph);
}

FPDF_FORMHANDLE pdfium_page_form_view(pdfium_page_handle *ph)
{
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(ph->h);

	if (ph->form_changes != ph->h->form_changes) {
		form_view_unload(ph);
	}
	if (form && !ph->form_loaded) {
		FORM_OnAfterLoadPage(ph->page, form);
		ph->form_loaded = true;
		ph->form_changes = ph->h->form_changes;
	}
	return form;
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

	if (p->ph) {
		pdfium_page_handle_release(p->ph);
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
