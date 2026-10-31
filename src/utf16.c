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

		if (cp >= 0xD800 && cp <= 0xDBFF && i + 1 < count && units[i + 1] >= 0xDC00 && units[i + 1] <= 0xDFFF) {
			cp = 0x10000 + ((cp - 0xD800) << 10) + (units[++i] - 0xDC00);
		} else if (cp >= 0xD800 && cp <= 0xDFFF) {
			cp = 0xFFFD;
		}
		if (cp < 0x80) {
			*p++ = (unsigned char) cp;
		} else if (cp < 0x800) {
			*p++ = (unsigned char) (0xC0 | (cp >> 6));
			*p++ = (unsigned char) (0x80 | (cp & 0x3F));
		} else if (cp < 0x10000) {
			*p++ = (unsigned char) (0xE0 | (cp >> 12));
			*p++ = (unsigned char) (0x80 | ((cp >> 6) & 0x3F));
			*p++ = (unsigned char) (0x80 | (cp & 0x3F));
		} else {
			*p++ = (unsigned char) (0xF0 | (cp >> 18));
			*p++ = (unsigned char) (0x80 | ((cp >> 12) & 0x3F));
			*p++ = (unsigned char) (0x80 | ((cp >> 6) & 0x3F));
			*p++ = (unsigned char) (0x80 | (cp & 0x3F));
		}
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
