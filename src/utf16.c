#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "ext/standard/html.h"

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

zend_string *pdfium_utf16le_fetch(pdfium_utf16le_getter get, void *source, unsigned long bytes)
{
	if (bytes <= 2) {
		return NULL;
	}
	unsigned short *units = emalloc(bytes);
	unsigned long got = get(source, units, bytes);
	size_t count = got <= bytes ? got / 2 : 0, n = 0;

	while (n < count && units[n]) {
		n++;
	}
	zend_string *s = n ? pdfium_utf16le_to_utf8(units, n) : NULL;

	efree(units);
	return s;
}

zend_string *pdfium_utf16le_fetch_under_limit(pdfium_utf16le_getter get, void *source, size_t *used, bool *oom)
{
	unsigned long bytes = get(source, NULL, 0);

	if (bytes <= 2) {
		return NULL;
	}
	*used += (size_t) bytes / 2 * 3;
	if (!pdfium_memory_fits(bytes + *used)) {
		*oom = true;
		return NULL;
	}
	return pdfium_utf16le_fetch(get, source, bytes);
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

const char *pdfium_utf8_to_utf16le(const zend_string *s, unsigned short *units, size_t *count)
{
	size_t pos = 0, n = 0;
	bool nul = false, invalid = false;

	while (pos < ZSTR_LEN(s)) {
		zend_result status;
		unsigned int cp = php_next_utf8_char((const unsigned char *) ZSTR_VAL(s), ZSTR_LEN(s), &pos, &status);

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

unsigned short *pdfium_utf8_to_utf16le_alloc(const zend_string *s)
{
	size_t count;

	pdfium_utf8_to_utf16le(s, NULL, &count);
	unsigned short *units = safe_emalloc(count + 1, sizeof(*units), 0);

	pdfium_utf8_to_utf16le(s, units, &count);
	units[count] = 0;
	return units;
}
