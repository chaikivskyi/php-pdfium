#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

zend_string *pdfium_utf16le_to_utf8(const unsigned short *units, size_t count)
{
	zend_string *out = zend_string_safe_alloc(count, 3, 0, 0);
	unsigned char *p = (unsigned char *) ZSTR_VAL(out);

	for (size_t i = 0; i < count; i++) {
		uint32_t cp = units[i];

		if (pdfium_high_surrogate(cp) && i + 1 < count && pdfium_low_surrogate(units[i + 1])) {
			cp = pdfium_surrogate_pair(cp, units[++i]);
		}
		p += pdfium_utf8_encode(cp, (char *) p);
	}

	size_t len = p - (unsigned char *) ZSTR_VAL(out);

	if (!len) {
		zend_string_efree(out);
		return ZSTR_EMPTY_ALLOC();
	}
	out = zend_string_truncate(out, len, 0);
	ZSTR_VAL(out)[len] = '\0';
	return out;
}

size_t pdfium_utf8_encode(uint32_t cp, char *out)
{
	if ((cp >= 0xD800 && cp <= 0xDFFF) || cp > 0x10FFFF) {
		cp = 0xFFFD;
	}
	if (cp < 0x80) {
		out[0] = (char) cp;
		return 1;
	}
	if (cp < 0x800) {
		out[0] = (char) (0xC0 | (cp >> 6));
		out[1] = (char) (0x80 | (cp & 0x3F));
		return 2;
	}
	if (cp < 0x10000) {
		out[0] = (char) (0xE0 | (cp >> 12));
		out[1] = (char) (0x80 | ((cp >> 6) & 0x3F));
		out[2] = (char) (0x80 | (cp & 0x3F));
		return 3;
	}
	out[0] = (char) (0xF0 | (cp >> 18));
	out[1] = (char) (0x80 | ((cp >> 12) & 0x3F));
	out[2] = (char) (0x80 | ((cp >> 6) & 0x3F));
	out[3] = (char) (0x80 | (cp & 0x3F));
	return 4;
}
