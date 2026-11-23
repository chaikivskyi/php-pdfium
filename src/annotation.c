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

static const char *point_read(const zval *zp, FS_POINTF *out)
{
	double coords[2];
	const char *fault = pdfium_coords_fault(zp, coords, 2);

	if (fault) {
		return fault;
	}
	out->x = pdfium_float_side(coords[POINT_X]);
	out->y = pdfium_float_side(coords[POINT_Y]);
	return NULL;
}

static const char *quad_read(const zval *zq, FS_QUADPOINTSF *out)
{
	float *corners[] = {&out->x1, &out->y1, &out->x2, &out->y2, &out->x3, &out->y3, &out->x4, &out->y4};
	double coords[8];
	const char *fault = pdfium_coords_fault(zq, coords, 8);

	if (fault) {
		return fault;
	}
	for (int c = 0; c < 8; c++) {
		*corners[c] = pdfium_float_side(coords[c]);
	}
	return NULL;
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

	if (!pdfium_page_handle_enter(a->ph)) {
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

FPDF_ATTACHMENT pdfium_annotation_file_enter(zend_object *annotation)
{
	pdfium_annotation *a = annotation_enter(annotation);

	if (!a) {
		return NULL;
	}
	FPDF_ATTACHMENT att = FPDFAnnot_GetFileAttachment(a->annot);

	if (!att) {
		pdfium_leave();
		zend_throw_exception(pdfium_ce_exception, "Cannot read the annotation's file", 0);
	}
	return att;
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
	if (!pdfium_page_enter(ZEND_THIS)) {
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
	if (!pdfium_page_enter(ZEND_THIS)) {
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

	if (!pdfium_page_enter(ZEND_THIS)) {
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

static FPDF_ANNOTATION_SUBTYPE type_of(const zend_string *name)
{
	for (size_t i = FPDF_ANNOT_UNKNOWN + 1; i < sizeof(type_names) / sizeof(type_names[0]); i++) {
		size_t len = strlen(type_names[i]);

		if (ZSTR_LEN(name) == len && !memcmp(ZSTR_VAL(name), type_names[i], len)) {
			return (FPDF_ANNOTATION_SUBTYPE) i;
		}
	}
	return FPDF_ANNOT_UNKNOWN;
}

static const char *rect_read(const zval *zr, FS_RECTF *out)
{
	double sides[RECT_SLOTS];
	const char *fault = pdfium_rect_fault(zr, sides);

	if (fault) {
		return fault;
	}
	out->left = pdfium_float_side(sides[RECT_LEFT]);
	out->top = pdfium_float_side(sides[RECT_TOP]);
	out->right = pdfium_float_side(sides[RECT_RIGHT]);
	out->bottom = pdfium_float_side(sides[RECT_BOTTOM]);
	return NULL;
}

static bool annotation_remove(pdfium_page_handle *ph, int index)
{
	FPDF_ANNOTATION annot = FPDFPage_GetAnnot(ph->page, index);
	FPDF_ANNOTATION_SUBTYPE subtype = FPDF_ANNOT_UNKNOWN;

	if (annot) {
		subtype = FPDFAnnot_GetSubtype(annot);
		FPDFPage_CloseAnnot(annot);
	}
	if (!FPDFPage_RemoveAnnot(ph->page, index)) {
		return false;
	}
	ph->h->annotation_removals++;
	if (subtype == FPDF_ANNOT_WIDGET) {
		ph->h->form_changes++;
	}
	return true;
}

ZEND_METHOD(Pdfium_Page, addAnnotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zend_string *name;
	zval *zr;
	FS_RECTF r;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_STR(name)
		Z_PARAM_OBJECT_OF_CLASS(zr, pdfium_ce_rect)
	ZEND_PARSE_PARAMETERS_END();

	FPDF_ANNOTATION_SUBTYPE subtype = type_of(name);

	if (subtype == FPDF_ANNOT_UNKNOWN) {
		zend_argument_value_error(1, "must be an annotation type name, such as \"Highlight\"");
		RETURN_THROWS();
	}
	const char *fault = rect_read(zr, &r);

	if (fault) {
		zend_argument_value_error(2, "must have %s", fault);
		RETURN_THROWS();
	}
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	if (!FPDFAnnot_IsSupportedSubtype(subtype)) {
		pdfium_leave();
		zend_throw_exception_ex(pdfium_ce_unsupported_exception, 0,
			"PDFium cannot create %s annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, "
			"Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment", type_name(subtype));
		RETURN_THROWS();
	}
	FPDF_ANNOTATION annot = FPDFPage_CreateAnnot(ph->page, subtype);

	if (annot && !FPDFAnnot_SetRect(annot, &r)) {
		int index = FPDFPage_GetAnnotIndex(ph->page, annot);

		if (index >= 0) {
			annotation_remove(ph, index);
		}
		FPDFPage_CloseAnnot(annot);
		annot = NULL;
	}
	pdfium_leave();
	if (!annot) {
		zend_throw_exception(pdfium_ce_exception, "Cannot add the annotation", 0);
		RETURN_THROWS();
	}
	RETURN_OBJ(annotation_new(ph, annot));
}

ZEND_METHOD(Pdfium_Page, removeAnnotation)
{
	pdfium_page_handle *ph = pdfium_this_page(ZEND_THIS);
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	int count = annotation_count(ph->page);
	bool in_range = index >= 0 && index < count;
	bool removed = in_range && annotation_remove(ph, (int) index);

	pdfium_leave();
	if (!in_range) {
		annotation_index_error(count);
		RETURN_THROWS();
	}
	if (!removed) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot remove annotation %d", (int) index);
		RETURN_THROWS();
	}
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

static bool c_string_valid(const zend_string *s)
{
	if (!ZSTR_LEN(s)) {
		zend_argument_value_error(1, "must not be empty");
		return false;
	}
	if (memchr(ZSTR_VAL(s), '\0', ZSTR_LEN(s))) {
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

	if (!c_string_valid(*key)) {
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

ZEND_METHOD(Pdfium_Annotation, linkedAnnotation)
{
	zend_string *key;
	pdfium_annotation *a = keyed_enter(INTERNAL_FUNCTION_PARAM_PASSTHRU, &key);

	if (!a) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION linked = FPDFAnnot_GetLinkedAnnot(a->annot, ZSTR_VAL(key));

	if (linked && FPDFPage_GetAnnotIndex(a->ph->page, linked) < 0) {
		FPDFPage_CloseAnnot(linked);
		linked = NULL;
	}
	pdfium_leave();
	if (!linked) {
		RETURN_NULL();
	}
	RETURN_OBJ(annotation_new(a->ph, linked));
}

ZEND_METHOD(Pdfium_Annotation, link)
{
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	FPDF_LINK link = FPDFAnnot_GetLink(a->annot);

	if (link && !pdfium_link_build(return_value, a->ph->h->doc, link)) {
		oom = "The link needs";
	}
	pdfium_leave_or_throw(return_value, oom);
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

static pdfium_annotation *edit_enter(zval *self, FPDF_ANNOTATION_SUBTYPE *before)
{
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(self));

	*before = a ? FPDFAnnot_GetSubtype(a->annot) : FPDF_ANNOT_UNKNOWN;
	return a;
}

static bool edit_done(pdfium_annotation *a, FPDF_ANNOTATION_SUBTYPE before, bool done, const char *refusal)
{
	if (done && before == FPDF_ANNOT_WIDGET) {
		a->ph->h->form_changes++;
	}
	pdfium_leave();
	if (!done) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, refusal, type_name(before));
	}
	return done;
}

ZEND_METHOD(Pdfium_Annotation, setRect)
{
	zval *zr;
	FS_RECTF r;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(zr, pdfium_ce_rect)
	ZEND_PARSE_PARAMETERS_END();

	const char *fault = rect_read(zr, &r);

	if (fault) {
		zend_argument_value_error(1, "must have %s", fault);
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_SetRect(a->annot, &r), "Cannot set the rectangle of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setFlags)
{
	zend_long flags;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(flags)
	ZEND_PARSE_PARAMETERS_END();

	if (flags < 0 || (uint64_t) flags > UINT32_MAX) {
		zend_argument_value_error(1, "must be between 0 and 4294967295");
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_SetFlags(a->annot, (int) (uint32_t) flags), "Cannot set the flags of a %s annotation")) {
		RETURN_THROWS();
	}
}

static void annotation_set_color(INTERNAL_FUNCTION_PARAMETERS, FPDFANNOT_COLORTYPE type)
{
	zend_long rgba[COLOR_SLOTS] = {0, 0, 0, 255};

	ZEND_PARSE_PARAMETERS_START(3, 4)
		Z_PARAM_LONG(rgba[0])
		Z_PARAM_LONG(rgba[1])
		Z_PARAM_LONG(rgba[2])
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(rgba[3])
	ZEND_PARSE_PARAMETERS_END();

	for (uint32_t i = 0; i < COLOR_SLOTS; i++) {
		if (rgba[i] < 0 || rgba[i] > 255) {
			zend_argument_value_error(i + 1, "must be between 0 and 255");
			RETURN_THROWS();
		}
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a) {
		RETURN_THROWS();
	}
	bool done = FPDFAnnot_SetColor(a->annot, type, (unsigned int) rgba[0], (unsigned int) rgba[1], (unsigned int) rgba[2],
		(unsigned int) rgba[3]);
	const char *refusal = !done && FPDFAnnot_HasKey(a->annot, "AP")
		? "Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first"
		: "Cannot set the colour of a %s annotation";

	if (!edit_done(a, before, done, refusal)) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setColor)
{
	annotation_set_color(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFANNOT_COLORTYPE_Color);
}

ZEND_METHOD(Pdfium_Annotation, setInteriorColor)
{
	annotation_set_color(INTERNAL_FUNCTION_PARAM_PASSTHRU, FPDFANNOT_COLORTYPE_InteriorColor);
}

ZEND_METHOD(Pdfium_Annotation, setBorder)
{
	double values[3];

	ZEND_PARSE_PARAMETERS_START(3, 3)
		Z_PARAM_DOUBLE(values[BORDER_HORIZONTAL_RADIUS])
		Z_PARAM_DOUBLE(values[BORDER_VERTICAL_RADIUS])
		Z_PARAM_DOUBLE(values[BORDER_WIDTH])
	ZEND_PARSE_PARAMETERS_END();

	for (uint32_t i = 0; i < 3; i++) {
		if (!zend_finite(values[i]) || values[i] < 0) {
			zend_argument_value_error(i + 1, "must be finite and not negative");
			RETURN_THROWS();
		}
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_SetBorder(a->annot, pdfium_float_side(values[BORDER_HORIZONTAL_RADIUS]),
			pdfium_float_side(values[BORDER_VERTICAL_RADIUS]), pdfium_float_side(values[BORDER_WIDTH])),
			"Cannot set the border of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, addQuadPoints)
{
	zval *zq;
	FS_QUADPOINTSF q;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(zq, pdfium_ce_quad)
	ZEND_PARSE_PARAMETERS_END();

	const char *fault = quad_read(zq, &q);

	if (fault) {
		zend_argument_value_error(1, "must have %s", fault);
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_AppendAttachmentPoints(a->annot, &q), "Cannot add quad points to a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setQuadPoints)
{
	zend_long index;
	zval *zq;
	FS_QUADPOINTSF q;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_LONG(index)
		Z_PARAM_OBJECT_OF_CLASS(zq, pdfium_ce_quad)
	ZEND_PARSE_PARAMETERS_END();

	const char *fault = quad_read(zq, &q);

	if (fault) {
		zend_argument_value_error(2, "must have %s", fault);
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a) {
		RETURN_THROWS();
	}
	bool has = FPDFAnnot_HasAttachmentPoints(a->annot);
	size_t count = has ? FPDFAnnot_CountAttachmentPoints(a->annot) : 0;

	if (has && (index < 0 || (size_t) index >= count)) {
		pdfium_leave();
		pdfium_index_error(1, (int) MIN(count, INT_MAX), "an index of the quad points", "the annotation has none");
		RETURN_THROWS();
	}
	bool done = has && FPDFAnnot_SetAttachmentPoints(a->annot, (size_t) index, &q);

	if (!edit_done(a, before, done, "Cannot set the quad points of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, addInkStroke)
{
	HashTable *ht;
	zval *zp;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_ARRAY_HT(ht)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t count = zend_hash_num_elements(ht);

	if (!count) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	ZEND_HASH_FOREACH_VAL(ht, zp) {
		ZVAL_DEREF(zp);
		if (Z_TYPE_P(zp) != IS_OBJECT || Z_OBJCE_P(zp) != pdfium_ce_point) {
			zend_argument_type_error(1, "must be a list of Pdfium\\Point");
			RETURN_THROWS();
		}
	} ZEND_HASH_FOREACH_END();
	if (!pdfium_fits_or_throw((size_t) count * sizeof(FS_POINTF), "The ink strokes need")) {
		RETURN_THROWS();
	}
	FS_POINTF *points = safe_emalloc(count, sizeof(FS_POINTF), 0);
	size_t n = 0;

	ZEND_HASH_FOREACH_VAL(ht, zp) {
		ZVAL_DEREF(zp);
		const char *fault = point_read(zp, &points[n++]);

		if (fault) {
			efree(points);
			zend_argument_value_error(1, "must have %s", fault);
			RETURN_THROWS();
		}
	} ZEND_HASH_FOREACH_END();
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);
	int index = a ? FPDFAnnot_AddInkStroke(a->annot, points, n) : -1;
	bool done = a && edit_done(a, before, index >= 0, "Cannot add an ink stroke to a %s annotation");

	efree(points);
	if (!done) {
		RETURN_THROWS();
	}
	RETURN_LONG(index);
}

ZEND_METHOD(Pdfium_Annotation, removeInkStrokes)
{
	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_RemoveInkList(a->annot), "Cannot remove the ink strokes of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setStringValue)
{
	zend_string *key, *value;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_STR(key)
		Z_PARAM_STR(value)
	ZEND_PARSE_PARAMETERS_END();

	if (!c_string_valid(key)) {
		RETURN_THROWS();
	}
	unsigned short *wvalue = pdfium_utf16_arg(value, 2, "The annotation value needs");

	if (!wvalue) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);
	bool done = false;

	if (a) {
		done = edit_done(a, before, FPDFAnnot_SetStringValue(a->annot, ZSTR_VAL(key), wvalue),
			"Cannot set the value of a %s annotation");
	}
	efree(wvalue);
	if (!done) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setAppearance)
{
	zend_string *stream;
	zend_long mode = FPDF_ANNOT_APPEARANCEMODE_NORMAL;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_STR(stream)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(mode)
	ZEND_PARSE_PARAMETERS_END();

	if (!appearance_mode_valid(mode, 2)) {
		RETURN_THROWS();
	}
	unsigned short *wstream = pdfium_utf16_arg(stream, 1, "The appearance needs");

	if (!wstream) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);
	bool done = a && edit_done(a, before, FPDFAnnot_SetAP(a->annot, (FPDF_ANNOT_APPEARANCEMODE) mode, wstream),
		"Cannot set the appearance of a %s annotation");

	efree(wstream);
	if (!done) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, clearAppearance)
{
	zend_long mode = FPDF_ANNOT_APPEARANCEMODE_NORMAL;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(mode)
	ZEND_PARSE_PARAMETERS_END();

	if (!appearance_mode_valid(mode, 1)) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_SetAP(a->annot, (FPDF_ANNOT_APPEARANCEMODE) mode, NULL),
			"Cannot set the appearance of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, setUri)
{
	zend_string *uri;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(uri)
	ZEND_PARSE_PARAMETERS_END();

	if (!c_string_valid(uri)) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);

	if (!a || !edit_done(a, before, FPDFAnnot_SetURI(a->annot, ZSTR_VAL(uri)), "Cannot set the URI of a %s annotation")) {
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Annotation, attachment)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter(Z_OBJ_P(ZEND_THIS));

	if (!a) {
		RETURN_THROWS();
	}
	bool has = FPDFAnnot_GetFileAttachment(a->annot) != NULL;

	pdfium_leave();
	if (!has) {
		RETURN_NULL();
	}
	RETURN_OBJ(pdfium_attachment_for_annotation(Z_OBJ_P(ZEND_THIS)));
}

ZEND_METHOD(Pdfium_Annotation, setAttachment)
{
	zend_string *name, *contents, *description = NULL;
	unsigned short *wname, *wdesc;

	ZEND_PARSE_PARAMETERS_START(2, 3)
		Z_PARAM_STR(name)
		Z_PARAM_STR(contents)
		Z_PARAM_OPTIONAL
		Z_PARAM_STR_OR_NULL(description)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_attachment_args(name, contents, description, &wname, &wdesc)) {
		RETURN_THROWS();
	}
	FPDF_ANNOTATION_SUBTYPE before;
	pdfium_annotation *a = edit_enter(ZEND_THIS, &before);
	bool added = false, stored = false;

	if (a) {
		FPDF_ATTACHMENT att = FPDFAnnot_AddFileAttachment(a->annot, wname);

		if (att) {
			stored = pdfium_attachment_fill(att, a->ph->h->doc, contents, wdesc);
		}
		added = edit_done(a, before, att != NULL, "Cannot attach a file to a %s annotation");
	}
	pdfium_attachment_args_free(wname, wdesc);
	if (!added) {
		RETURN_THROWS();
	}
	if (!stored) {
		zend_throw_exception(pdfium_ce_exception,
			"Cannot store the attachment's contents; the annotation's previous file is gone", 0);
		RETURN_THROWS();
	}
	RETURN_OBJ(pdfium_attachment_for_annotation(Z_OBJ_P(ZEND_THIS)));
}

ZEND_METHOD(Pdfium_Annotation, remove)
{
	int index;

	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_annotation *a = annotation_enter_at(Z_OBJ_P(ZEND_THIS), &index);

	if (!a) {
		RETURN_THROWS();
	}
	bool removed = annotation_remove(a->ph, index);

	a->removed = removed;
	pdfium_leave();
	if (!removed) {
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot remove annotation %d", index);
		RETURN_THROWS();
	}
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
