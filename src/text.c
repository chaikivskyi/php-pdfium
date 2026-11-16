#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <math.h>
#include <float.h>
#include <fpdf_text.h>
#include <fpdf_searchex.h>
#include "zend_exceptions.h"
#include "ext/standard/html.h"
#include "unicode_tables.h"

#define CHAR_BYTES 1024
#define CHAR_MIN_BYTES 512
#define SPAN_BYTES 512
#define RECT_BYTES 160
#define ANGLE_STEPS 1e4
#define UNMAPPED_MARK 0xFFFE
#define POINTS_KEPT 256

enum {
	CHAR_TEXT, CHAR_BOX, CHAR_LOOSE_BOX, CHAR_X, CHAR_Y, CHAR_FONT_SIZE, CHAR_FONT_NAME, CHAR_FONT_WEIGHT,
	CHAR_FONT_FLAGS, CHAR_FILL_COLOR, CHAR_STROKE_COLOR, CHAR_ANGLE, CHAR_MATRIX, CHAR_GENERATED, CHAR_HYPHEN,
	CHAR_UNICODE_MAP_ERROR,
};
enum { COLOR_SLOTS = 4 };
enum { MATRIX_A, MATRIX_B, MATRIX_C, MATRIX_D, MATRIX_E, MATRIX_F };
enum { MATCH_INDEX, MATCH_LENGTH, MATCH_TEXT, MATCH_RECTS };
enum { LINK_URL, LINK_INDEX, LINK_LENGTH, LINK_RECTS };
enum { RANGE_FIRST, RANGE_LAST, RANGE_DELTA, RANGE_STEP };

typedef struct {
	unsigned char key[sizeof(FS_MATRIX)];
	zend_object *obj;
} object_run;

typedef struct {
	uint32_t bits[POINTS_KEPT];
	double points[POINTS_KEPT];
} points_run;

typedef struct {
	FPDF_TEXTPAGE tp;
	int chars, text, chr, length;
} text_run;

typedef struct {
	double line;
	float x, y;
	unsigned short unit, space;
	bool boxed;
} placed_char;

static bool slots_named(zend_class_entry *ce, const char *names)
{
	int slots = 0;

	while (*names) {
		size_t len = strcspn(names, " ");
		zend_property_info *info = zend_hash_str_find_ptr(&ce->properties_info, names, len);

		if (!info || info->offset != (uint32_t) OBJ_PROP_TO_OFFSET(slots)) {
			return false;
		}
		slots++;
		names += len + (names[len] == ' ');
	}
	return slots == ce->default_properties_count;
}

bool pdfium_text_slots_match(void)
{
	return slots_named(pdfium_ce_rect, "left bottom right top")
		&& slots_named(pdfium_ce_color, "red green blue alpha")
		&& slots_named(pdfium_ce_matrix, "a b c d e f")
		&& slots_named(pdfium_ce_char, "text box looseBox x y fontSize fontName fontWeight fontFlags fillColor strokeColor"
			" angle matrix generated hyphen unicodeMapError")
		&& slots_named(pdfium_ce_text_match, "index length text rects")
		&& slots_named(pdfium_ce_web_link, "url index length rects");
}

ZEND_METHOD(Pdfium_Color, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Matrix, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Char, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_TextMatch, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_WebLink, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Rect, __construct)
{
	static const char *names[] = {"left", "bottom", "right", "top"};
	double sides[4];

	ZEND_PARSE_PARAMETERS_START(4, 4)
		Z_PARAM_DOUBLE(sides[RECT_LEFT])
		Z_PARAM_DOUBLE(sides[RECT_BOTTOM])
		Z_PARAM_DOUBLE(sides[RECT_RIGHT])
		Z_PARAM_DOUBLE(sides[RECT_TOP])
	ZEND_PARSE_PARAMETERS_END();

	for (uint32_t i = 0; i < 4; i++) {
		if (!zend_finite(sides[i])) {
			zend_argument_value_error(i + 1, "must be finite");
			RETURN_THROWS();
		}
	}
	if (sides[RECT_LEFT] > sides[RECT_RIGHT]) {
		zend_argument_value_error(1, "must not be greater than argument #3 ($right)");
		RETURN_THROWS();
	}
	if (sides[RECT_BOTTOM] > sides[RECT_TOP]) {
		zend_argument_value_error(2, "must not be greater than argument #4 ($top)");
		RETURN_THROWS();
	}
	for (int i = 0; i < 4; i++) {
		zend_update_property_double(pdfium_ce_rect, Z_OBJ_P(ZEND_THIS), names[i], strlen(names[i]), sides[i]);
		if (EG(exception)) {
			RETURN_THROWS();
		}
	}
}

static zval *slot(zend_object *obj, int index)
{
	zval *zv = OBJ_PROP_NUM(obj, index);

	Z_PROP_FLAG_P(zv) = 0;
	return zv;
}

static float float_side(double value)
{
	if (zend_isnan(value)) {
		return 0;
	}
	return value > FLT_MAX ? FLT_MAX : value < -FLT_MAX ? -FLT_MAX : (float) value;
}

static double points_of(points_run *run, float value)
{
	uint32_t bits;

	if (!run) {
		return pdfium_points(value);
	}
	memcpy(&bits, &value, sizeof(bits));
	uint32_t at = (bits * 2654435761u) >> 24;

	if (run->bits[at] != bits) {
		run->bits[at] = bits;
		run->points[at] = pdfium_points(value);
	}
	return run->points[at];
}

static void rect_init(zval *zv, points_run *run, double x1, double y1, double x2, double y2)
{
	float fx1 = float_side(x1), fy1 = float_side(y1), fx2 = float_side(x2), fy2 = float_side(y2);

	object_init_ex(zv, pdfium_ce_rect);
	zend_object *obj = Z_OBJ_P(zv);

	ZVAL_DOUBLE(slot(obj, RECT_LEFT), points_of(run, MIN(fx1, fx2)));
	ZVAL_DOUBLE(slot(obj, RECT_BOTTOM), points_of(run, MIN(fy1, fy2)));
	ZVAL_DOUBLE(slot(obj, RECT_RIGHT), points_of(run, MAX(fx1, fx2)));
	ZVAL_DOUBLE(slot(obj, RECT_TOP), points_of(run, MAX(fy1, fy2)));
}

static bool joined_hyphen(FPDF_TEXTPAGE tp, int c)
{
	return c >= 0 && FPDFText_IsHyphen(tp, c) == 1;
}

static unsigned int code_point(unsigned int prev, unsigned int u, unsigned int next, bool hyphen)
{
	if (hyphen) {
		return '-';
	}
	if (u == UNMAPPED_MARK) {
		return 0;
	}
	if (pdfium_high_surrogate(u) && pdfium_low_surrogate(next)) {
		return pdfium_surrogate_pair(u, next);
	}
	if (pdfium_low_surrogate(u) && pdfium_high_surrogate(prev)) {
		return 0;
	}
	return u;
}

static bool run_reuse(zval *zv, const object_run *run, const void *key, size_t size)
{
	if (!run->obj || memcmp(run->key, key, size)) {
		return false;
	}
	ZVAL_OBJ_COPY(zv, run->obj);
	return true;
}

static void run_keep(zval *zv, object_run *run, const void *key, size_t size)
{
	memcpy(run->key, key, size);
	run->obj = Z_OBJ_P(zv);
}

static void color_set(zval *zv, object_run *run, FPDF_BOOL got, const unsigned int *rgba)
{
	if (!got) {
		ZVAL_NULL(zv);
		return;
	}
	if (run_reuse(zv, run, rgba, 4 * sizeof(*rgba))) {
		return;
	}
	object_init_ex(zv, pdfium_ce_color);
	for (int i = 0; i < COLOR_SLOTS; i++) {
		ZVAL_LONG(slot(Z_OBJ_P(zv), i), rgba[i]);
	}
	run_keep(zv, run, rgba, 4 * sizeof(*rgba));
}

static void matrix_set(zval *zv, object_run *run, FPDF_BOOL got, const FS_MATRIX *m)
{
	if (!got) {
		ZVAL_NULL(zv);
		return;
	}
	if (run_reuse(zv, run, m, sizeof(*m))) {
		return;
	}
	object_init_ex(zv, pdfium_ce_matrix);
	zend_object *obj = Z_OBJ_P(zv);

	ZVAL_DOUBLE(slot(obj, MATRIX_A), pdfium_points(m->a));
	ZVAL_DOUBLE(slot(obj, MATRIX_B), pdfium_points(m->b));
	ZVAL_DOUBLE(slot(obj, MATRIX_C), pdfium_points(m->c));
	ZVAL_DOUBLE(slot(obj, MATRIX_D), pdfium_points(m->d));
	ZVAL_DOUBLE(slot(obj, MATRIX_E), pdfium_points(m->e));
	ZVAL_DOUBLE(slot(obj, MATRIX_F), pdfium_points(m->f));
	run_keep(zv, run, m, sizeof(*m));
}

static void char_text(zval *zv, unsigned int cp)
{
	char buf[4];

	if (!cp) {
		ZVAL_EMPTY_STRING(zv);
		return;
	}
	size_t len = pdfium_utf8_encode(cp, buf);

	if (len == 1) {
		ZVAL_INTERNED_STR(zv, ZSTR_CHAR((unsigned char) buf[0]));
	} else {
		ZVAL_STRINGL(zv, buf, len);
	}
}

static zend_string *units_to_utf8(unsigned short *units, size_t count)
{
	size_t kept = 0;

	for (size_t i = 0; i < count; i++) {
		if (units[i] != UNMAPPED_MARK) {
			units[kept++] = units[i];
		}
	}
	return pdfium_utf16le_to_utf8(units, kept);
}

static bool run_holds(const text_run *run, int length)
{
	return FPDFText_GetTextIndexFromCharIndex(run->tp, run->chr + length - 1) == run->text + length - 1;
}

static bool run_next(text_run *run)
{
	run->text += run->length;
	run->length = 0;
	run->chr = FPDFText_GetCharIndexFromTextIndex(run->tp, run->text);
	if (run->chr < 0 || run->chr >= run->chars) {
		run->chr = run->chars;
		return false;
	}
	int low = 1, high = run->chars - run->chr;

	if (run_holds(run, high)) {
		low = high;
	}
	while (high - low > 1) {
		int middle = low + (high - low) / 2;

		if (run_holds(run, middle)) {
			low = middle;
		} else {
			high = middle;
		}
	}
	run->length = low;
	return true;
}

static bool in_text(text_run *run, int chr)
{
	while (chr >= run->chr + run->length && run_next(run)) {
	}
	return chr >= run->chr && chr < run->chr + run->length;
}

static int char_at(text_run *run, int text)
{
	if (text < run->text) {
		run->text = run->chr = run->length = 0;
	}
	while (text >= run->text + run->length) {
		if (!run_next(run)) {
			return -1;
		}
	}
	return run->chr + text - run->text;
}

static void mark_text_hyphens(FPDF_TEXTPAGE tp, int chars, unsigned short *units, size_t count)
{
	text_run run = {.tp = tp, .chars = chars};

	for (size_t at = 0; at < count; at++) {
		if (units[at] == UNMAPPED_MARK && joined_hyphen(tp, char_at(&run, (int) at))) {
			units[at] = '-';
		}
	}
}

static size_t units_got(int got)
{
	return got > 0 ? (size_t) got - 1 : 0;
}

static bool span_fits(const zval *spans, size_t text_bytes, int rects)
{
	size_t grow = 2 * zend_hash_num_elements(Z_ARRVAL_P(spans)) * sizeof(zval);

	return pdfium_memory_fits(SPAN_BYTES + grow + text_bytes + (size_t) MAX(rects, 0) * RECT_BYTES);
}

static void rects_set(zval *zv, FPDF_TEXTPAGE tp, FPDF_PAGELINK links, int link, int count)
{
	array_init_size(zv, (uint32_t) MAX(count, 0));
	for (int r = 0; r < count; r++) {
		double left = 0, top = 0, right = 0, bottom = 0;
		zval rect;

		if (links) {
			FPDFLink_GetRect(links, link, r, &left, &top, &right, &bottom);
		} else {
			FPDFText_GetRect(tp, r, &left, &top, &right, &bottom);
		}
		rect_init(&rect, NULL, left, bottom, right, top);
		add_next_index_zval(zv, &rect);
	}
}

static FPDF_TEXTPAGE enter_text_page(pdfium_page_handle *ph)
{
	if (!pdfium_enter()) {
		return NULL;
	}
	FPDF_TEXTPAGE tp = FPDFText_LoadPage(ph->page);

	if (!tp) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_format_exception, "Cannot load page text", 0);
	}
	return tp;
}

static void leave_or_throw(zval *return_value, const char *oom)
{
	pdfium_leave();
	if (oom) {
		zval_ptr_dtor(return_value);
		ZVAL_NULL(return_value);
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0, "%s more memory than memory_limit leaves", oom);
	}
}

static FPDF_TEXTPAGE this_text_page(zval *self)
{
	return pdfium_text_page_from_obj(Z_OBJ_P(self))->tp;
}

static const char *text_of(FPDF_TEXTPAGE tp, zval *return_value)
{
	int count = FPDFText_CountChars(tp);

	if (count <= 0) {
		RETVAL_EMPTY_STRING();
		return NULL;
	}
	if (!pdfium_memory_fits((size_t) count * 8)) {
		return "Page text needs";
	}
	unsigned short *buf = safe_emalloc((size_t) count + 1, sizeof(*buf), 0);
	size_t units = units_got(FPDFText_GetText(tp, 0, count, buf));

	mark_text_hyphens(tp, count, buf, units);
	RETVAL_STR(units_to_utf8(buf, units));
	efree(buf);
	return NULL;
}

static const char *chars_of(FPDF_TEXTPAGE tp, int from, int to, zval *return_value)
{
	zend_string *font = NULL;
	object_run fill = {0}, stroke = {0}, matrices = {0};
	points_run points = {0};
	char *name = NULL;
	unsigned long cap = 0;
	bool oom = false;
	int count = FPDFText_CountChars(tp);

	if (!pdfium_memory_fits((size_t) (to - from) * (sizeof(zval) + CHAR_MIN_BYTES))) {
		return "Page characters need";
	}
	array_init_size(return_value, (uint32_t) (to - from));

	unsigned int prev = from > 0 ? FPDFText_GetUnicode(tp, from - 1) : 0;
	unsigned int unit = from < to ? FPDFText_GetUnicode(tp, from) : 0;

	for (int i = from; i < to; i++) {
		double left = 0, right = 0, bottom = 0, top = 0, x = 0, y = 0;
		FS_RECTF loose = {0};
		FS_MATRIX matrix = {0};
		unsigned int rgba[4];
		FPDF_BOOL got;
		int flags = 0;
		zval zc;

		if (!pdfium_memory_fits(CHAR_BYTES + cap)) {
			oom = true;
			break;
		}

		unsigned long len = FPDFText_GetFontInfo(tp, i, name, cap, &flags);

		if (len > cap) {
			if (!pdfium_memory_fits(CHAR_BYTES + len)) {
				oom = true;
				break;
			}
			cap = len;
			name = erealloc(name, cap);
			len = FPDFText_GetFontInfo(tp, i, name, cap, &flags);
		}
		object_init_ex(&zc, pdfium_ce_char);
		zend_object *c = Z_OBJ(zc);

		bool hyphen = joined_hyphen(tp, i);
		unsigned int next = i + 1 < count ? FPDFText_GetUnicode(tp, i + 1) : 0;

		char_text(slot(c, CHAR_TEXT), code_point(prev, unit, next, hyphen));
		prev = unit;
		unit = next;
		FPDFText_GetCharBox(tp, i, &left, &right, &bottom, &top);
		rect_init(slot(c, CHAR_BOX), &points, left, bottom, right, top);
		FPDFText_GetLooseCharBox(tp, i, &loose);
		rect_init(slot(c, CHAR_LOOSE_BOX), &points, loose.left, loose.bottom, loose.right, loose.top);
		FPDFText_GetCharOrigin(tp, i, &x, &y);
		ZVAL_DOUBLE(slot(c, CHAR_X), points_of(&points, (float) x));
		ZVAL_DOUBLE(slot(c, CHAR_Y), points_of(&points, (float) y));
		ZVAL_DOUBLE(slot(c, CHAR_FONT_SIZE), points_of(&points, (float) FPDFText_GetFontSize(tp, i)));
		if (len > 1) {
			if (!font || ZSTR_LEN(font) != len - 1 || memcmp(ZSTR_VAL(font), name, len - 1)) {
				if (font) {
					zend_string_release(font);
				}
				font = zend_string_init(name, len - 1, 0);
			}
			ZVAL_STR_COPY(slot(c, CHAR_FONT_NAME), font);
		} else {
			ZVAL_NULL(slot(c, CHAR_FONT_NAME));
		}

		int weight = FPDFText_GetFontWeight(tp, i);

		if (weight <= 0) {
			ZVAL_NULL(slot(c, CHAR_FONT_WEIGHT));
		} else {
			ZVAL_LONG(slot(c, CHAR_FONT_WEIGHT), weight);
		}
		ZVAL_LONG(slot(c, CHAR_FONT_FLAGS), (uint32_t) flags);
		got = FPDFText_GetFillColor(tp, i, &rgba[0], &rgba[1], &rgba[2], &rgba[3]);
		color_set(slot(c, CHAR_FILL_COLOR), &fill, got, rgba);
		got = FPDFText_GetStrokeColor(tp, i, &rgba[0], &rgba[1], &rgba[2], &rgba[3]);
		color_set(slot(c, CHAR_STROKE_COLOR), &stroke, got, rgba);

		float radians = FPDFText_GetCharAngle(tp, i);
		double steps = radians < 0 ? 0 : (360.0 - radians * 180.0 / M_PI) * ANGLE_STEPS;

		ZVAL_DOUBLE(slot(c, CHAR_ANGLE), steps < 1 || steps > 360.0 * ANGLE_STEPS - 1 ? 0 : round(steps) / ANGLE_STEPS);
		got = FPDFText_GetMatrix(tp, i, &matrix);
		matrix_set(slot(c, CHAR_MATRIX), &matrices, got, &matrix);
		ZVAL_BOOL(slot(c, CHAR_GENERATED), FPDFText_IsGenerated(tp, i) == 1);
		ZVAL_BOOL(slot(c, CHAR_HYPHEN), hyphen);
		ZVAL_BOOL(slot(c, CHAR_UNICODE_MAP_ERROR), FPDFText_HasUnicodeMapError(tp, i) == 1);
		add_next_index_zval(return_value, &zc);
	}
	if (font) {
		zend_string_release(font);
	}
	if (name) {
		efree(name);
	}
	return oom ? "Page characters need" : NULL;
}

static placed_char *placed_chars(FPDF_TEXTPAGE tp, int count)
{
	placed_char *chars = safe_emalloc((size_t) count, sizeof(*chars), 0);
	text_run run = {.tp = tp, .chars = count};

	for (int c = 0; c < count; c++) {
		double left = 0, right = 0, bottom = 0, top = 0, x = 0, y = 0;
		unsigned int unit = FPDFText_GetUnicode(tp, c);
		placed_char *pc = &chars[c];

		pc->boxed = FPDFText_GetCharBox(tp, c, &left, &right, &bottom, &top) && left != right && bottom != top;
		pc->x = (float) ((left + right) / 2);
		pc->y = (float) ((bottom + top) / 2);
		pc->space = unit == ' ' || unit == 0xA0 ? (unsigned short) unit : 0;
		pc->line = 0;
		pc->unit = 0;
		if (!pc->boxed) {
			continue;
		}
		FPDFText_GetCharOrigin(tp, c, &x, &y);
		pc->line = y;
		if (joined_hyphen(tp, c)) {
			pc->unit = '-';
		} else if (unit && unit < UNMAPPED_MARK && in_text(&run, c)) {
			pc->unit = (unsigned short) unit;
		}
	}
	return chars;
}

static size_t units_in(const placed_char *chars, int count, const double *sides, unsigned short *units)
{
	float left = float_side(sides[RECT_LEFT]), bottom = float_side(sides[RECT_BOTTOM]);
	float right = float_side(sides[RECT_RIGHT]), top = float_side(sides[RECT_TOP]);
	size_t kept = 0;
	double line = 0;
	unsigned short spaced = 0;
	bool inside = false, broken = false;

	for (int c = 0; c < count; c++) {
		const placed_char *pc = &chars[c];

		if (pc->boxed && pc->x >= left && pc->x <= right && pc->y >= bottom && pc->y <= top) {
			if (pc->line != line && !inside && broken && kept) {
				units[kept++] = '\r';
				units[kept++] = '\n';
			} else if (spaced) {
				units[kept++] = spaced;
			}
			spaced = 0;
			line = pc->line;
			inside = true;
			broken = false;
			if (pc->unit) {
				units[kept++] = pc->unit;
			}
		} else if (pc->space) {
			if (inside) {
				spaced = pc->space;
				inside = false;
				broken = false;
			}
		} else {
			inside = false;
			broken = true;
		}
	}
	return kept;
}

static const char *texts_in(FPDF_TEXTPAGE tp, const double *sides, uint32_t rects, zval *texts)
{
	int count = FPDFText_CountChars(tp);
	bool fits = true;

	if (count <= 0) {
		for (uint32_t r = 0; r < rects; r++) {
			ZVAL_EMPTY_STRING(&texts[r]);
		}
		return NULL;
	}
	if (!pdfium_memory_fits((size_t) count * (sizeof(placed_char) + 2 * sizeof(unsigned short)))) {
		return "Text in the rectangle needs";
	}
	unsigned short *units = safe_emalloc((size_t) count + 1, 2 * sizeof(*units), 0);
	placed_char *chars = placed_chars(tp, count);

	for (uint32_t r = 0; fits && r < rects; r++) {
		size_t kept = units_in(chars, count, sides + 4 * r, units);

		fits = pdfium_memory_fits(kept * 3 + SPAN_BYTES);
		if (fits) {
			ZVAL_STR(&texts[r], pdfium_utf16le_to_utf8(units, kept));
		}
	}
	efree(units);
	efree(chars);
	return fits ? NULL : "Text in the rectangle needs";
}

static bool gap_unit(unsigned int unit)
{
	return unit == ' ' || unit == '\n' || unit == '\r' || unit == 0xA0;
}

static bool word_point(unsigned int point)
{
	size_t count = sizeof(pdfium_word) / sizeof(*pdfium_word), low = 0, high = count;

	if (point < 0x80) {
		return (point >= '0' && point <= '9') || (point >= 'a' && point <= 'z') || (point >= 'A' && point <= 'Z');
	}
	while (low < high) {
		size_t middle = (low + high) / 2;

		if (point > pdfium_word[middle][RANGE_LAST]) {
			low = middle + 1;
		} else {
			high = middle;
		}
	}
	return low < count && point >= pdfium_word[low][RANGE_FIRST];
}

static unsigned int point_before(const unsigned short *units, size_t at)
{
	if (at > 1 && pdfium_low_surrogate(units[at - 1]) && pdfium_high_surrogate(units[at - 2])) {
		return pdfium_surrogate_pair(units[at - 2], units[at - 1]);
	}
	return units[at - 1];
}

static unsigned int point_at(const unsigned short *units, size_t size, size_t at)
{
	if (at + 1 < size && pdfium_high_surrogate(units[at]) && pdfium_low_surrogate(units[at + 1])) {
		return pdfium_surrogate_pair(units[at], units[at + 1]);
	}
	return units[at];
}

static unsigned short fold_unit(unsigned short unit)
{
	if (unit < 0x80) {
		return unit >= 'A' && unit <= 'Z' ? unit + ('a' - 'A') : unit;
	}
	size_t count = sizeof(pdfium_casefold) / sizeof(*pdfium_casefold), low = 0, high = count;

	while (low < high) {
		size_t middle = (low + high) / 2;

		if (unit > pdfium_casefold[middle][RANGE_LAST]) {
			low = middle + 1;
		} else {
			high = middle;
		}
	}
	if (low == count || unit < pdfium_casefold[low][RANGE_FIRST]
		|| (unit - pdfium_casefold[low][RANGE_FIRST]) % pdfium_casefold[low][RANGE_STEP]) {
		return unit;
	}
	return (unsigned short) (unit + pdfium_casefold[low][RANGE_DELTA]);
}

static size_t pack_units(const unsigned short *from, size_t size, unsigned short *to, int *first, bool exact)
{
	size_t kept = 0;
	bool run = false;

	for (size_t i = 0; i < size; i++) {
		bool gap = gap_unit(from[i]);

		if ((first && from[i] == UNMAPPED_MARK) || (gap && run)) {
			continue;
		}
		run = gap;
		if (first) {
			first[kept] = (int) i;
		}
		to[kept++] = gap ? ' ' : exact ? from[i] : fold_unit(from[i]);
	}
	return kept;
}

static size_t *needle_links(const unsigned short *needle, size_t length)
{
	size_t *links = safe_emalloc(length, sizeof(*links), 0);

	links[0] = 0;
	for (size_t i = 1, k = 0; i < length; i++) {
		while (k && needle[i] != needle[k]) {
			k = links[k - 1];
		}
		if (needle[i] == needle[k]) {
			k++;
		}
		links[i] = k;
	}
	return links;
}

static bool whole_word(const unsigned short *units, size_t size, size_t from, size_t end)
{
	return !(from && word_point(point_before(units, from)) && word_point(point_at(units, size, from)))
		&& !(end < size && word_point(point_at(units, size, end)) && word_point(point_before(units, end)));
}

static size_t unit_end(const unsigned short *text, size_t size, size_t at)
{
	size_t end = at + 1;

	for (size_t i = end; gap_unit(text[at]) && i < size && (gap_unit(text[i]) || text[i] == UNMAPPED_MARK); i++) {
		if (text[i] != UNMAPPED_MARK) {
			end = i + 1;
		}
	}
	return end;
}

static bool match_append(FPDF_TEXTPAGE tp, int index, int length, const unsigned short *text, size_t size,
	zval *return_value)
{
	int rects = FPDFText_CountRects(tp, index, length);
	zval zm;

	if (!span_fits(return_value, size * 5, rects)) {
		return false;
	}
	unsigned short *units = safe_emalloc(size, sizeof(*units), 0);

	memcpy(units, text, size * sizeof(*units));
	object_init_ex(&zm, pdfium_ce_text_match);
	zend_object *m = Z_OBJ(zm);

	ZVAL_LONG(slot(m, MATCH_INDEX), index);
	ZVAL_LONG(slot(m, MATCH_LENGTH), length);
	rects_set(slot(m, MATCH_RECTS), tp, NULL, 0, rects);
	ZVAL_STR(slot(m, MATCH_TEXT), units_to_utf8(units, size));
	efree(units);
	add_next_index_zval(return_value, &zm);
	return true;
}

static const char *search_of(FPDF_TEXTPAGE tp, unsigned short *needle, size_t length, bool exact, bool whole,
	bool overlapping, zval *return_value)
{
	int count = FPDFText_CountChars(tp);
	bool fits = true;

	array_init(return_value);
	if (count <= 0) {
		return NULL;
	}
	if (!pdfium_memory_fits(((size_t) count + 1) * (2 * sizeof(*needle) + sizeof(int)))) {
		return "Search matches need";
	}
	unsigned short *text = safe_emalloc((size_t) count + 1, 2 * sizeof(*text), 0), *units = text + count + 1;
	int *first = safe_emalloc((size_t) count + 1, sizeof(*first), 0);
	size_t size = units_got(FPDFText_GetText(tp, 0, count, text));
	text_run run = {.tp = tp, .chars = count};

	mark_text_hyphens(tp, count, text, size);

	size_t packed = pack_units(text, size, units, first, exact);

	length = pack_units(needle, length, needle, NULL, exact);

	size_t *links = needle_links(needle, length);

	for (size_t i = 0, k = 0, next = 0; fits && i < packed; i++) {
		while (k && units[i] != needle[k]) {
			k = links[k - 1];
		}
		if (units[i] != needle[k] || ++k < length) {
			continue;
		}
		size_t from = i + 1 - length;

		k = links[length - 1];
		if (from < next || (whole && !whole_word(units, packed, from, i + 1))) {
			continue;
		}
		size_t end = unit_end(text, size, (size_t) first[i]);
		int index = char_at(&run, first[from]);
		int last = char_at(&run, (int) end - 1);

		fits = match_append(tp, index, last - index + 1, text + first[from], end - (size_t) first[from], return_value);
		next = overlapping ? 0 : i + 1;
	}
	efree(links);
	efree(first);
	efree(text);
	return fits ? NULL : "Search matches need";
}

static const char *web_links_of(FPDF_TEXTPAGE tp, zval *return_value)
{
	unsigned short *buf = NULL;
	int cap = 0;
	bool oom = false;

	array_init(return_value);

	FPDF_PAGELINK links = FPDFLink_LoadWebLinks(tp);
	int count = links ? FPDFLink_CountWebLinks(links) : 0;

	for (int i = 0; i < count; i++) {
		int units = FPDFLink_GetURL(links, i, NULL, 0), start = 0, length = 0, n = FPDFLink_CountRects(links, i);
		zval zl;

		if (!span_fits(return_value, (size_t) MAX(units, 0) * 5, n)) {
			oom = true;
			break;
		}
		if (units > cap) {
			cap = units;
			buf = safe_erealloc(buf, (size_t) cap, sizeof(*buf), 0);
		}
		if (!FPDFLink_GetTextRange(links, i, &start, &length)) {
			continue;
		}
		int got = units > 0 ? FPDFLink_GetURL(links, i, buf, units) : 0;

		object_init_ex(&zl, pdfium_ce_web_link);
		zend_object *l = Z_OBJ(zl);

		ZVAL_STR(slot(l, LINK_URL), pdfium_utf16le_to_utf8(buf, units_got(got)));
		ZVAL_LONG(slot(l, LINK_INDEX), start);
		ZVAL_LONG(slot(l, LINK_LENGTH), length);
		rects_set(slot(l, LINK_RECTS), tp, links, i, n);
		add_next_index_zval(return_value, &zl);
	}
	if (links) {
		FPDFLink_CloseWebLinks(links);
	}
	if (buf) {
		efree(buf);
	}
	return oom ? "Web links need" : NULL;
}

ZEND_METHOD(Pdfium_Page, text)
{
	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_TEXTPAGE tp = enter_text_page(pdfium_this_page(ZEND_THIS));

	if (!tp) {
		RETURN_THROWS();
	}
	const char *oom = text_of(tp, return_value);

	FPDFText_ClosePage(tp);
	leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_TextPage, text)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	leave_or_throw(return_value, text_of(this_text_page(ZEND_THIS), return_value));
}

ZEND_METHOD(Pdfium_TextPage, charCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = FPDFText_CountChars(this_text_page(ZEND_THIS));

	pdfium_leave();
	RETURN_LONG(MAX(count, 0));
}

ZEND_METHOD(Pdfium_TextPage, chars)
{
	zend_long start = 0, length = 0;
	bool rest = true;

	ZEND_PARSE_PARAMETERS_START(0, 2)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(start)
		Z_PARAM_LONG_OR_NULL(length, rest)
	ZEND_PARSE_PARAMETERS_END();

	if (!rest && length < 0) {
		zend_argument_value_error(2, "must not be negative");
		RETURN_THROWS();
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	FPDF_TEXTPAGE tp = this_text_page(ZEND_THIS);
	int count = MAX(FPDFText_CountChars(tp), 0);

	if (start < 0 || start > count) {
		pdfium_leave();
		zend_argument_value_error(1, "must be between 0 and the number of characters (%d)", count);
		RETURN_THROWS();
	}
	int to = rest || length > count - start ? count : (int) (start + length);

	leave_or_throw(return_value, chars_of(tp, (int) start, to, return_value));
}

ZEND_METHOD(Pdfium_TextPage, charIndexAt)
{
	double point[4] = {0};

	ZEND_PARSE_PARAMETERS_START(2, 4)
		Z_PARAM_DOUBLE(point[0])
		Z_PARAM_DOUBLE(point[1])
		Z_PARAM_OPTIONAL
		Z_PARAM_DOUBLE(point[2])
		Z_PARAM_DOUBLE(point[3])
	ZEND_PARSE_PARAMETERS_END();

	for (uint32_t i = 0; i < 4; i++) {
		if (!zend_finite(point[i]) || (i >= 2 && point[i] < 0)) {
			zend_argument_value_error(i + 1, i < 2 ? "must be finite" : "must be finite and not negative");
			RETURN_THROWS();
		}
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int index = FPDFText_GetCharIndexAtPos(this_text_page(ZEND_THIS), float_side(point[0]), float_side(point[1]),
		float_side(point[2]), float_side(point[3]));

	pdfium_leave();
	if (index < 0) {
		RETURN_NULL();
	}
	RETURN_LONG(index);
}

const char *pdfium_rect_fault(const zval *zr, double *sides)
{
	bool finite = true;

	for (int i = 0; i < RECT_SLOTS; i++) {
		zval *side = OBJ_PROP_NUM(Z_OBJ_P(zr), i);

		ZVAL_DEREF(side);
		if (Z_TYPE_P(side) != IS_DOUBLE) {
			return "every side set";
		}
		sides[i] = Z_DVAL_P(side);
		finite &= zend_finite(sides[i]) != 0;
	}
	if (!finite || sides[RECT_LEFT] > sides[RECT_RIGHT] || sides[RECT_BOTTOM] > sides[RECT_TOP]) {
		return "finite sides, with left not greater than right and bottom not greater than top";
	}
	return NULL;
}

ZEND_METHOD(Pdfium_TextPage, textIn)
{
	zval *zr;
	double sides[RECT_SLOTS];

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(zr, pdfium_ce_rect)
	ZEND_PARSE_PARAMETERS_END();

	const char *fault = pdfium_rect_fault(zr, sides);

	if (fault) {
		zend_argument_value_error(1, "must have %s", fault);
		RETURN_THROWS();
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	leave_or_throw(return_value, texts_in(this_text_page(ZEND_THIS), sides, 1, return_value));
}

ZEND_METHOD(Pdfium_TextPage, textInEach)
{
	HashTable *rects;
	zval *zr;
	zend_string *key;
	zend_ulong index;
	uint32_t r = 0;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_ARRAY_HT(rects)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t count = zend_hash_num_elements(rects);

	if (!pdfium_memory_fits((size_t) count * (RECT_SLOTS * sizeof(double) + 2 * sizeof(zval) + SPAN_BYTES))) {
		zend_throw_exception(pdfium_ce_memory_exception, "Text in the rectangle needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	double *sides = safe_emalloc(count, RECT_SLOTS * sizeof(*sides), 0);
	zval *texts = safe_emalloc(count, sizeof(*texts), 0);

	for (r = 0; r < count; r++) {
		ZVAL_UNDEF(&texts[r]);
	}
	r = 0;
	ZEND_HASH_FOREACH_VAL(rects, zr) {
		ZVAL_DEREF(zr);
		if (Z_TYPE_P(zr) != IS_OBJECT || Z_OBJCE_P(zr) != pdfium_ce_rect) {
			zend_argument_type_error(1, "must contain only %s objects", ZSTR_VAL(pdfium_ce_rect->name));
			break;
		}
		const char *fault = pdfium_rect_fault(zr, sides + RECT_SLOTS * r++);

		if (fault) {
			zend_argument_value_error(1, "must contain only rectangles with %s", fault);
			break;
		}
	} ZEND_HASH_FOREACH_END();

	if (!EG(exception) && pdfium_enter()) {
		leave_or_throw(return_value, texts_in(this_text_page(ZEND_THIS), sides, count, texts));
	}
	if (EG(exception)) {
		for (r = 0; r < count; r++) {
			zval_ptr_dtor(&texts[r]);
		}
	} else {
		r = 0;
		array_init_size(return_value, count);
		ZEND_HASH_FOREACH_KEY(rects, index, key) {
			if (key) {
				zend_hash_add_new(Z_ARRVAL_P(return_value), key, &texts[r++]);
			} else {
				zend_hash_index_add_new(Z_ARRVAL_P(return_value), index, &texts[r++]);
			}
		} ZEND_HASH_FOREACH_END();
	}
	efree(texts);
	efree(sides);
}

static const char *needle_units(const zend_string *needle, unsigned short *units, size_t *count)
{
	size_t pos = 0, n = 0;
	bool nul = false, invalid = false;

	while (pos < ZSTR_LEN(needle)) {
		zend_result status;
		unsigned int cp = php_next_utf8_char((const unsigned char *) ZSTR_VAL(needle), ZSTR_LEN(needle), &pos, &status);

		invalid |= status != SUCCESS;
		nul |= status == SUCCESS && !cp;
		if (cp >= 0x10000) {
			if (units) {
				units[n] = (unsigned short) (0xD800 | ((cp - 0x10000) >> 10));
			}
			n++;
			cp = 0xDC00 | ((cp - 0x10000) & 0x3FF);
		}
		if (units) {
			units[n] = (unsigned short) cp;
		}
		n++;
	}
	*count = n;
	return nul ? "must not contain any null bytes" : invalid ? "must be valid UTF-8" : NULL;
}

ZEND_METHOD(Pdfium_TextPage, search)
{
	zend_string *needle;
	bool match_case = false, whole_word = false, overlapping = false;

	ZEND_PARSE_PARAMETERS_START(1, 4)
		Z_PARAM_STR(needle)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(match_case)
		Z_PARAM_BOOL(whole_word)
		Z_PARAM_BOOL(overlapping)
	ZEND_PARSE_PARAMETERS_END();

	if (!ZSTR_LEN(needle)) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	size_t count;
	const char *bad = needle_units(needle, NULL, &count);

	if (bad) {
		zend_argument_value_error(1, "%s", bad);
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits(count * (sizeof(unsigned short) + sizeof(size_t)))) {
		zend_throw_exception(pdfium_ce_memory_exception, "The needle needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	unsigned short *units = safe_emalloc(count, sizeof(*units), 0);

	needle_units(needle, units, &count);
	if (pdfium_enter()) {
		leave_or_throw(return_value, search_of(this_text_page(ZEND_THIS), units, count, match_case, whole_word, overlapping,
			return_value));
	}
	efree(units);
}

ZEND_METHOD(Pdfium_TextPage, webLinks)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	leave_or_throw(return_value, web_links_of(this_text_page(ZEND_THIS), return_value));
}

ZEND_METHOD(Pdfium_TextPage, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Page, textPage)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);

	object_init_ex(return_value, pdfium_ce_text_page);
	pdfium_text_page *t = pdfium_text_page_from_obj(Z_OBJ_P(return_value));

	t->ph = ph;
	pdfium_page_handle_addref(ph);
	t->tp = enter_text_page(ph);
	if (!t->tp) {
		zval_ptr_dtor(return_value);
		ZVAL_NULL(return_value);
		RETURN_THROWS();
	}
	pdfium_leave();
}

void pdfium_text_page_free_obj(zend_object *obj)
{
	pdfium_text_page *t = pdfium_text_page_from_obj(obj);

	if (t->tp) {
		pdfium_enter_wait();
		FPDFText_ClosePage(t->tp);
		pdfium_leave();
	}
	if (t->ph) {
		pdfium_page_handle_release(t->ph);
	}
	zend_object_std_dtor(obj);
}
