#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_doc.h>
#include "zend_exceptions.h"

#define ACTION_BYTES 1024

enum { DEST_PAGE_INDEX, DEST_VIEW, DEST_PARAMS, DEST_X, DEST_Y, DEST_ZOOM };
enum { ACTION_TYPE, ACTION_URI, ACTION_FILE_PATH, ACTION_DESTINATION };

ZEND_METHOD(Pdfium_Destination, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Action, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_navigation_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_destination, "pageIndex view params x y zoom")
		&& pdfium_slots_named(pdfium_ce_action, "type uri filePath destination");
}

static void optional_points(zval *zv, FPDF_BOOL has, FS_FLOAT value)
{
	if (has) {
		ZVAL_DOUBLE(zv, pdfium_points(value));
	} else {
		ZVAL_NULL(zv);
	}
}

void pdfium_destination_set(zval *zv, FPDF_DOCUMENT doc, FPDF_DEST dest)
{
	static const char *views[] = {NULL, "XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH", "FitBV"};
	int index = dest ? FPDFDest_GetDestPageIndex(doc, dest) : -1;

	if (index < 0) {
		ZVAL_NULL(zv);
		return;
	}
	unsigned long count = 0;
	FS_FLOAT params[4] = {0};
	unsigned long view = FPDFDest_GetView(dest, &count, params);
	FPDF_BOOL has_x = false, has_y = false, has_zoom = false;
	FS_FLOAT x = 0, y = 0, zoom = 0;

	if (view >= sizeof(views) / sizeof(views[0])) {
		view = 0;
	}
	if (view != PDFDEST_VIEW_XYZ || !FPDFDest_GetLocationInPage(dest, &has_x, &has_y, &has_zoom, &x, &y, &zoom)) {
		has_x = has_y = has_zoom = false;
	}
	object_init_ex(zv, pdfium_ce_destination);
	zend_object *obj = Z_OBJ_P(zv);
	zval *zp = pdfium_slot(obj, DEST_PARAMS);

	ZVAL_LONG(pdfium_slot(obj, DEST_PAGE_INDEX), index);
	if (views[view]) {
		ZVAL_STRING(pdfium_slot(obj, DEST_VIEW), views[view]);
	} else {
		ZVAL_NULL(pdfium_slot(obj, DEST_VIEW));
	}
	array_init_size(zp, 4);
	for (unsigned long i = 0; i < count && i < 4; i++) {
		add_next_index_double(zp, pdfium_points(params[i]));
	}
	optional_points(pdfium_slot(obj, DEST_X), has_x, x);
	optional_points(pdfium_slot(obj, DEST_Y), has_y, y);
	optional_points(pdfium_slot(obj, DEST_ZOOM), has_zoom, zoom);
}

static bool has_file_path(unsigned long type)
{
	return type == PDFACTION_REMOTEGOTO || type == PDFACTION_LAUNCH || type == PDFACTION_EMBEDDEDGOTO;
}

size_t pdfium_action_bytes(FPDF_DOCUMENT doc, FPDF_ACTION action)
{
	if (!action) {
		return 0;
	}
	unsigned long type = FPDFAction_GetType(action);

	if (type == PDFACTION_URI) {
		return FPDFAction_GetURIPath(doc, action, NULL, 0);
	}
	return has_file_path(type) ? FPDFAction_GetFilePath(action, NULL, 0) : 0;
}

static void bytes_set(zval *zv, unsigned long length, bool uri, FPDF_DOCUMENT doc, FPDF_ACTION action)
{
	if (!length) {
		ZVAL_NULL(zv);
		return;
	}
	zend_string *s = zend_string_alloc(length, 0);

	if (uri) {
		FPDFAction_GetURIPath(doc, action, ZSTR_VAL(s), length);
	} else {
		FPDFAction_GetFilePath(action, ZSTR_VAL(s), length);
	}
	ZSTR_LEN(s) = length - 1;
	ZSTR_VAL(s)[ZSTR_LEN(s)] = '\0';
	ZVAL_STR(zv, s);
}

void pdfium_action_set(zval *zv, FPDF_DOCUMENT doc, FPDF_ACTION action)
{
	static const char *types[] = {"Unsupported", "GoTo", "GoToR", "URI", "Launch", "GoToE"};

	if (!action) {
		ZVAL_NULL(zv);
		return;
	}
	unsigned long type = FPDFAction_GetType(action);

	if (type >= sizeof(types) / sizeof(types[0])) {
		type = PDFACTION_UNSUPPORTED;
	}
	object_init_ex(zv, pdfium_ce_action);
	zend_object *obj = Z_OBJ_P(zv);

	ZVAL_STRING(pdfium_slot(obj, ACTION_TYPE), types[type]);
	bytes_set(pdfium_slot(obj, ACTION_URI), type == PDFACTION_URI ? FPDFAction_GetURIPath(doc, action, NULL, 0) : 0,
		true, doc, action);
	bytes_set(pdfium_slot(obj, ACTION_FILE_PATH), has_file_path(type) ? FPDFAction_GetFilePath(action, NULL, 0) : 0,
		false, doc, action);
	if (type == PDFACTION_GOTO) {
		pdfium_destination_set(pdfium_slot(obj, ACTION_DESTINATION), doc, FPDFAction_GetDest(doc, action));
	} else {
		ZVAL_NULL(pdfium_slot(obj, ACTION_DESTINATION));
	}
}

void pdfium_entry_targets(zval *dest_zv, zval *action_zv, FPDF_DOCUMENT doc, FPDF_DEST own_dest, FPDF_ACTION action)
{
	pdfium_action_set(action_zv, doc, action);
	if (!action) {
		pdfium_destination_set(dest_zv, doc, own_dest);
	} else if (Z_TYPE_P(action_zv) == IS_OBJECT && FPDFAction_GetType(action) == PDFACTION_GOTO) {
		ZVAL_COPY(dest_zv, pdfium_slot(Z_OBJ_P(action_zv), ACTION_DESTINATION));
	} else {
		ZVAL_NULL(dest_zv);
	}
}

static void page_action(INTERNAL_FUNCTION_PARAMETERS, int which)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	FPDF_ACTION action = FPDF_GetPageAAction(ph->page, which);

	if (pdfium_memory_fits(ACTION_BYTES + pdfium_action_bytes(ph->h->doc, action))) {
		pdfium_action_set(return_value, ph->h->doc, action);
	} else {
		oom = "The action needs";
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Page, openAction)
{
	page_action(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFPAGE_AACTION_OPEN);
}

ZEND_METHOD(Pdfium_Page, closeAction)
{
	page_action(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFPAGE_AACTION_CLOSE);
}
