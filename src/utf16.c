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

size_t pdfium_utf16le_read(pdfium_utf16le_getter get, void *source, unsigned short *units, unsigned long bytes)
{
	unsigned long got = get(source, units, bytes);
	size_t count = got <= bytes ? got / 2 : 0, n = 0;

	while (n < count && units[n]) {
		n++;
	}
	return n;
}

zend_string *pdfium_utf16le_fetch(pdfium_utf16le_getter get, void *source, unsigned long bytes)
{
	if (bytes <= 2) {
		return NULL;
	}
	unsigned short *units = emalloc(bytes);
	size_t n = pdfium_utf16le_read(get, source, units, bytes);
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

static bool pdf_doc_encodes(unsigned int cp)
{
	static const unsigned short high[] = {
		0x0131, 0x0141, 0x0142, 0x0152, 0x0153, 0x0160, 0x0161, 0x0178, 0x017D, 0x017E, 0x0192, 0x02C6, 0x02C7, 0x02D8,
		0x02D9, 0x02DA, 0x02DB, 0x02DC, 0x02DD, 0x2013, 0x2014, 0x2018, 0x2019, 0x201A, 0x201C, 0x201D, 0x201E, 0x2020,
		0x2021, 0x2022, 0x2026, 0x2030, 0x2039, 0x203A, 0x2044, 0x20AC, 0x2122, 0x2212, 0xFB01, 0xFB02,
	};
	size_t i;

	if (cp <= 0xFF) {
		return !(cp >= 0x18 && cp <= 0x1F) && !(cp >= 0x7F && cp <= 0xA0) && cp != 0xAD;
	}
	for (i = 0; i < sizeof(high) / sizeof(high[0]); i++) {
		if (cp == high[i]) {
			return true;
		}
	}
	return false;
}

const char *pdfium_pdf_text_fault(const zend_string *s, size_t *units)
{
	static const struct { const char *bytes; size_t len; } marks[] = {
		{"\xC3\xBE\xC3\xBF", 4}, {"\xC3\xBF\xC3\xBE", 4}, {"\xC3\xAF\xC2\xBB\xC2\xBF", 6},
	};
	const unsigned char *bytes = (const unsigned char *) ZSTR_VAL(s);
	const char *bad = pdfium_utf8_to_utf16le(s, NULL, units);
	size_t i, pos = 0;

	if (bad) {
		return bad;
	}
	if (memchr(bytes, 0x1B, ZSTR_LEN(s))) {
		return "must not contain the escape character (U+001B)";
	}
	for (i = 0; i < sizeof(marks) / sizeof(marks[0]); i++) {
		if (ZSTR_LEN(s) >= marks[i].len && !memcmp(bytes, marks[i].bytes, marks[i].len)) {
			break;
		}
	}
	if (i == sizeof(marks) / sizeof(marks[0])) {
		return NULL;
	}
	while (pos < ZSTR_LEN(s)) {
		zend_result status;

		if (!pdf_doc_encodes(php_next_utf8_char(bytes, ZSTR_LEN(s), &pos, &status))) {
			return NULL;
		}
	}
	return "must not start with U+00FE U+00FF, U+00FF U+00FE or U+00EF U+00BB U+00BF while all its characters are in"
		" PDFDocEncoding, as a PDF reader would read that start as a byte order mark";
}
