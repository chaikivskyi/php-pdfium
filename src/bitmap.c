#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_edit.h>
#include "zend_exceptions.h"
#include <math.h>
#include <setjmp.h>
#include <stdio.h>
#include <png.h>
#include <jpeglib.h>
#include <jerror.h>

#define PDFIUM_MAX_PIXELS 100000000
#define PDFIUM_JPEG_CHUNK 16384
#define PDFIUM_MAX_PAGE_SIDE (1 << 22)

ZEND_METHOD(Pdfium_Bitmap, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

static pdfium_bitmap *this_bitmap(zval *self)
{
	return pdfium_bitmap_from_obj(Z_OBJ_P(self));
}

static int pixels_for(double points, zend_long dpi)
{
	double px = round(points * (double) dpi / 72.0);

	return !(px >= 1) ? 1 : px > INT_MAX ? INT_MAX : (int) px;
}

static int channels_for(bool gray, bool alpha)
{
	return (gray ? 1 : 3) + alpha;
}

static unsigned char luma(const unsigned char *bgr)
{
	return (unsigned char) ((299 * bgr[2] + 587 * bgr[1] + 114 * bgr[0] + 500) / 1000);
}

static void *to_gray(unsigned char *pixels, size_t count, bool alpha)
{
	size_t step = 1 + alpha;

	for (size_t i = 0; i < count; i++) {
		pixels[i * step] = luma(pixels + i * 4);
		if (alpha) {
			pixels[i * step + 1] = pixels[i * 4 + 3];
		}
	}
	return erealloc(pixels, count * step);
}

static bool region_place(FPDF_PAGE page, const double *sides, zend_long dpi, int page_w, int page_h, int *w, int *h,
	int *x0, int *y0)
{
	FS_RECTF box;
	int ax = 0, ay = 0, bx = 0, by = 0, rotation;

	if (!FPDF_GetPageBoundingBox(page, &box) || sides[RECT_RIGHT] <= box.left || sides[RECT_LEFT] >= box.right
		|| sides[RECT_TOP] <= box.bottom || sides[RECT_BOTTOM] >= box.top) {
		return false;
	}
	*w = pixels_for(sides[RECT_RIGHT] - sides[RECT_LEFT], dpi);
	*h = pixels_for(sides[RECT_TOP] - sides[RECT_BOTTOM], dpi);
	rotation = FPDFPage_GetRotation(page);
	if (rotation == 1 || rotation == 3) {
		int swap = *w;

		*w = *h;
		*h = swap;
	}
	FPDF_PageToDevice(page, 0, 0, page_w, page_h, 0, sides[RECT_LEFT], sides[RECT_TOP], &ax, &ay);
	FPDF_PageToDevice(page, 0, 0, page_w, page_h, 0, sides[RECT_RIGHT], sides[RECT_BOTTOM], &bx, &by);
	*x0 = MIN(ax, bx);
	*y0 = MIN(ay, by);
	return true;
}

ZEND_METHOD(Pdfium_Page, render)
{
	pdfium_page_handle *p = pdfium_this_page(ZEND_THIS);
	zend_long dpi = 150;
	zval *region = NULL;
	double sides[RECT_SLOTS];
	bool transparent = false, annotations = true, grayscale = false;

	ZEND_PARSE_PARAMETERS_START(0, 5)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(dpi)
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(region, pdfium_ce_rect)
		Z_PARAM_BOOL(transparent)
		Z_PARAM_BOOL(annotations)
		Z_PARAM_BOOL(grayscale)
	ZEND_PARSE_PARAMETERS_END();

	if (dpi < 1 || dpi > 2400) {
		zend_argument_value_error(1, "must be between 1 and 2400");
		RETURN_THROWS();
	}
	const char *fault = region ? pdfium_rect_fault(region, sides) : NULL;

	if (region && !fault && (sides[RECT_LEFT] == sides[RECT_RIGHT] || sides[RECT_BOTTOM] == sides[RECT_TOP])) {
		fault = "a width and a height";
	}
	if (fault) {
		zend_argument_value_error(2, "must have %s", fault);
		RETURN_THROWS();
	}
	if (!pdfium_page_enter(ZEND_THIS)) {
		RETURN_THROWS();
	}
	int page_w = pixels_for(FPDF_GetPageWidthF(p->page), dpi);
	int page_h = pixels_for(FPDF_GetPageHeightF(p->page), dpi);
	int w = page_w, h = page_h, x0 = 0, y0 = 0;
	uint32_t size_arg = region ? 2 : 1;

	if (region) {
		if (page_w > PDFIUM_MAX_PAGE_SIDE || page_h > PDFIUM_MAX_PAGE_SIDE) {
			pdfium_leave();
			zend_argument_value_error(2, "needs the whole page at %dx%d pixels, above the %d-pixel limit per side "
				"for a region", page_w, page_h, PDFIUM_MAX_PAGE_SIDE);
			RETURN_THROWS();
		}
		if (!region_place(p->page, sides, dpi, page_w, page_h, &w, &h, &x0, &y0)) {
			pdfium_leave();
			zend_argument_value_error(2, "must overlap the page");
			RETURN_THROWS();
		}
	}
	if (w > JPEG_MAX_DIMENSION || h > JPEG_MAX_DIMENSION) {
		pdfium_leave();
		zend_argument_value_error(size_arg, "would render %dx%d pixels, above the %d-pixel limit per side", w, h,
			(int) JPEG_MAX_DIMENSION);
		RETURN_THROWS();
	}
	if ((double) w * h > PDFIUM_MAX_PIXELS) {
		pdfium_leave();
		zend_argument_value_error(size_arg, "would render %dx%d pixels, above the %d-pixel ceiling", w, h,
			PDFIUM_MAX_PIXELS);
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits((size_t) w * h * (4 + channels_for(grayscale, transparent)))) {
		pdfium_leave();
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
			"Rendering %dx%d pixels needs more memory than memory_limit leaves", w, h);
		RETURN_THROWS();
	}

	void *pixels = safe_emalloc((size_t) w * 4, h, 0);
	FPDF_BITMAP bitmap = FPDFBitmap_CreateEx(w, h, transparent ? FPDFBitmap_BGRA : FPDFBitmap_BGRx, pixels, w * 4);
	if (!bitmap) {
		pdfium_leave();
		efree(pixels);
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0, "Cannot allocate a %dx%d bitmap", w, h);
		RETURN_THROWS();
	}
	FPDF_FORMHANDLE form = annotations ? pdfium_page_form(p) : NULL;
	int flags = annotations ? FPDF_ANNOT : 0;
	FPDFBitmap_FillRect(bitmap, 0, 0, w, h, transparent ? 0x00000000 : 0xFFFFFFFF);
	FPDF_RenderPageBitmap(bitmap, p->page, -x0, -y0, page_w, page_h, 0, flags);
	if (form) {
		FPDF_FFLDraw(form, bitmap, p->page, -x0, -y0, page_w, page_h, 0, flags);
	}
	FPDFBitmap_Destroy(bitmap);
	pdfium_leave();

	if (grayscale) {
		pixels = to_gray(pixels, (size_t) w * h, transparent);
	}
	object_init_ex(return_value, pdfium_ce_bitmap);
	pdfium_bitmap *img = this_bitmap(return_value);
	img->pixels = pixels;
	img->width = w;
	img->height = h;
	img->alpha = transparent;
	img->gray = grayscale;
}

ZEND_METHOD(Pdfium_Bitmap, width)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(this_bitmap(ZEND_THIS)->width);
}

ZEND_METHOD(Pdfium_Bitmap, height)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(this_bitmap(ZEND_THIS)->height);
}

static unsigned char on_white(unsigned char c, unsigned char a)
{
	return (unsigned char) ((c * a + 255 * (255 - a) + 127) / 255);
}

static int out_channels(const pdfium_bitmap *img, bool keep_alpha)
{
	return channels_for(img->gray, keep_alpha && img->alpha);
}

static void row_out(const pdfium_bitmap *img, int y, unsigned char *out, bool keep_alpha)
{
	int step = img->gray ? 1 + img->alpha : 4;
	const unsigned char *src = img->pixels + (size_t) y * img->width * step;
	bool composite = img->alpha && !keep_alpha;

	for (int x = img->width; x > 0; x--, src += step) {
		unsigned char a = !img->alpha ? 255 : img->gray ? src[1] : src[3];

		if (img->gray) {
			*out++ = composite ? on_white(src[0], a) : src[0];
		} else {
			*out++ = composite ? on_white(src[2], a) : src[2];
			*out++ = composite ? on_white(src[1], a) : src[1];
			*out++ = composite ? on_white(src[0], a) : src[0];
		}
		if (keep_alpha && img->alpha) {
			*out++ = a;
		}
	}
}

#define PDFIUM_PNG_MSG 200

static void png_on_error(png_structp png, png_const_charp msg)
{
	snprintf(png_get_error_ptr(png), PDFIUM_PNG_MSG, "%s", msg);
	png_longjmp(png, 1);
}

static void png_on_warning(png_structp png, png_const_charp msg)
{
}

static void png_on_write(png_structp png, png_bytep data, size_t len)
{
	if (!pdfium_buf_append(png_get_io_ptr(png), data, len)) {
		png_error(png, "memory_limit reached");
	}
}

static void png_on_flush(png_structp png)
{
}

static bool png_write(png_structp png, png_infop info, const pdfium_bitmap *img, pdfium_buf *out, unsigned char *row)
{
	if (setjmp(png_jmpbuf(png))) {
		return false;
	}
	png_set_write_fn(png, out, png_on_write, png_on_flush);
	png_set_IHDR(png, info, img->width, img->height, 8,
		(img->gray ? PNG_COLOR_TYPE_GRAY : PNG_COLOR_TYPE_RGB) | (img->alpha ? PNG_COLOR_MASK_ALPHA : 0),
		PNG_INTERLACE_NONE, PNG_COMPRESSION_TYPE_DEFAULT, PNG_FILTER_TYPE_DEFAULT);
	png_write_info(png, info);
	for (int y = 0, h = img->height; y < h; y++) {
		row_out(img, y, row, true);
		png_write_row(png, row);
	}
	png_write_end(png, info);
	return true;
}

static zend_string *encode_png(const pdfium_bitmap *img)
{
	char message[PDFIUM_PNG_MSG] = "out of memory";
	png_structp png = png_create_write_struct(PNG_LIBPNG_VER_STRING, message, png_on_error, png_on_warning);
	png_infop info = png ? png_create_info_struct(png) : NULL;
	pdfium_buf out = {0};
	bool ok = false;

	if (info) {
		unsigned char *row = safe_emalloc(img->width, out_channels(img, true), 0);

		ok = png_write(png, info, img, &out, row);
		efree(row);
	} else {
		out.oom = true;
	}
	png_destroy_write_struct(&png, &info);
	if (!ok) {
		smart_str_free(&out.s);
		zend_throw_exception_ex(out.oom ? pdfium_ce_memory_exception : pdfium_ce_exception, 0, "Cannot encode PNG: %s", message);
		return NULL;
	}
	return smart_str_extract(&out.s);
}

typedef struct {
	struct jpeg_error_mgr pub;
	jmp_buf jmp;
	char message[JMSG_LENGTH_MAX];
} jpeg_err;

typedef struct {
	struct jpeg_destination_mgr pub;
	pdfium_buf *out;
	JOCTET chunk[PDFIUM_JPEG_CHUNK];
} jpeg_dest;

static void jpeg_on_error(j_common_ptr cinfo)
{
	jpeg_err *err = (jpeg_err *) cinfo->err;

	err->pub.format_message(cinfo, err->message);
	longjmp(err->jmp, 1);
}

static void jpeg_on_message(j_common_ptr cinfo)
{
}

static void jpeg_dest_init(j_compress_ptr cinfo)
{
	jpeg_dest *dest = (jpeg_dest *) cinfo->dest;

	dest->pub.next_output_byte = dest->chunk;
	dest->pub.free_in_buffer = PDFIUM_JPEG_CHUNK;
}

static boolean jpeg_dest_flush(j_compress_ptr cinfo)
{
	jpeg_dest *dest = (jpeg_dest *) cinfo->dest;

	if (!pdfium_buf_append(dest->out, dest->chunk, PDFIUM_JPEG_CHUNK)) {
		ERREXIT(cinfo, JERR_FILE_WRITE);
	}
	jpeg_dest_init(cinfo);
	return TRUE;
}

static void jpeg_dest_term(j_compress_ptr cinfo)
{
	jpeg_dest *dest = (jpeg_dest *) cinfo->dest;

	if (!pdfium_buf_append(dest->out, dest->chunk, PDFIUM_JPEG_CHUNK - dest->pub.free_in_buffer)) {
		ERREXIT(cinfo, JERR_FILE_WRITE);
	}
}

static bool jpeg_write(j_compress_ptr cinfo, jpeg_err *err, jpeg_dest *dest, const pdfium_bitmap *img,
	unsigned char *row, int quality)
{
	if (setjmp(err->jmp)) {
		return false;
	}
	jpeg_create_compress(cinfo);
	cinfo->dest = &dest->pub;
	cinfo->image_width = img->width;
	cinfo->image_height = img->height;
	cinfo->input_components = out_channels(img, false);
	cinfo->in_color_space = img->gray ? JCS_GRAYSCALE : JCS_RGB;
	jpeg_set_defaults(cinfo);
	jpeg_set_quality(cinfo, quality, TRUE);
	jpeg_start_compress(cinfo, TRUE);
	for (JDIMENSION y = 0; y < cinfo->image_height; y++) {
		row_out(img, y, row, false);
		jpeg_write_scanlines(cinfo, &row, 1);
	}
	jpeg_finish_compress(cinfo);
	return true;
}

static zend_string *encode_jpeg(const pdfium_bitmap *img, int quality)
{
	struct jpeg_compress_struct cinfo;
	jpeg_err err;
	jpeg_dest *dest = emalloc(sizeof(*dest));
	pdfium_buf out = {0};
	unsigned char *row = safe_emalloc(img->width, out_channels(img, false), 0);
	bool ok;

	memset(&cinfo, 0, sizeof(cinfo));
	cinfo.err = jpeg_std_error(&err.pub);
	err.pub.error_exit = jpeg_on_error;
	err.pub.output_message = jpeg_on_message;
	dest->pub.init_destination = jpeg_dest_init;
	dest->pub.empty_output_buffer = jpeg_dest_flush;
	dest->pub.term_destination = jpeg_dest_term;
	dest->out = &out;

	ok = jpeg_write(&cinfo, &err, dest, img, row, quality);
	jpeg_destroy_compress(&cinfo);
	efree(row);
	efree(dest);
	if (!ok) {
		smart_str_free(&out.s);
		zend_throw_exception_ex(out.oom ? pdfium_ce_memory_exception : pdfium_ce_exception, 0, "Cannot encode JPEG: %s",
			out.oom ? "memory_limit reached" : err.message);
		return NULL;
	}
	return smart_str_extract(&out.s);
}

static bool quality_ok(zend_long quality, uint32_t arg)
{
	if (quality < 1 || quality > 100) {
		zend_argument_value_error(arg, "must be between 1 and 100");
		return false;
	}
	return true;
}

ZEND_METHOD(Pdfium_Bitmap, toPng)
{
	ZEND_PARSE_PARAMETERS_NONE();

	zend_string *png = encode_png(this_bitmap(ZEND_THIS));
	if (!png) {
		RETURN_THROWS();
	}
	RETURN_STR(png);
}

ZEND_METHOD(Pdfium_Bitmap, toJpeg)
{
	zend_long quality = 85;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(quality)
	ZEND_PARSE_PARAMETERS_END();

	if (!quality_ok(quality, 1)) {
		RETURN_THROWS();
	}
	zend_string *jpeg = encode_jpeg(this_bitmap(ZEND_THIS), (int) quality);
	if (!jpeg) {
		RETURN_THROWS();
	}
	RETURN_STR(jpeg);
}

ZEND_METHOD(Pdfium_Bitmap, savePng)
{
	char *path;
	size_t path_len;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_PATH(path, path_len)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_path_given(path_len)) {
		RETURN_THROWS();
	}
	pdfium_write_and_release(encode_png(this_bitmap(ZEND_THIS)), path);
}

ZEND_METHOD(Pdfium_Bitmap, saveJpeg)
{
	char *path;
	size_t path_len;
	zend_long quality = 85;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(quality)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_path_given(path_len) || !quality_ok(quality, 2)) {
		RETURN_THROWS();
	}
	pdfium_write_and_release(encode_jpeg(this_bitmap(ZEND_THIS), (int) quality), path);
}

void pdfium_bitmap_free_obj(zend_object *obj)
{
	pdfium_bitmap *img = pdfium_bitmap_from_obj(obj);

	if (img->pixels) {
		efree(img->pixels);
	}
	zend_object_std_dtor(obj);
}
