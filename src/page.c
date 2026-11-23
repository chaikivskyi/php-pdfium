#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include <fpdf_transformpage.h>
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

bool pdfium_page_live(pdfium_page_handle *ph)
{
	if (ph->state == PDFIUM_PAGE_DELETED) {
		zend_throw_exception(pdfium_ce_exception, "The page was deleted from its document", 0);
		return false;
	}
	if (ph->state == PDFIUM_PAGE_LOST) {
		zend_throw_exception(pdfium_ce_exception,
			"The page's position is unknown after a failed edit of its document; load the document again", 0);
		return false;
	}
	return true;
}

static bool placed_at(const pdfium_page_handle *ph, int index)
{
	return ph->state == PDFIUM_PAGE_PLACED && ph->index == index;
}

ZEND_METHOD(Pdfium_Page, index)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_live(ph)) {
		RETURN_THROWS();
	}
	RETURN_LONG(ph->index);
}

ZEND_METHOD(Pdfium_Page, width)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	float width = FPDF_GetPageWidthF(ph->page);
	pdfium_leave();
	RETURN_DOUBLE(pdfium_points(width));
}

ZEND_METHOD(Pdfium_Page, height)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	float height = FPDF_GetPageHeightF(ph->page);
	pdfium_leave();
	RETURN_DOUBLE(pdfium_points(height));
}

ZEND_METHOD(Pdfium_Page, rotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	int rotation = FPDFPage_GetRotation(ph->page);
	pdfium_leave();
	RETURN_LONG(rotation * 90);
}

typedef struct {
	FPDF_BOOL (*get)(FPDF_PAGE, float *, float *, float *, float *);
	void (*set)(FPDF_PAGE, float, float, float, float);
} box_kind;

static const box_kind media_kind = {FPDFPage_GetMediaBox, FPDFPage_SetMediaBox};
static const box_kind crop_kind = {FPDFPage_GetCropBox, FPDFPage_SetCropBox};
static const box_kind bleed_kind = {FPDFPage_GetBleedBox, FPDFPage_SetBleedBox};
static const box_kind trim_kind = {FPDFPage_GetTrimBox, FPDFPage_SetTrimBox};
static const box_kind art_kind = {FPDFPage_GetArtBox, FPDFPage_SetArtBox};

static void page_box_get(INTERNAL_FUNCTION_PARAMETERS, const box_kind *kind)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	float left, bottom, right, top;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_page_handle_enter(ph)) {
		RETURN_THROWS();
	}
	bool found = kind->get(ph->page, &left, &bottom, &right, &top);

	pdfium_leave();
	if (!found) {
		RETURN_NULL();
	}
	pdfium_rect_init(return_value, left, bottom, right, top);
}

static const char *box_fault(const zval *zr, float *box)
{
	double sides[RECT_SLOTS];
	bool finite = true;

	if (!pdfium_rect_sides(zr, sides)) {
		return "every side set";
	}
	for (int k = 0; k < RECT_SLOTS; k++) {
		finite &= zend_finite(sides[k]) != 0;
		box[k] = pdfium_float_side(sides[k]);
	}
	if (finite && box[RECT_LEFT] < box[RECT_RIGHT] && box[RECT_BOTTOM] < box[RECT_TOP]) {
		return NULL;
	}
	return "finite sides, with left less than right and bottom less than top";
}

static void page_box_set(INTERNAL_FUNCTION_PARAMETERS, const box_kind *kind)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zval *zr;
	float box[RECT_SLOTS];

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(zr, pdfium_ce_rect)
	ZEND_PARSE_PARAMETERS_END();
	if (!pdfium_page_usable(ph)) {
		RETURN_THROWS();
	}
	const char *fault = box_fault(zr, box);

	if (fault) {
		zend_argument_value_error(1, "must have %s", fault);
		RETURN_THROWS();
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	for (pdfium_page_handle *other = ph->h->pages; other; other = other->next) {
		if (placed_at(other, ph->index)) {
			kind->set(other->page, box[RECT_LEFT], box[RECT_BOTTOM], box[RECT_RIGHT], box[RECT_TOP]);
		}
	}
	pdfium_leave();
}

ZEND_METHOD(Pdfium_Page, setRotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zend_long degrees;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(degrees)
	ZEND_PARSE_PARAMETERS_END();
	if (!pdfium_page_usable(ph)) {
		RETURN_THROWS();
	}
	if (degrees < 0 || degrees > 270 || degrees % 90) {
		zend_argument_value_error(1, "must be 0, 90, 180 or 270");
		RETURN_THROWS();
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	for (pdfium_page_handle *other = ph->h->pages; other; other = other->next) {
		if (placed_at(other, ph->index)) {
			FPDFPage_SetRotation(other->page, (int) (degrees / 90));
		}
	}
	pdfium_leave();
}

ZEND_METHOD(Pdfium_Page, mediaBox) { page_box_get(INTERNAL_FUNCTION_PARAM_PASSTHRU, &media_kind); }
ZEND_METHOD(Pdfium_Page, cropBox) { page_box_get(INTERNAL_FUNCTION_PARAM_PASSTHRU, &crop_kind); }
ZEND_METHOD(Pdfium_Page, bleedBox) { page_box_get(INTERNAL_FUNCTION_PARAM_PASSTHRU, &bleed_kind); }
ZEND_METHOD(Pdfium_Page, trimBox) { page_box_get(INTERNAL_FUNCTION_PARAM_PASSTHRU, &trim_kind); }
ZEND_METHOD(Pdfium_Page, artBox) { page_box_get(INTERNAL_FUNCTION_PARAM_PASSTHRU, &art_kind); }
ZEND_METHOD(Pdfium_Page, setMediaBox) { page_box_set(INTERNAL_FUNCTION_PARAM_PASSTHRU, &media_kind); }
ZEND_METHOD(Pdfium_Page, setCropBox) { page_box_set(INTERNAL_FUNCTION_PARAM_PASSTHRU, &crop_kind); }
ZEND_METHOD(Pdfium_Page, setBleedBox) { page_box_set(INTERNAL_FUNCTION_PARAM_PASSTHRU, &bleed_kind); }
ZEND_METHOD(Pdfium_Page, setTrimBox) { page_box_set(INTERNAL_FUNCTION_PARAM_PASSTHRU, &trim_kind); }
ZEND_METHOD(Pdfium_Page, setArtBox) { page_box_set(INTERNAL_FUNCTION_PARAM_PASSTHRU, &art_kind); }

zend_object *pdfium_page_new(pdfium_doc_handle *h)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_page);
	pdfium_page_handle *ph = ecalloc(1, sizeof(*ph));

	ph->h = h;
	ph->refs = 1;
	pdfium_doc_handle_addref(h);
	pdfium_page_from_obj(Z_OBJ(zv))->ph = ph;
	return Z_OBJ(zv);
}

static void handle_link(pdfium_page_handle *ph, FPDF_PAGE page, int index)
{
	pdfium_doc_handle *h = ph->h;

	ph->page = page;
	ph->index = index;
	ph->next = h->pages;
	if (h->pages) {
		h->pages->prev = ph;
	}
	h->pages = ph;
}

zend_object *pdfium_page_attach(zend_object *obj, FPDF_PAGE page, int index)
{
	handle_link(pdfium_page_from_obj(obj)->ph, page, index);
	return obj;
}

zend_object *pdfium_page_load(pdfium_doc_handle *h, zend_long index, int *page_count)
{
	zend_object *obj = pdfium_page_new(h);

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
	return pdfium_page_attach(obj, page, (int) index);
}

void pdfium_pages_deleted(pdfium_doc_handle *h, int index)
{
	for (pdfium_page_handle *ph = h->pages; ph; ph = ph->next) {
		if (ph->state != PDFIUM_PAGE_PLACED) {
			continue;
		}
		if (ph->index == index) {
			ph->state = PDFIUM_PAGE_DELETED;
		} else if (ph->index > index) {
			ph->index--;
		}
	}
}

void pdfium_pages_inserted(pdfium_doc_handle *h, int index, int count)
{
	for (pdfium_page_handle *ph = h->pages; ph; ph = ph->next) {
		if (ph->state == PDFIUM_PAGE_PLACED && ph->index >= index) {
			ph->index += count;
		}
	}
}

void pdfium_pages_moved(pdfium_doc_handle *h, const pdfium_moved_page *sorted, int count, int to)
{
	for (pdfium_page_handle *ph = h->pages; ph; ph = ph->next) {
		if (ph->state != PDFIUM_PAGE_PLACED) {
			continue;
		}
		int lo = 0, hi = count;

		while (lo < hi) {
			int mid = lo + (hi - lo) / 2;

			if (sorted[mid].index < ph->index) {
				lo = mid + 1;
			} else {
				hi = mid;
			}
		}
		if (lo < count && sorted[lo].index == ph->index) {
			ph->index = to + sorted[lo].order;
		} else {
			int r = ph->index - lo;

			ph->index = r < to ? r : r + count;
		}
	}
}

void pdfium_pages_lost(pdfium_doc_handle *h)
{
	for (pdfium_page_handle *ph = h->pages; ph; ph = ph->next) {
		if (ph->state == PDFIUM_PAGE_PLACED) {
			ph->state = PDFIUM_PAGE_LOST;
		}
	}
}

void pdfium_page_handle_addref(pdfium_page_handle *ph)
{
	ph->refs++;
}

static void form_unload(pdfium_page_handle *ph)
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
		form_unload(ph);
		FPDF_ClosePage(ph->page);
		pdfium_leave();
		if (ph->prev) {
			ph->prev->next = ph->next;
		} else {
			ph->h->pages = ph->next;
		}
		if (ph->next) {
			ph->next->prev = ph->prev;
		}
	}
	pdfium_doc_handle_release(ph->h);
	efree(ph);
}

FPDF_FORMHANDLE pdfium_page_form(pdfium_page_handle *ph)
{
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(ph->h);

	if (ph->form_changes != ph->h->form_changes) {
		form_unload(ph);
	}
	if (form && !ph->form_loaded) {
		FORM_OnAfterLoadPage(ph->page, form);
		ph->form_loaded = true;
		ph->form_changes = ph->h->form_changes;
	}
	return form;
}

void pdfium_page_swap_handle(pdfium_page *p, FPDF_PAGE fresh)
{
	pdfium_page_handle *old = p->ph;
	pdfium_page_handle *ph = ecalloc(1, sizeof(*ph));

	ph->h = old->h;
	ph->refs = 1;
	ph->index = old->index;
	pdfium_doc_handle_addref(old->h);
	if (fresh) {
		handle_link(ph, fresh, old->index);
	}
	p->ph = ph;
	pdfium_page_handle_release(old);
}

bool pdfium_page_usable(pdfium_page_handle *ph)
{
	if (!pdfium_page_live(ph)) {
		return false;
	}
	if (!ph->page) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot load page %d", ph->index);
		return false;
	}
	return true;
}

bool pdfium_page_handle_enter(pdfium_page_handle *ph)
{
	return pdfium_page_usable(ph) && pdfium_enter();
}

bool pdfium_page_enter(zval *self)
{
	return pdfium_page_handle_enter(pdfium_this_page(self));
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
		pdfium_page_handle *ph = pdfium_page_from_obj(Z_OBJ(it->current))->ph;

		if (placed_at(ph, it->index)) {
			RETURN_COPY(&it->current);
		}
		iterator_drop_current(it);
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
