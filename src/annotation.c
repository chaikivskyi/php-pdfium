#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_annot.h>
#include "zend_exceptions.h"

#define POINT_BYTES 128
#define STROKE_BYTES 128

enum { BORDER_HORIZONTAL_RADIUS, BORDER_VERTICAL_RADIUS, BORDER_WIDTH };
enum { POINT_X, POINT_Y };

typedef struct { FPDF_ANNOTATION annot; FPDF_BYTESTRING key; } value_source;
typedef struct { FPDF_ANNOTATION annot; FPDF_ANNOT_APPEARANCEMODE mode; } appearance_source;

static const char *const value_type_names[] = {
	"Unknown", "Boolean", "Number", "String", "Name", "Array", "Dictionary", "Stream", "Null", "Reference",
};

static const char *const type_names[] = {
	"Unknown", "Text", "Link", "FreeText", "Line", "Square", "Circle", "Polygon", "PolyLine", "Highlight",
	"Underline", "Squiggly", "StrikeOut", "Stamp", "Caret", "Ink", "Popup", "FileAttachment", "Sound", "Movie",
	"Widget", "Screen", "PrinterMark", "TrapNet", "Watermark", "3D", "RichMedia", "XFAWidget", "Redact",
};

static const char *name_at(const char *const *names, size_t count, int index)
{
	return index >= 0 && (size_t) index < count ? names[index] : names[0];
}

static const char *type_name(FPDF_ANNOTATION_SUBTYPE subtype)
{
	return name_at(type_names, sizeof(type_names) / sizeof(type_names[0]), subtype);
}

ZEND_METHOD(Pdfium_Annotation, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Border, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Point, __construct)
{
	static const char *const names[] = {"x", "y"};
	double coords[2];

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_DOUBLE(coords[POINT_X])
		Z_PARAM_DOUBLE(coords[POINT_Y])
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_coords_construct(ZEND_THIS, pdfium_ce_point, names, coords, 2)) {
		RETURN_THROWS();
	}
}

bool pdfium_annotation_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_border, "horizontalRadius verticalRadius width")
		&& pdfium_slots_named(pdfium_ce_point, "x y");
}

static void point_init(zval *zv, float x, float y)
{
	object_init_ex(zv, pdfium_ce_point);
	ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(zv), POINT_X), pdfium_points(x));
	ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(zv), POINT_Y), pdfium_points(y));
}

static void points_init(zval *zv, const FS_POINTF *points, unsigned long count)
{
	array_init_size(zv, (uint32_t) count);
	for (unsigned long i = 0; i < count; i++) {
		zval zp;

		point_init(&zp, points[i].x, points[i].y);
		add_next_index_zval(zv, &zp);
	}
}

static zend_object *annotation_new(pdfium_page_handle *ph, FPDF_ANNOTATION annot)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_annotation);
	pdfium_annotation *a = pdfium_annotation_from_obj(Z_OBJ(zv));
	a->ph = ph;
	a->annot = annot;
	a->removals = ph->h->annotation_removals;
	pdfium_page_handle_addref(ph);
	return Z_OBJ(zv);
}

static pdfium_annotation *annotation_enter_at(zend_object *obj, int *index)
{
	pdfium_annotation *a = pdfium_annotation_from_obj(obj);

	if (!pdfium_enter()) {
		return NULL;
	}
	if (!a->removed && (index || a->removals != a->ph->h->annotation_removals)) {
		int at = FPDFPage_GetAnnotIndex(a->ph->page, a->annot);

		a->removals = a->ph->h->annotation_removals;
		a->removed = at < 0;
		if (index) {
			*index = at;
		}
	}
	if (a->removed) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "The annotation was removed from its page", 0);
		return NULL;
	}
	return a;
}

static pdfium_annotation *annotation_enter(zend_object *obj)
{
	return annotation_enter_at(obj, NULL);
}

static int annotation_count(FPDF_PAGE page)
{
	int count = FPDFPage_GetAnnotCount(page);

	return count > 0 ? count : 0;
}

static void annotation_index_error(int count)
{
	pdfium_index_error(1, count, "an annotation index", "the page has no annotations");
}

ZEND_METHOD(Pdfium_Page, annotationCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(pdfium_this_page(ZEND_THIS)->page);

	pdfium_leave();
	RETURN_LONG(count);
}

ZEND_METHOD(Pdfium_Page, annotations)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(ph->page);

	if (!pdfium_memory_fits((size_t) count * (sizeof(pdfium_annotation) + sizeof(Bucket)))) {
		oom = "The annotations need";
	} else {
		array_init_size(return_value, (uint32_t) count);
		for (int i = 0; i < count; i++) {
			FPDF_ANNOTATION annot = FPDFPage_GetAnnot(ph->page, i);

			if (annot) {
				zval zv;

				ZVAL_OBJ(&zv, annotation_new(ph, annot));
				add_index_zval(return_value, i, &zv);
			}
		}
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Page, annotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = annotation_count(ph->page);
	bool in_range = index >= 0 && index < count;
	FPDF_ANNOTATION annot = in_range ? FPDFPage_GetAnnot(ph->page, (int) index) : NULL;

	pdfium_leave();
	if (!in_range) {
		annotation_index_error(count);
		RETURN_THROWS();
	}
	if (!annot) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot read annotation %d", (int) index);
		RETURN_THROWS();
	}
	RETURN_OBJ(annotation_new(ph, annot));
}

ZEND_METHOD(Pdfium_Annotation, index)
{
	int index;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!annotation_enter_at(Z_OBJ_P(ZEND_THIS), &index)) {
		RETURN_THROWS();
	}
	pdfium_leave();
	RETURN_LONG(index);
}

ZEND_METHOD(Pdfium_Annotation, type)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE subtype = FPDFAnnot_GetSubtype(a->annot);

	pdfium_leave();
	RETURN_STRING(type_name(subtype));
}

ZEND_METHOD(Pdfium_Annotation, rect)
{
	FS_RECTF r;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetRect(a->annot, &r);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	pdfium_rect_init(return_value, r.left, r.bottom, r.right, r.top);
}

ZEND_METHOD(Pdfium_Annotation, flags)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	int flags = FPDFAnnot_GetFlags(a->annot);

	pdfium_leave();
	RETURN_LONG((uint32_t) flags);
}

static void annotation_color(INTERNAL_FUNCTION_PARAMETERS, FPDFANNOT_COLORTYPE type)
{
	unsigned int rgba[COLOR_SLOTS];
	zend_long channels[COLOR_SLOTS];

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetColor(a->annot, type, &rgba[0], &rgba[1], &rgba[2], &rgba[3]);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	for (int i = 0; i < COLOR_SLOTS; i++) {
		channels[i] = rgba[i] > INT_MAX ? 0 : MIN(rgba[i], 255);
	}
	pdfium_color_init(return_value, channels);
}

ZEND_METHOD(Pdfium_Annotation, color)
{
	annotation_color(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFANNOT_COLORTYPE_Color);
}

ZEND_METHOD(Pdfium_Annotation, interiorColor)
{
	annotation_color(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFANNOT_COLORTYPE_InteriorColor);
}

ZEND_METHOD(Pdfium_Annotation, border)
{
	float horizontal, vertical, width;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetBorder(a->annot, &horizontal, &vertical, &width);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	object_init_ex(return_value, pdfium_ce_border);
	ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(return_value), BORDER_HORIZONTAL_RADIUS), pdfium_points(horizontal));
	ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(return_value), BORDER_VERTICAL_RADIUS), pdfium_points(vertical));
	ZVAL_DOUBLE(pdfium_slot(Z_OBJ_P(return_value), BORDER_WIDTH), pdfium_points(width));
}

ZEND_METHOD(Pdfium_Annotation, quadPoints)
{
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	size_t count = FPDFAnnot_CountAttachmentPoints(a->annot);

	if (!pdfium_memory_fits(count * PDFIUM_QUAD_BYTES)) {
		oom = "The quad points need";
	} else {
		array_init_size(return_value, (uint32_t) count);
		for (size_t i = 0; i < count; i++) {
			FS_QUADPOINTSF q;
			zval zq;

			if (FPDFAnnot_GetAttachmentPoints(a->annot, i, &q)) {
				pdfium_quad_init(&zq, &q);
				add_next_index_zval(return_value, &zq);
			}
		}
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Annotation, vertices)
{
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	unsigned long count = FPDFAnnot_GetVertices(a->annot, NULL, 0);

	if (!pdfium_memory_fits((size_t) count * (POINT_BYTES + sizeof(FS_POINTF)))) {
		oom = "The vertices need";
	} else {
		FS_POINTF *points = safe_emalloc(count, sizeof(FS_POINTF), 0);
		unsigned long got = FPDFAnnot_GetVertices(a->annot, points, count);

		points_init(return_value, points, got <= count ? got : 0);
		efree(points);
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Annotation, line)
{
	FS_POINTF points[2];

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetLine(a->annot, &points[0], &points[1]);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	points_init(return_value, points, 2);
}

ZEND_METHOD(Pdfium_Annotation, inkStrokes)
{
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	unsigned long strokes = FPDFAnnot_GetInkListCount(a->annot), longest = 0;
	size_t total = 0;

	for (unsigned long i = 0; i < strokes; i++) {
		unsigned long count = FPDFAnnot_GetInkListPath(a->annot, i, NULL, 0);

		total += count;
		longest = MAX(longest, count);
	}
	if (!pdfium_memory_fits((size_t) strokes * STROKE_BYTES + total * POINT_BYTES + (size_t) longest * sizeof(FS_POINTF))) {
		oom = "The ink strokes need";
	} else {
		FS_POINTF *points = safe_emalloc(longest, sizeof(FS_POINTF), 0);

		array_init_size(return_value, (uint32_t) strokes);
		for (unsigned long i = 0; i < strokes; i++) {
			unsigned long got = FPDFAnnot_GetInkListPath(a->annot, i, points, longest);
			zval zs;

			points_init(&zs, points, got <= longest ? got : 0);
			add_next_index_zval(return_value, &zs);
		}
		efree(points);
	}
	pdfium_leave_or_throw(return_value, oom);
}

static bool key_valid(const zend_string *key)
{
	if (!ZSTR_LEN(key)) {
		zend_argument_value_error(1, "must not be empty");
		return false;
	}
	if (memchr(ZSTR_VAL(key), '\0', ZSTR_LEN(key))) {
		zend_argument_value_error(1, "must not contain a null byte");
		return false;
	}
	return true;
}

static pdfium_annotation *keyed_enter(INTERNAL_FUNCTION_PARAMETERS, zend_string **key)
{
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(*key)
	ZEND_PARSE_PARAMETERS_END_EX(return NULL);

	if (!key_valid(*key)) {
		return NULL;
	}
	return annotation_enter(Z_OBJ_P(ZEND_THIS));
}

ZEND_METHOD(Pdfium_Annotation, hasKey)
{
	zend_string *key;
	pdfium_annotation *a = keyed_enter(INTERNAL_FUNCTION_PARAM_PASSTHRU, &key);

	if (!a) {
		RETURN_THROWS();
	}
	bool has = FPDFAnnot_HasKey(a->annot, ZSTR_VAL(key));

	pdfium_leave();
	RETURN_BOOL(has);
}

ZEND_METHOD(Pdfium_Annotation, valueType)
{
	zend_string *key;
	pdfium_annotation *a = keyed_enter(INTERNAL_FUNCTION_PARAM_PASSTHRU, &key);

	if (!a) {
		RETURN_THROWS();
	}
	FPDF_OBJECT_TYPE type = FPDFAnnot_GetValueType(a->annot, ZSTR_VAL(key));

	pdfium_leave();
	RETURN_STRING(name_at(value_type_names, sizeof(value_type_names) / sizeof(value_type_names[0]), type));
}

static void utf16_return(zval *return_value, pdfium_utf16le_getter get, void *source, const char *what)
{
	size_t used = 0;
	bool oom = false;
	zend_string *s = pdfium_utf16le_fetch_under_limit(get, source, &used, &oom);

	if (s) {
		RETVAL_STR(s);
	}
	pdfium_leave_or_throw(return_value, oom ? what : NULL);
}

static unsigned long value_get(void *source, unsigned short *buf, unsigned long len)
{
	value_source *v = source;

	return FPDFAnnot_GetStringValue(v->annot, v->key, buf, len);
}

ZEND_METHOD(Pdfium_Annotation, stringValue)
{
	zend_string *key;
	pdfium_annotation *a = keyed_enter(INTERNAL_FUNCTION_PARAM_PASSTHRU, &key);

	if (!a) {
		RETURN_THROWS();
	}
	value_source v = {a->annot, ZSTR_VAL(key)};

	utf16_return(return_value, value_get, &v, "The annotation value needs");
}

ZEND_METHOD(Pdfium_Annotation, numberValue)
{
	zend_string *key;
	float value;
	pdfium_annotation *a = keyed_enter(INTERNAL_FUNCTION_PARAM_PASSTHRU, &key);

	if (!a) {
		RETURN_THROWS();
	}
	bool got = FPDFAnnot_GetNumberValue(a->annot, ZSTR_VAL(key), &value);

	pdfium_leave();
	if (!got) {
		RETURN_NULL();
	}
	RETURN_DOUBLE(pdfium_points(value));
}

static bool appearance_mode_valid(zend_long mode, uint32_t arg)
{
	if (mode < 0 || mode >= FPDF_ANNOT_APPEARANCEMODE_COUNT) {
		zend_argument_value_error(arg, "must be one of the Annotation::APPEARANCE_* constants");
		return false;
	}
	return true;
}

static unsigned long appearance_get(void *source, unsigned short *buf, unsigned long len)
{
	appearance_source *ap = source;

	return FPDFAnnot_GetAP(ap->annot, ap->mode, buf, len);
}

ZEND_METHOD(Pdfium_Annotation, appearance)
{
	zend_long mode = FPDF_ANNOT_APPEARANCEMODE_NORMAL;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(mode)
	ZEND_PARSE_PARAMETERS_END();

	if (!appearance_mode_valid(mode, 1)) {
		RETURN_THROWS();
	}
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	appearance_source ap = {a->annot, (FPDF_ANNOT_APPEARANCEMODE) mode};

	utf16_return(return_value, appearance_get, &ap, "The appearance needs");
}

void pdfium_annotation_free_obj(zend_object *obj)
{
	pdfium_annotation *a = pdfium_annotation_from_obj(obj);

	if (a->annot) {
		pdfium_enter_wait();
		FPDFPage_CloseAnnot(a->annot);
		pdfium_leave();
	}
	if (a->ph) {
		pdfium_page_handle_release(a->ph);
	}
	zend_object_std_dtor(obj);
}
