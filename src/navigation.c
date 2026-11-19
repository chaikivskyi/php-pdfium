#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <math.h>
#include <fpdf_doc.h>

#define ACTION_BYTES 1024
#define LINK_BYTES 2048
#define BOOKMARK_BYTES 2048
#define BOOKMARK_DEPTH 100

enum {
	BOOKMARK_TITLE, BOOKMARK_OPEN, BOOKMARK_COLOR, BOOKMARK_ITALIC, BOOKMARK_BOLD, BOOKMARK_DESTINATION, BOOKMARK_ACTION,
	BOOKMARK_CHILDREN,
};
enum { LINK_RECT, LINK_QUAD_POINTS, LINK_DESTINATION, LINK_ACTION };
enum { DEST_PAGE_INDEX, DEST_VIEW, DEST_PARAMS, DEST_X, DEST_Y, DEST_ZOOM };
enum { ACTION_TYPE, ACTION_URI, ACTION_FILE_PATH, ACTION_DESTINATION };
enum { STYLE_ITALIC = 1, STYLE_BOLD = 2 };

typedef struct {
	FPDF_BOOKMARK next;
	zval *into;
} bookmark_level;

typedef struct {
	FPDF_DOCUMENT doc;
	FPDF_ACTION action;
	unsigned long type;
	unsigned long bytes;
} action_info;

typedef struct {
	int quads;
	action_info action;
} link_info;

ZEND_METHOD(Pdfium_Bookmark, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Link, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Quad, __construct)
{
	static const char *const names[] = {"x1", "y1", "x2", "y2", "x3", "y3", "x4", "y4"};
	double corners[8];

	ZEND_PARSE_PARAMETERS_START(8, 8)
		Z_PARAM_DOUBLE(corners[0])
		Z_PARAM_DOUBLE(corners[1])
		Z_PARAM_DOUBLE(corners[2])
		Z_PARAM_DOUBLE(corners[3])
		Z_PARAM_DOUBLE(corners[4])
		Z_PARAM_DOUBLE(corners[5])
		Z_PARAM_DOUBLE(corners[6])
		Z_PARAM_DOUBLE(corners[7])
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_coords_construct(ZEND_THIS, pdfium_ce_quad, names, corners, 8)) {
		RETURN_THROWS();
	}
}

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
	return pdfium_slots_named(pdfium_ce_bookmark, "title open color italic bold destination action children")
		&& pdfium_slots_named(pdfium_ce_link, "rect quadPoints destination action")
		&& pdfium_slots_named(pdfium_ce_quad, "x1 y1 x2 y2 x3 y3 x4 y4")
		&& pdfium_slots_named(pdfium_ce_destination, "pageIndex view params x y zoom")
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

static void destination_set(zval *zv, FPDF_DOCUMENT doc, int pages, FPDF_DEST dest)
{
	static const char *views[] = {NULL, "XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH", "FitBV"};
	int index = dest ? FPDFDest_GetDestPageIndex(doc, dest) : -1;

	if (index < 0 || index >= pages) {
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

static unsigned long action_bytes(const action_info *a, void *buf, unsigned long len)
{
	if (a->type == PDFACTION_URI) {
		return FPDFAction_GetURIPath(a->doc, a->action, buf, len);
	}
	return has_file_path(a->type) ? FPDFAction_GetFilePath(a->action, buf, len) : 0;
}

static void action_bytes_set(zval *zv, const action_info *a, bool applies)
{
	unsigned long bytes = a->bytes;

	ZVAL_NULL(zv);
	if (!applies || bytes <= 1) {
		return;
	}
	zend_string *s = zend_string_alloc(bytes - 1, 0);

	if (action_bytes(a, ZSTR_VAL(s), bytes) != bytes) {
		zend_string_efree(s);
		return;
	}
	ZSTR_VAL(s)[bytes - 1] = '\0';
	ZVAL_STR(zv, s);
}

static action_info action_read(FPDF_DOCUMENT doc, FPDF_ACTION action)
{
	action_info a = {doc, action, PDFACTION_UNSUPPORTED, 0};

	if (action) {
		a.type = FPDFAction_GetType(action);
		a.bytes = action_bytes(&a, NULL, 0);
	}
	return a;
}

static void action_set(zval *zv, int pages, const action_info *a)
{
	static const char *types[] = {"Unsupported", "GoTo", "GoToR", "URI", "Launch", "GoToE"};

	if (!a->action) {
		ZVAL_NULL(zv);
		return;
	}
	object_init_ex(zv, pdfium_ce_action);
	zend_object *obj = Z_OBJ_P(zv);

	ZVAL_STRING(pdfium_slot(obj, ACTION_TYPE), a->type < sizeof(types) / sizeof(types[0]) ? types[a->type] : types[0]);
	action_bytes_set(pdfium_slot(obj, ACTION_URI), a, a->type == PDFACTION_URI);
	action_bytes_set(pdfium_slot(obj, ACTION_FILE_PATH), a, has_file_path(a->type));
	if (a->type == PDFACTION_GOTO) {
		destination_set(pdfium_slot(obj, ACTION_DESTINATION), a->doc, pages, FPDFAction_GetDest(a->doc, a->action));
	} else {
		ZVAL_NULL(pdfium_slot(obj, ACTION_DESTINATION));
	}
}

static void targets_set(zval *dest_zv, zval *action_zv, int pages, FPDF_DEST own_dest, const action_info *a)
{
	action_set(action_zv, pages, a);
	if (!a->action) {
		destination_set(dest_zv, a->doc, pages, own_dest);
	} else if (a->type == PDFACTION_GOTO) {
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
	action_info a = action_read(ph->h->doc, FPDF_GetPageAAction(ph->page, which));

	if (pdfium_memory_fits(ACTION_BYTES + (size_t) a.bytes)) {
		action_set(return_value, FPDF_GetPageCount(ph->h->doc), &a);
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

static link_info link_read(FPDF_DOCUMENT doc, FPDF_LINK link)
{
	link_info li = {MAX(FPDFLink_CountQuadPoints(link), 0), action_read(doc, FPDFLink_GetAction(link))};

	return li;
}

static bool link_fits(const link_info *li, uint32_t held)
{
	return pdfium_memory_fits(LINK_BYTES + (size_t) li->quads * PDFIUM_QUAD_BYTES + 2 * (size_t) held * sizeof(zval)
		+ (size_t) li->action.bytes);
}

void pdfium_quad_init(zval *zv, const FS_QUADPOINTSF *q)
{
	float corners[] = {q->x1, q->y1, q->x2, q->y2, q->x3, q->y3, q->x4, q->y4};

	object_init_ex(zv, pdfium_ce_quad);
	for (int c = 0; c < 8; c++) {
		ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(zv), c), pdfium_points(corners[c]));
	}
}

static void quad_points_set(zval *zv, FPDF_LINK link, int count)
{
	array_init_size(zv, (uint32_t) count);
	for (int i = 0; i < count; i++) {
		FS_QUADPOINTSF q;
		zval zq;

		if (!FPDFLink_GetQuadPoints(link, i, &q)) {
			continue;
		}
		pdfium_quad_init(&zq, &q);
		add_next_index_zval(zv, &zq);
	}
}

static void link_set(zval *zv, FPDF_DOCUMENT doc, int pages, FPDF_LINK link, const link_info *li)
{
	FS_RECTF r = {0};

	FPDFLink_GetAnnotRect(link, &r);
	object_init_ex(zv, pdfium_ce_link);
	zend_object *obj = Z_OBJ_P(zv);

	pdfium_rect_init(pdfium_slot(obj, LINK_RECT), r.left, r.bottom, r.right, r.top);
	quad_points_set(pdfium_slot(obj, LINK_QUAD_POINTS), link, li->quads);
	targets_set(pdfium_slot(obj, LINK_DESTINATION), pdfium_slot(obj, LINK_ACTION), pages,
		li->action.action ? NULL : FPDFLink_GetDest(doc, link), &li->action);
}

bool pdfium_link_build(zval *zv, FPDF_DOCUMENT doc, FPDF_LINK link)
{
	link_info li = link_read(doc, link);

	if (!link_fits(&li, 0)) {
		return false;
	}
	link_set(zv, doc, FPDF_GetPageCount(doc), link, &li);
	return true;
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
	int pages = FPDF_GetPageCount(ph->h->doc);

	array_init(return_value);
	while (FPDFLink_Enumerate(ph->page, &pos, &link)) {
		link_info li = link_read(ph->h->doc, link);
		zval zl;

		if (!link_fits(&li, zend_hash_num_elements(Z_ARRVAL_P(return_value)))) {
			oom = "Links need";
			break;
		}
		link_set(&zl, ph->h->doc, pages, link, &li);
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
	FPDF_LINK link = FPDFLink_GetLinkAtPoint(ph->page, pdfium_float_side(x), pdfium_float_side(y));

	if (link && !pdfium_link_build(return_value, ph->h->doc, link)) {
		oom = "The link needs";
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
	zend_long rgba[COLOR_SLOTS] = {color_channel(rgb[0]), color_channel(rgb[1]), color_channel(rgb[2]), 255};

	pdfium_color_init(zv, rgba);
}

static unsigned long title_get(void *bm, unsigned short *buf, unsigned long bytes)
{
	return FPDFBookmark_GetTitle(bm, buf, bytes);
}

static void bookmark_title_set(zval *zv, FPDF_BOOKMARK bm, unsigned long bytes)
{
	zend_string *title = pdfium_utf16le_fetch(title_get, bm, bytes);

	ZVAL_STR(zv, title ? title : ZSTR_EMPTY_ALLOC());
}

static bool bookmark_fits(unsigned long title_bytes, const action_info *a, uint32_t held)
{
	return pdfium_memory_fits(BOOKMARK_BYTES + (size_t) title_bytes + (size_t) title_bytes / 2 * 3
		+ (size_t) held * (2 * sizeof(zval) + 2 * (sizeof(Bucket) + 2 * sizeof(uint32_t))) + (size_t) a->bytes);
}

static zend_object *bookmark_append(zval *into, FPDF_DOCUMENT doc, int pages, FPDF_BOOKMARK bm,
	unsigned long title_bytes, const action_info *a)
{
	zval zb;
	int style = FPDFBookmark_GetStyle(bm);

	object_init_ex(&zb, pdfium_ce_bookmark);
	zend_object *obj = Z_OBJ(zb);

	bookmark_title_set(pdfium_slot(obj, BOOKMARK_TITLE), bm, title_bytes);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_OPEN), FPDFBookmark_GetCount(bm) > 0);
	bookmark_color_set(pdfium_slot(obj, BOOKMARK_COLOR), bm);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_ITALIC), style & STYLE_ITALIC);
	ZVAL_BOOL(pdfium_slot(obj, BOOKMARK_BOLD), style & STYLE_BOLD);
	targets_set(pdfium_slot(obj, BOOKMARK_DESTINATION), pdfium_slot(obj, BOOKMARK_ACTION), pages,
		a->action ? NULL : FPDFBookmark_GetDest(doc, bm), a);
	array_init(pdfium_slot(obj, BOOKMARK_CHILDREN));
	add_next_index_zval(into, &zb);
	return obj;
}

static const char *bookmarks_of(FPDF_DOCUMENT doc, zval *return_value, HashTable *visited, bookmark_level *levels)
{
	int depth = 1, pages = FPDF_GetPageCount(doc);

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
		action_info a = action_read(doc, FPDFBookmark_GetAction(bm));

		if (!bookmark_fits(title_bytes, &a, zend_hash_num_elements(visited))) {
			return "Bookmarks need";
		}
		zend_hash_index_add_empty_element(visited, (zend_ulong) (uintptr_t) bm);
		zend_object *obj = bookmark_append(level->into, doc, pages, bm, title_bytes, &a);

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
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	bookmark_level levels[BOOKMARK_DEPTH];
	HashTable visited;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	zend_hash_init(&visited, 8, NULL, NULL, 0);
	array_init(return_value);

	const char *oom = bookmarks_of(h->doc, return_value, &visited, levels);

	zend_hash_destroy(&visited);
	pdfium_leave_or_throw(return_value, oom);
}
