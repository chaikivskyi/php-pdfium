#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <math.h>
#include <fpdf_doc.h>
#include "zend_exceptions.h"

#define ACTION_BYTES 1024
#define LINK_BYTES 2048
#define QUAD_BYTES 256
#define BOOKMARK_BYTES 2048
#define BOOKMARK_DEPTH 100

enum { DEST_PAGE_INDEX, DEST_VIEW, DEST_PARAMS, DEST_X, DEST_Y, DEST_ZOOM };
enum { ACTION_TYPE, ACTION_URI, ACTION_FILE_PATH, ACTION_DESTINATION };
enum { LINK_RECT, LINK_QUAD_POINTS, LINK_DESTINATION, LINK_ACTION };
enum {
	BOOKMARK_TITLE, BOOKMARK_OPEN, BOOKMARK_COLOR, BOOKMARK_ITALIC, BOOKMARK_BOLD, BOOKMARK_DESTINATION, BOOKMARK_ACTION,
	BOOKMARK_CHILDREN,
};
enum { COLOR_RED, COLOR_GREEN, COLOR_BLUE, COLOR_ALPHA };

typedef struct {
	FPDF_BOOKMARK next;
	zval *into;
} bookmark_level;

ZEND_METHOD(Pdfium_Destination, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Action, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Link, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Quad, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Bookmark, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_navigation_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_destination, "pageIndex view params x y zoom")
		&& pdfium_slots_named(pdfium_ce_action, "type uri filePath destination")
		&& pdfium_slots_named(pdfium_ce_link, "rect quadPoints destination action")
		&& pdfium_slots_named(pdfium_ce_quad, "x1 y1 x2 y2 x3 y3 x4 y4")
		&& pdfium_slots_named(pdfium_ce_bookmark, "title open color italic bold destination action children");
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
	if (length <= 1) {
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

static bool link_fits(FPDF_DOCUMENT doc, FPDF_LINK link, uint32_t held)
{
	size_t quads = (size_t) MAX(FPDFLink_CountQuadPoints(link), 0);

	return pdfium_memory_fits(LINK_BYTES + quads * QUAD_BYTES + 2 * (size_t) held * sizeof(zval)
		+ pdfium_action_bytes(doc, FPDFLink_GetAction(link)));
}

static void quad_points_set(zval *zv, FPDF_LINK link)
{
	int count = MAX(FPDFLink_CountQuadPoints(link), 0);

	array_init_size(zv, (uint32_t) count);
	for (int i = 0; i < count; i++) {
		FS_QUADPOINTSF q;
		zval zq;

		if (!FPDFLink_GetQuadPoints(link, i, &q)) {
			continue;
		}
		float corners[] = {q.x1, q.y1, q.x2, q.y2, q.x3, q.y3, q.x4, q.y4};

		object_init_ex(&zq, pdfium_ce_quad);
		for (int c = 0; c < 8; c++) {
			ZVAL_DOUBLE(pdfium_slot(Z_OBJ(zq), c), pdfium_points(corners[c]));
		}
		add_next_index_zval(zv, &zq);
	}
}

static void link_set(zval *zv, FPDF_DOCUMENT doc, FPDF_LINK link)
{
	FS_RECTF r = {0};

	FPDFLink_GetAnnotRect(link, &r);
	object_init_ex(zv, pdfium_ce_link);
	zend_object *obj = Z_OBJ_P(zv);

	pdfium_rect_init(pdfium_slot(obj, LINK_RECT), r.left, r.bottom, r.right, r.top);
	quad_points_set(pdfium_slot(obj, LINK_QUAD_POINTS), link);
	pdfium_entry_targets(pdfium_slot(obj, LINK_DESTINATION), pdfium_slot(obj, LINK_ACTION), doc,
		FPDFLink_GetDest(doc, link), FPDFLink_GetAction(link));
}

ZEND_METHOD(Pdfium_Page, links)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	const char *oom = NULL;
	FPDF_LINK link;
	int pos = 0;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	array_init(return_value);
	while (FPDFLink_Enumerate(ph->page, &pos, &link)) {
		zval zl;

		if (!link_fits(ph->h->doc, link, zend_hash_num_elements(Z_ARRVAL_P(return_value)))) {
			oom = "Links need";
			break;
		}
		link_set(&zl, ph->h->doc, link);
		add_next_index_zval(return_value, &zl);
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Page, linkAt)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	const char *oom = NULL;
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
	FPDF_LINK link = FPDFLink_GetLinkAtPoint(ph->page, x, y);

	if (link && link_fits(ph->h->doc, link, 0)) {
		link_set(return_value, ph->h->doc, link);
	} else if (link) {
		oom = "Links need";
	}
	pdfium_leave_or_throw(return_value, oom);
}

static zend_long color_channel(float value)
{
	if (!(value > 0)) {
		return 0;
	}
	return value >= 1 ? 255 : (zend_long) lroundf(value * 255);
}

static void bookmark_color_set(zval *zv, FPDF_BOOKMARK bm)
{
	float rgb[3];

	if (!FPDFBookmark_GetColor(bm, &rgb[0], &rgb[1], &rgb[2])) {
		ZVAL_NULL(zv);
		return;
	}
	object_init_ex(zv, pdfium_ce_color);
	for (int i = 0; i < 3; i++) {
		ZVAL_LONG(pdfium_slot(Z_OBJ_P(zv), COLOR_RED + i), color_channel(rgb[i]));
	}
	ZVAL_LONG(pdfium_slot(Z_OBJ_P(zv), COLOR_ALPHA), 255);
}

static void bookmark_title_set(zval *zv, FPDF_BOOKMARK bm, unsigned long bytes)
{
	if (bytes <= 2) {
		ZVAL_EMPTY_STRING(zv);
		return;
	}
	unsigned short *units = emalloc(bytes);

	FPDFBookmark_GetTitle(bm, units, bytes);
	ZVAL_STR(zv, pdfium_utf16le_to_utf8(units, bytes / 2 - 1));
	efree(units);
}

static zend_object *bookmark_append(zval *into, FPDF_DOCUMENT doc, FPDF_BOOKMARK bm, unsigned long title_bytes)
{
	zval zb;
	int style = FPDFBookmark_GetStyle(bm);

	object_init_ex(&zb, pdfium_ce_bookmark);
	zend_object *obj = Z_OBJ(zb);

	bookmark_title_set(pdfium_slot(obj, BOOKMARK_TITLE), bm, title_bytes);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_OPEN), FPDFBookmark_GetCount(bm) > 0);
	bookmark_color_set(pdfium_slot(obj, BOOKMARK_COLOR), bm);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_ITALIC), style & 1);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_BOLD), style & 2);
	pdfium_entry_targets(pdfium_slot(obj, BOOKMARK_DESTINATION), pdfium_slot(obj, BOOKMARK_ACTION), doc,
		FPDFBookmark_GetDest(doc, bm), FPDFBookmark_GetAction(bm));
	array_init(pdfium_slot(obj, BOOKMARK_CHILDREN));
	add_next_index_zval(into, &zb);
	return obj;
}

static const char *bookmarks_read(FPDF_DOCUMENT doc, zval *return_value, HashTable *visited, bookmark_level *levels)
{
	int depth = 1;

	levels[0].next = FPDFBookmark_GetFirstChild(doc, NULL);
	levels[0].into = return_value;
	while (depth) {
		bookmark_level *level = &levels[depth - 1];
		FPDF_BOOKMARK bm = level->next;

		if (!bm || zend_hash_index_exists(visited, (zend_ulong) (uintptr_t) bm)) {
			depth--;
			continue;
		}
		unsigned long title_bytes = FPDFBookmark_GetTitle(bm, NULL, 0);
		size_t held = zend_hash_num_elements(visited);

		if (!pdfium_memory_fits(BOOKMARK_BYTES + (size_t) title_bytes * 2 + 2 * held * sizeof(zval)
				+ pdfium_action_bytes(doc, FPDFBookmark_GetAction(bm)))) {
			return "Bookmarks need";
		}
		zend_hash_index_add_empty_element(visited, (zend_ulong) (uintptr_t) bm);
		zend_object *obj = bookmark_append(level->into, doc, bm, title_bytes);

		level->next = FPDFBookmark_GetNextSibling(doc, bm);
		if (depth < BOOKMARK_DEPTH) {
			levels[depth].next = FPDFBookmark_GetFirstChild(doc, bm);
			levels[depth].into = pdfium_slot(obj, BOOKMARK_CHILDREN);
			depth++;
		}
	}
	return NULL;
}

ZEND_METHOD(Pdfium_Document, bookmarks)
{
	pdfium_doc_handle *h = pdfium_document_from_obj(Z_OBJ_P(ZEND_THIS))->h;
	bookmark_level levels[BOOKMARK_DEPTH];
	HashTable visited;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	zend_hash_init(&visited, 8, NULL, NULL, 0);
	array_init(return_value);

	const char *oom = bookmarks_read(h->doc, return_value, &visited, levels);

	zend_hash_destroy(&visited);
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Document, pageLabel)
{
	pdfium_doc_handle *h = pdfium_document_from_obj(Z_OBJ_P(ZEND_THIS))->h;
	unsigned short *units = NULL;
	unsigned long bytes = 0;
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = FPDF_GetPageCount(h->doc);

	if (index < 0 || index >= count) {
		pdfium_leave();
		if (!count) {
			zend_argument_value_error(1, "must be a page index, but the document has no pages");
		} else {
			zend_argument_value_error(1, "must be between 0 and %d", count - 1);
		}
		RETURN_THROWS();
	}
	bytes = FPDF_GetPageLabel(h->doc, (int) index, NULL, 0);
	if (bytes > 2 && !pdfium_memory_fits((size_t) bytes * 3)) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_memory_exception, "The page label needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	if (bytes > 2) {
		units = emalloc(bytes);
		FPDF_GetPageLabel(h->doc, (int) index, units, bytes);
	}
	pdfium_leave();
	if (!units) {
		RETURN_NULL();
	}
	size_t n = 0;

	while (n < bytes / 2 && units[n]) {
		n++;
	}
	if (n) {
		RETVAL_STR(pdfium_utf16le_to_utf8(units, n));
	}
	efree(units);
}

static void file_id(INTERNAL_FUNCTION_PARAMETERS, FPDF_FILEIDTYPE type)
{
	pdfium_doc_handle *h = pdfium_document_from_obj(Z_OBJ_P(ZEND_THIS))->h;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	unsigned long bytes = FPDF_GetFileIdentifier(h->doc, type, NULL, 0);

	if (bytes && !pdfium_memory_fits(bytes)) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_memory_exception, "The file identifier needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	if (bytes) {
		zend_string *s = zend_string_alloc(bytes, 0);

		FPDF_GetFileIdentifier(h->doc, type, ZSTR_VAL(s), bytes);
		ZSTR_LEN(s) = bytes - 1;
		ZSTR_VAL(s)[ZSTR_LEN(s)] = '\0';
		RETVAL_STR(s);
	}
	pdfium_leave();
}

ZEND_METHOD(Pdfium_Document, permanentId)
{
	file_id(INTERNAL_FUNCTION_PARAM_PASSTHRU, FILEIDTYPE_PERMANENT);
}

ZEND_METHOD(Pdfium_Document, changingId)
{
	file_id(INTERNAL_FUNCTION_PARAM_PASSTHRU, FILEIDTYPE_CHANGING);
}
