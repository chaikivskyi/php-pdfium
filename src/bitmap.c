#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "fopen_wrappers.h"
#include "zend_exceptions.h"
#include "zend_smart_str.h"
#include <ctype.h>
#include <math.h>
#include <setjmp.h>
#include <stdio.h>
#include <png.h>
#include <jpeglib.h>

#define PDFIUM_MAX_PIXELS 100000000
#define PDFIUM_JPEG_CHUNK 16384

ZEND_METHOD(Pdfium_Bitmap, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

static pdfium_bitmap *this_bitmap(zval *self)
{
	return pdfium_bitmap_from_obj(Z_OBJ_P(self));
}

static int pixels_for(float points, zend_long dpi)
{
	double px = round((double) points * (double) dpi / 72.0);

	return !(px >= 1) ? 1 : px > INT_MAX ? INT_MAX : (int) px;
}

ZEND_METHOD(Pdfium_Page, render)
{
	pdfium_page *p = pdfium_page_from_obj(Z_OBJ_P(ZEND_THIS));
	zend_long dpi = 150;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(dpi)
	ZEND_PARSE_PARAMETERS_END();

	if (dpi < 1 || dpi > 2400) {
		zend_argument_value_error(1, "must be between 1 and 2400");
		RETURN_THROWS();
	}
	int w = pixels_for(FPDF_GetPageWidthF(p->page), dpi);
	int h = pixels_for(FPDF_GetPageHeightF(p->page), dpi);

	if (w > JPEG_MAX_DIMENSION || h > JPEG_MAX_DIMENSION) {
		zend_argument_value_error(1, "would render %dx%d pixels, above the %d-pixel limit per side", w, h,
			(int) JPEG_MAX_DIMENSION);
		RETURN_THROWS();
	}
	if ((double) w * h > PDFIUM_MAX_PIXELS) {
		zend_argument_value_error(1, "would render %dx%d pixels, above the %d-pixel ceiling", w, h,
			PDFIUM_MAX_PIXELS);
		RETURN_THROWS();
	}
	int64_t needed = (int64_t) w * h * 7;
	if (PG(memory_limit) != -1 && needed > (int64_t) PG(memory_limit) - (int64_t) zend_memory_usage(0)) {
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
			"Rendering %dx%d pixels needs more memory than memory_limit leaves", w, h);
		RETURN_THROWS();
	}

	FPDF_BITMAP bitmap = FPDFBitmap_Create(w, h, 0);
	if (!bitmap) {
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0, "Cannot allocate a %dx%d bitmap", w, h);
		RETURN_THROWS();
	}
	FPDF_FORMHANDLE form = pdfium_doc_handle_form(p->h);
	if (form && !p->form_loaded) {
		FORM_OnAfterLoadPage(p->page, form);
		p->form_loaded = true;
	}
	FPDFBitmap_FillRect(bitmap, 0, 0, w, h, 0xFFFFFFFF);
	FPDF_RenderPageBitmap(bitmap, p->page, 0, 0, w, h, 0, FPDF_ANNOT);
	if (form) {
		FPDF_FFLDraw(form, bitmap, p->page, 0, 0, w, h, 0, FPDF_ANNOT);
	}

	object_init_ex(return_value, pdfium_ce_bitmap);
	pdfium_bitmap *img = this_bitmap(return_value);
	img->bitmap = bitmap;
}

ZEND_METHOD(Pdfium_Bitmap, width)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(FPDFBitmap_GetWidth(this_bitmap(ZEND_THIS)->bitmap));
}

ZEND_METHOD(Pdfium_Bitmap, height)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(FPDFBitmap_GetHeight(this_bitmap(ZEND_THIS)->bitmap));
}

static void bgrx_row_to_rgb(const pdfium_bitmap *img, int y, unsigned char *rgb)
{
	const unsigned char *src = (const unsigned char *) FPDFBitmap_GetBuffer(img->bitmap)
		+ (size_t) y * FPDFBitmap_GetStride(img->bitmap);

	for (int x = FPDFBitmap_GetWidth(img->bitmap); x > 0; x--, src += 4, rgb += 3) {
		rgb[0] = src[2];
		rgb[1] = src[1];
		rgb[2] = src[0];
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
	smart_str_appendl((smart_str *) png_get_io_ptr(png), (const char *) data, len);
}

static void png_on_flush(png_structp png)
{
}

static bool png_write(png_structp png, png_infop info, const pdfium_bitmap *img, smart_str *out, unsigned char *row)
{
	if (setjmp(png_jmpbuf(png))) {
		return false;
	}
	png_set_write_fn(png, out, png_on_write, png_on_flush);
	png_set_IHDR(png, info, FPDFBitmap_GetWidth(img->bitmap), FPDFBitmap_GetHeight(img->bitmap), 8, PNG_COLOR_TYPE_RGB,
		PNG_INTERLACE_NONE, PNG_COMPRESSION_TYPE_DEFAULT, PNG_FILTER_TYPE_DEFAULT);
	png_write_info(png, info);
	for (int y = 0, h = FPDFBitmap_GetHeight(img->bitmap); y < h; y++) {
		bgrx_row_to_rgb(img, y, row);
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
	smart_str out = {0};
	bool ok = false;

	if (info) {
		unsigned char *row = safe_emalloc(FPDFBitmap_GetWidth(img->bitmap), 3, 0);

		ok = png_write(png, info, img, &out, row);
		efree(row);
	}
	png_destroy_write_struct(&png, &info);
	if (!ok) {
		smart_str_free(&out);
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot encode PNG: %s", message);
		return NULL;
	}
	return smart_str_extract(&out);
}

typedef struct {
	struct jpeg_error_mgr pub;
	jmp_buf jmp;
	char message[JMSG_LENGTH_MAX];
} jpeg_err;

typedef struct {
	struct jpeg_destination_mgr pub;
	smart_str *out;
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

	smart_str_appendl(dest->out, (const char *) dest->chunk, PDFIUM_JPEG_CHUNK);
	jpeg_dest_init(cinfo);
	return TRUE;
}

static void jpeg_dest_term(j_compress_ptr cinfo)
{
	jpeg_dest *dest = (jpeg_dest *) cinfo->dest;

	smart_str_appendl(dest->out, (const char *) dest->chunk, PDFIUM_JPEG_CHUNK - dest->pub.free_in_buffer);
}

static bool jpeg_write(j_compress_ptr cinfo, jpeg_err *err, jpeg_dest *dest, const pdfium_bitmap *img,
	unsigned char *row, int quality)
{
	if (setjmp(err->jmp)) {
		return false;
	}
	jpeg_create_compress(cinfo);
	cinfo->dest = &dest->pub;
	cinfo->image_width = FPDFBitmap_GetWidth(img->bitmap);
	cinfo->image_height = FPDFBitmap_GetHeight(img->bitmap);
	cinfo->input_components = 3;
	cinfo->in_color_space = JCS_RGB;
	jpeg_set_defaults(cinfo);
	jpeg_set_quality(cinfo, quality, TRUE);
	jpeg_start_compress(cinfo, TRUE);
	for (JDIMENSION y = 0; y < cinfo->image_height; y++) {
		bgrx_row_to_rgb(img, y, row);
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
	smart_str out = {0};
	unsigned char *row = safe_emalloc(FPDFBitmap_GetWidth(img->bitmap), 3, 0);
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
		smart_str_free(&out);
		zend_throw_exception_ex(pdfium_ce_exception, 0, "Cannot encode JPEG: %s", err.message);
		return NULL;
	}
	return smart_str_extract(&out);
}

static bool quality_ok(zend_long quality, uint32_t arg)
{
	if (quality < 1 || quality > 100) {
		zend_argument_value_error(arg, "must be between 1 and 100");
		return false;
	}
	return true;
}

static bool scheme_absent_or_registered(const char *path)
{
	HashTable *wrappers = php_stream_get_url_stream_wrappers_hash();
	size_t n = 0;

	while (isalnum((unsigned char) path[n]) || path[n] == '+' || path[n] == '-' || path[n] == '.') {
		n++;
	}
	if (n < 2 || path[n] != ':' || (strncmp(path + n + 1, "//", 2) != 0 && !(n == 4 && !memcmp(path, "data:", 5)))) {
		return true;
	}
	return zend_hash_str_find_ptr(wrappers, path, n) || zend_hash_str_find_ptr_lc(wrappers, path, n);
}

static void write_and_release(zend_string *data, const char *path)
{
	const char *local_path;
	php_stream_wrapper *wrapper = NULL;
	php_stream *stream = NULL;

	if (!data) {
		return;
	}
	if (scheme_absent_or_registered(path)) {
		wrapper = php_stream_locate_url_wrapper(path, &local_path, 0);
	}
	if (wrapper && (wrapper != &php_plain_files_wrapper || php_check_open_basedir_ex(local_path, 0) == 0)) {
		stream = php_stream_open_wrapper(path, "wb", 0, NULL);
	}
	if (stream) {
		stream->flags |= PHP_STREAM_FLAG_SUPPRESS_ERRORS;
		bool written = php_stream_write(stream, ZSTR_VAL(data), ZSTR_LEN(data)) == (ssize_t) ZSTR_LEN(data);

		if (php_stream_close(stream) == 0 && written) {
			zend_string_release(data);
			return;
		}
	}
	zend_string_release(data);
	zend_throw_exception_ex(pdfium_ce_file_exception, 0, "Cannot write %s", path);
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

	write_and_release(encode_png(this_bitmap(ZEND_THIS)), path);
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

	if (!quality_ok(quality, 2)) {
		RETURN_THROWS();
	}
	write_and_release(encode_jpeg(this_bitmap(ZEND_THIS), (int) quality), path);
}

void pdfium_bitmap_free_obj(zend_object *obj)
{
	pdfium_bitmap *img = pdfium_bitmap_from_obj(obj);

	if (img->bitmap) {
		FPDFBitmap_Destroy(img->bitmap);
	}
	zend_object_std_dtor(obj);
}
