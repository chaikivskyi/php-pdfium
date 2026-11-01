#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "ext/spl/spl_exceptions.h"
#include "ext/standard/info.h"
#include "zend_exceptions.h"
#include "zend_interfaces.h"
#include "fopen_wrappers.h"
#include "ext/standard/file.h"
#include <errno.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>
#include <limits.h>
#ifdef HAVE_SYS_XATTR_H
#include <sys/xattr.h>
#ifdef __APPLE__
#define PDFIUM_LISTXATTR(p, b, n) listxattr(p, b, n, 0)
#define PDFIUM_GETXATTR(p, k, b, n) getxattr(p, k, b, n, 0, 0)
#define PDFIUM_FGETXATTR(f, k, b, n) fgetxattr(f, k, b, n, 0, 0)
#define PDFIUM_FSETXATTR(f, k, b, n) fsetxattr(f, k, b, n, 0, 0)
#define PDFIUM_FLISTXATTR(f, b, n) flistxattr(f, b, n, 0)
#define PDFIUM_FREMOVEXATTR(f, k) fremovexattr(f, k, 0)
#else
#define PDFIUM_LISTXATTR(p, b, n) listxattr(p, b, n)
#define PDFIUM_GETXATTR(p, k, b, n) getxattr(p, k, b, n)
#define PDFIUM_FGETXATTR(f, k, b, n) fgetxattr(f, k, b, n)
#define PDFIUM_FSETXATTR(f, k, b, n) fsetxattr(f, k, b, n, 0)
#define PDFIUM_FLISTXATTR(f, b, n) flistxattr(f, b, n)
#define PDFIUM_FREMOVEXATTR(f, k) fremovexattr(f, k)
#endif
#endif
#include "pdfium_arginfo.h"

zend_class_entry *pdfium_ce_document;
zend_class_entry *pdfium_ce_page;
zend_class_entry *pdfium_ce_page_iterator;
zend_class_entry *pdfium_ce_bitmap;
zend_class_entry *pdfium_ce_exception;
zend_class_entry *pdfium_ce_file_exception;
zend_class_entry *pdfium_ce_format_exception;
zend_class_entry *pdfium_ce_password_exception;
zend_class_entry *pdfium_ce_unsupported_exception;
zend_class_entry *pdfium_ce_memory_exception;

static zend_object_handlers document_handlers;
static zend_object_handlers page_handlers;
static zend_object_handlers page_iterator_handlers;
static zend_object_handlers bitmap_handlers;

void pdfium_throw_last_error(const char *context)
{
	unsigned long err = FPDF_GetLastError();
	zend_class_entry *ce = pdfium_ce_exception;
	const char *reason = "unknown error";

	switch (err) {
		case FPDF_ERR_FILE:
			ce = pdfium_ce_file_exception;
			reason = "file not found or could not be opened";
			break;
		case FPDF_ERR_FORMAT:
			ce = pdfium_ce_format_exception;
			reason = "not a PDF or corrupted";
			break;
		case FPDF_ERR_PAGE:
			ce = pdfium_ce_format_exception;
			reason = "page not found or content error";
			break;
		case FPDF_ERR_PASSWORD:
			ce = pdfium_ce_password_exception;
			reason = "password required or incorrect";
			break;
		case FPDF_ERR_SECURITY:
			ce = pdfium_ce_unsupported_exception;
			reason = "unsupported security scheme";
			break;
	}
	zend_throw_exception_ex(ce, (zend_long) err, "%s: %s", context, reason);
}

bool pdfium_memory_fits(size_t bytes)
{
	return PG(memory_limit) == -1 || zend_memory_usage(true) + bytes + ZEND_MM_CHUNK_SIZE <= (size_t) PG(memory_limit);
}

static bool buf_grow(pdfium_buf *b, size_t need)
{
	size_t used = smart_str_get_len(&b->s);
	size_t doubled = MAX(need, used);
	size_t stepped = MAX(need, MIN(used, (size_t) 1 << 20));
	size_t want;

	if (b->oom) {
		return false;
	}
	if (b->s.s && used + need < b->s.a) {
		return true;
	}
	if (pdfium_memory_fits(used + doubled)) {
		want = doubled;
	} else if (pdfium_memory_fits(used + stepped)) {
		want = stepped;
	} else if (pdfium_memory_fits(used + need)) {
		want = need;
	} else {
		b->oom = true;
		return false;
	}
	smart_str_alloc(&b->s, want + 1, 0);
	return true;
}

bool pdfium_buf_append(pdfium_buf *b, const void *data, size_t len)
{
	if (!buf_grow(b, len)) {
		return false;
	}
	smart_str_appendl(&b->s, data, len);
	return true;
}

bool pdfium_path_given(size_t len)
{
	if (!len) {
		zend_argument_value_error(1, "must not be empty");
		return false;
	}
	return true;
}

static char *shown_path(const char *path)
{
	smart_str out = {0};
	const char *p = path;
	const char *first = strstr(path, "://");

	if (!first || first == path || strspn(path, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+-.") != (size_t) (first - path)) {
		return estrdup(path);
	}

	for (const char *scheme_end; (scheme_end = strstr(p, "://")); ) {
		const char *authority = scheme_end + 3;
		const char *at = zend_memrchr(authority, '@', strcspn(authority, "/?#"));

		smart_str_appendl(&out, p, authority - p);
		if (at) {
			smart_str_appends(&out, "...");
		}
		p = at ? at : authority;
	}
	smart_str_appends(&out, p);
	smart_str_0(&out);

	char *shown = estrndup(ZSTR_VAL(out.s), ZSTR_LEN(out.s));

	smart_str_free(&out);
	return shown;
}

static void warn_path(const char *path, const char *format, ...)
{
	char *shown = shown_path(path);
	char *message;
	va_list args;

	va_start(args, format);
	vspprintf(&message, 0, format, args);
	va_end(args);
	php_error_docref1(NULL, shown, E_WARNING, "%s", message);
	efree(message);
	efree(shown);
}

static void throw_path(zend_class_entry *ce, zend_long code, const char *before, const char *path, const char *after)
{
	char *shown = shown_path(path);

	zend_throw_exception_ex(ce, code, "%s%s%s", before, shown, after);
	efree(shown);
}

static bool is_userspace(php_stream_wrapper *wrapper)
{
	return wrapper->wops->label && strcmp(wrapper->wops->label, "user-space") == 0;
}

static bool write_stream(php_stream_wrapper *wrapper, const char *path, zend_string *data, bool may_remove)
{
	php_stream_context *context = php_stream_context_from_zval(NULL, 0);
	php_stream *stream = may_remove ? php_stream_open_wrapper_ex((char *) path, "xb", 0, NULL, context) : NULL;
	bool removable = stream != NULL;
	bool ok;

	if (!stream) {
		stream = php_stream_open_wrapper_ex((char *) path, "wb", REPORT_ERRORS, NULL, context);
	}
	if (!stream) {
		return false;
	}
	errno = 0;
	bool written = php_stream_write(stream, ZSTR_VAL(data), ZSTR_LEN(data)) == (ssize_t) ZSTR_LEN(data);
	int error = errno;

	if (!written && wrapper == &php_plain_files_wrapper && error) {
		warn_path(path, "Write failed: %s", strerror(error));
	}
	ok = php_stream_close(stream) == 0 && written && !EG(exception);
	if (!ok && removable) {
		wrapper->wops->unlink(wrapper, path, 0, NULL);
	}
	return ok;
}

static bool sync_xattrs(const char *from, int fd)
{
#ifndef HAVE_SYS_XATTR_H
	return false;
#else
	ssize_t size = PDFIUM_LISTXATTR(from, NULL, 0);
	char *names = NULL;
	bool ok;

	if (size < 0) {
		if (errno != ENOTSUP) {
			return false;
		}
		size = 0;
	}
	if (size > 0) {
		names = emalloc(size);
		size = PDFIUM_LISTXATTR(from, names, size);
	}
	ok = size >= 0;
	for (char *name = names; ok && name < names + size; name += strlen(name) + 1) {
		ssize_t len = PDFIUM_GETXATTR(from, name, NULL, 0);
		char *want = len >= 0 ? emalloc(len + 1) : NULL;
		char *have = len >= 0 ? emalloc(len + 1) : NULL;

		ok = want && PDFIUM_GETXATTR(from, name, want, len) == len
			&& ((PDFIUM_FGETXATTR(fd, name, have, len) == len && memcmp(want, have, len) == 0)
				|| PDFIUM_FSETXATTR(fd, name, want, len) == 0);
		if (want) {
			efree(want);
			efree(have);
		}
	}

	ssize_t extra = ok ? PDFIUM_FLISTXATTR(fd, NULL, 0) : 0;

	if (extra > 0) {
		char *own = emalloc(extra);

		extra = PDFIUM_FLISTXATTR(fd, own, extra);
		ok = extra >= 0;
		for (char *name = own; ok && name < own + extra; name += strlen(name) + 1) {
			bool shared = false;

			for (char *other = names; !shared && other && other < names + size; other += strlen(other) + 1) {
				shared = strcmp(name, other) == 0;
			}
			ok = shared || PDFIUM_FREMOVEXATTR(fd, name) == 0 || strncmp(name, "system.", 7) != 0;
		}
		efree(own);
	} else if (extra < 0 && errno != ENOTSUP) {
		ok = false;
	}
	if (names) {
		efree(names);
	}
	return ok;
#endif
}

static bool write_all(int fd, const char *buf, size_t len)
{
	while (len) {
		ssize_t n = write(fd, buf, len > SSIZE_MAX ? SSIZE_MAX : len);

		if (n < 0 && errno == EINTR) {
			continue;
		}
		if (n == 0) {
			errno = EIO;
		}
		if (n <= 0) {
			return false;
		}
		buf += n;
		len -= n;
	}
	return true;
}

static void sync_dir(const char *target, const char *slash)
{
	char dir[MAXPATHLEN];
	int fd;

	snprintf(dir, sizeof(dir), "%.*s", slash == target ? 1 : (int) (slash - target), target);
	fd = open(dir, O_RDONLY);
	if (fd >= 0) {
		fsync(fd);
		close(fd);
	}
}

static bool write_fd(int fd, const char *path, zend_string *data, bool trim)
{
	errno = 0;
	bool ok = write_all(fd, ZSTR_VAL(data), ZSTR_LEN(data)) && (!trim || ftruncate(fd, ZSTR_LEN(data)) == 0) && fsync(fd) == 0;
	int error = ok ? 0 : errno ? errno : EIO;

	if (close(fd) != 0 && ok) {
		error = errno;
		ok = false;
	}
	if (!ok) {
		warn_path(path, "Write failed: %s", strerror(error));
	}
	return ok;
}

static bool write_in_place(const char *target, const char *path, zend_string *data)
{
	int fd = VCWD_OPEN(target, O_WRONLY | O_NOFOLLOW | O_CLOEXEC);

	if (fd < 0) {
		warn_path(path, "Failed to open stream: %s", strerror(errno));
		return false;
	}
#ifdef HAVE_POSIX_FALLOCATE
	struct stat st;

	if (fstat(fd, &st) == 0 && (size_t) st.st_size < ZSTR_LEN(data)) {
		int error = posix_fallocate(fd, st.st_size, ZSTR_LEN(data) - st.st_size);

		if (error && error != EINVAL && error != EOPNOTSUPP) {
			close(fd);
			warn_path(path, "Write failed: %s", strerror(error));
			return false;
		}
	}
#endif
	return write_fd(fd, path, data, true);
}

ZEND_TLS char pending_tmp[MAXPATHLEN];

static bool write_replacing(const char *local_path, const char *path, zend_string *data)
{
	char target[MAXPATHLEN], tmp[MAXPATHLEN];
	zend_stat_t st;

	if (php_check_open_basedir(local_path)) {
		return false;
	}
	if (VCWD_STAT(local_path, &st) != 0 || !S_ISREG(st.st_mode) || !VCWD_REALPATH(local_path, target)) {
		bool created = VCWD_LSTAT(local_path, &st) != 0;

		return write_stream(&php_plain_files_wrapper, path, data, created);
	}
	if (php_check_open_basedir(target)) {
		return false;
	}

	const char *slash = strrchr(target, '/');
	int fd = -1;

	if (st.st_nlink == 1 && VCWD_ACCESS(target, W_OK) == 0
		&& snprintf(tmp, sizeof(tmp), "%.*s/.pdfium-XXXXXX", (int) (slash - target), target) < (int) sizeof(tmp)) {
#ifdef HAVE_MKOSTEMP
		fd = mkostemp(tmp, O_CLOEXEC);
#else
		fd = mkstemp(tmp);
#endif
	}
	if (fd >= 0) {
		memcpy(pending_tmp, tmp, sizeof(tmp));
		if (fchown(fd, st.st_uid, st.st_gid) != 0 || !sync_xattrs(target, fd) || fchmod(fd, st.st_mode & 07777) != 0) {
			close(fd);
			VCWD_UNLINK(tmp);
			pending_tmp[0] = '\0';
			fd = -1;
		}
	}
	if (fd < 0) {
		return write_in_place(target, path, data);
	}

	bool ok = write_fd(fd, path, data, false);

	if (ok && VCWD_RENAME(tmp, target) == 0) {
		pending_tmp[0] = '\0';
		sync_dir(target, slash);
		return true;
	}
	VCWD_UNLINK(tmp);
	pending_tmp[0] = '\0';
	return ok && write_in_place(target, path, data);
}

typedef struct {
	zend_error_handling eh;
	int reporting;
	bool local;
	php_stream_wrapper *wrapper;
	const char *local_path;
	int last_type;
	int last_lineno;
	zend_string *last_message;
	zend_string *last_file;
} stream_scope;

static void clear_last_error(void)
{
	if (PG(last_error_message)) {
		zend_string_release(PG(last_error_message));
		PG(last_error_message) = NULL;
	}
	if (PG(last_error_file)) {
		zend_string_release(PG(last_error_file));
		PG(last_error_file) = NULL;
	}
	PG(last_error_type) = 0;
	PG(last_error_lineno) = 0;
}

static bool scope_begin(stream_scope *sc, const char *path)
{
	sc->reporting = EG(error_reporting);
	sc->local = true;
	zend_replace_error_handling(EH_THROW, pdfium_ce_file_exception, &sc->eh);
	EG(error_reporting) &= E_FATAL_ERRORS;
	sc->wrapper = php_stream_locate_url_wrapper(path, &sc->local_path, REPORT_ERRORS);
	if (!sc->wrapper || EG(exception)) {
		return false;
	}
	if (sc->wrapper != &php_plain_files_wrapper) {
		zend_restore_error_handling(&sc->eh);
		sc->last_type = PG(last_error_type);
		sc->last_lineno = PG(last_error_lineno);
		sc->last_message = PG(last_error_message);
		sc->last_file = PG(last_error_file);
		PG(last_error_message) = NULL;
		PG(last_error_file) = NULL;
		PG(last_error_type) = 0;
		PG(last_error_lineno) = 0;
		sc->local = false;
		return true;
	}
	if (strlen(sc->local_path) >= MAXPATHLEN) {
		warn_path(path, "Failed to open stream: %s", strerror(ENAMETOOLONG));
		return false;
	}
	return true;
}

static void scope_end(stream_scope *sc, bool ok)
{
	EG(error_reporting) = sc->reporting;
	if (sc->local) {
		zend_restore_error_handling(&sc->eh);
		return;
	}
	if (!ok && !EG(exception) && PG(last_error_message)
		&& (PG(last_error_type) & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING))) {
		zend_throw_exception(pdfium_ce_file_exception, ZSTR_VAL(PG(last_error_message)), 0);
	}
	if (PG(last_error_type) & E_FATAL_ERRORS) {
		if (sc->last_message) {
			zend_string_release(sc->last_message);
		}
		if (sc->last_file) {
			zend_string_release(sc->last_file);
		}
		return;
	}
	clear_last_error();
	PG(last_error_type) = sc->last_type;
	PG(last_error_lineno) = sc->last_lineno;
	PG(last_error_message) = sc->last_message;
	PG(last_error_file) = sc->last_file;
}

static void scope_abort(stream_scope *sc)
{
	if (pending_tmp[0]) {
		VCWD_UNLINK(pending_tmp);
		pending_tmp[0] = '\0';
	}
	scope_end(sc, true);
}

void pdfium_write_and_release(zend_string *data, const char *path)
{
	stream_scope sc;
	volatile bool ok = false;

	if (!data) {
		return;
	}
	if (scope_begin(&sc, path)) {
		zend_try {
			ok = sc.local ? write_replacing(sc.local_path, path, data) : write_stream(sc.wrapper, path, data, false);
		} zend_catch {
			scope_abort(&sc);
			zend_bailout();
		} zend_end_try();
	}
	scope_end(&sc, ok);
	zend_string_release(data);
	if (!ok) {
		throw_path(pdfium_ce_file_exception, 0, "Cannot write ", path, "");
	}
}

static bool read_all(php_stream *stream, pdfium_buf *out, const char *path, bool local, bool sized)
{
	php_stream_statbuf ssb;

	if (sized && php_stream_stat(stream, &ssb) == 0 && ssb.sb.st_size > 0) {
		if (local && !buf_grow(out, ssb.sb.st_size + 1)) {
			return false;
		}
		if (!local && pdfium_memory_fits(ssb.sb.st_size + 1)) {
			smart_str_alloc(&out->s, ssb.sb.st_size + 1, 0);
		}
	}
	for (;;) {
		size_t used = smart_str_get_len(&out->s);

		if ((!out->s.s || out->s.a == used) && !buf_grow(out, 65536)) {
			return false;
		}
		errno = 0;
		ssize_t n = php_stream_read(stream, ZSTR_VAL(out->s.s) + used, out->s.a - used);

		if (n < 0 && errno == EINTR) {
			continue;
		}
		if (n < 0) {
			if (errno) {
				warn_path(path, "Read failed: %s", strerror(errno));
			}
			return false;
		}
		if (n == 0) {
			if (php_stream_eof(stream)) {
				return true;
			}
			if (!EG(exception)) {
				warn_path(path, "Read stopped before the end of the stream");
			}
			return false;
		}
		ZSTR_LEN(out->s.s) += n;
	}
}

zend_string *pdfium_read_file(const char *path)
{
	stream_scope sc;
	pdfium_buf out = {0};
	volatile bool ok = false;

	if (scope_begin(&sc, path)) {
		zend_try {
			php_stream *stream = NULL;
			zend_stat_t st;
			bool allowed = !sc.local || php_check_open_basedir(sc.local_path) == 0;

			if (allowed && sc.local && VCWD_STAT(sc.local_path, &st) == 0 && !S_ISREG(st.st_mode)) {
				warn_path(path, "Failed to open stream: Not a regular file");
			} else if (allowed) {
				stream = php_stream_open_wrapper_ex((char *) path, "rb", REPORT_ERRORS, NULL, php_stream_context_from_zval(NULL, 0));
			}
			if (stream) {
				ok = read_all(stream, &out, path, sc.local, sc.local || !is_userspace(sc.wrapper));
				php_stream_close(stream);
				ok = ok && !EG(exception);
			}
		} zend_catch {
			scope_abort(&sc);
			zend_bailout();
		} zend_end_try();
	}
	scope_end(&sc, ok || out.oom);
	if (out.oom || !ok) {
		smart_str_free(&out.s);
		if (out.oom) {
			throw_path(pdfium_ce_memory_exception, 0, "Reading ", path, " needs more memory than memory_limit leaves");
		} else {
			throw_path(pdfium_ce_file_exception, FPDF_ERR_FILE, "Cannot open ", path, "");
		}
		return NULL;
	}
	return smart_str_extract(&out.s);
}

static zend_object *object_create(zend_class_entry *ce, size_t size, const zend_object_handlers *handlers)
{
	zend_object *obj = (zend_object *) ((char *) zend_object_alloc(size, ce) + handlers->offset);

	zend_object_std_init(obj, ce);
	object_properties_init(obj, ce);
	obj->handlers = handlers;
	return obj;
}

static zend_object *document_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_document), &document_handlers);
}

static zend_object *page_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_page), &page_handlers);
}

static zend_object *page_iterator_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_page_iterator), &page_iterator_handlers);
}

static zend_object *bitmap_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_bitmap), &bitmap_handlers);
}

static void init_handlers(zend_object_handlers *h, int offset, zend_object_free_obj_t free_obj)
{
	memcpy(h, &std_object_handlers, sizeof(*h));
	h->offset = offset;
	h->free_obj = free_obj;
	h->clone_obj = NULL;
	h->compare = zend_objects_not_comparable;
}

PHP_MINIT_FUNCTION(pdfium)
{
	FPDF_LIBRARY_CONFIG config = {.version = 2};

	ZEND_TSRMLS_CACHE_UPDATE();
	FPDF_InitLibraryWithConfig(&config);

	pdfium_ce_exception = register_class_Pdfium_Exception(spl_ce_RuntimeException);
	pdfium_ce_file_exception = register_class_Pdfium_FileException(pdfium_ce_exception);
	pdfium_ce_format_exception = register_class_Pdfium_FormatException(pdfium_ce_exception);
	pdfium_ce_password_exception = register_class_Pdfium_PasswordException(pdfium_ce_exception);
	pdfium_ce_unsupported_exception = register_class_Pdfium_UnsupportedException(pdfium_ce_exception);
	pdfium_ce_memory_exception = register_class_Pdfium_MemoryException(pdfium_ce_exception);

	/* Before Document: its getIterator() return type narrows IteratorAggregate's to PageIterator. */
	init_handlers(&page_iterator_handlers, XtOffsetOf(pdfium_page_iterator, std), pdfium_page_iterator_free_obj);
	pdfium_ce_page_iterator = register_class_Pdfium_PageIterator(zend_ce_iterator);
	pdfium_ce_page_iterator->create_object = page_iterator_create;

	init_handlers(&document_handlers, XtOffsetOf(pdfium_document, std), pdfium_document_free_obj);
	pdfium_ce_document = register_class_Pdfium_Document(zend_ce_countable, zend_ce_aggregate);
	pdfium_ce_document->create_object = document_create;

	init_handlers(&page_handlers, XtOffsetOf(pdfium_page, std), pdfium_page_free_obj);
	pdfium_ce_page = register_class_Pdfium_Page();
	pdfium_ce_page->create_object = page_create;

	init_handlers(&bitmap_handlers, XtOffsetOf(pdfium_bitmap, std), pdfium_bitmap_free_obj);
	pdfium_ce_bitmap = register_class_Pdfium_Bitmap();
	pdfium_ce_bitmap->create_object = bitmap_create;

	return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(pdfium)
{
	FPDF_DestroyLibrary();
	return SUCCESS;
}

#ifdef ZTS
PHP_RINIT_FUNCTION(pdfium)
{
	ZEND_TSRMLS_CACHE_UPDATE();
	return SUCCESS;
}
#define PDFIUM_RINIT PHP_RINIT(pdfium)
#else
#define PDFIUM_RINIT NULL
#endif

PHP_MINFO_FUNCTION(pdfium)
{
	php_info_print_table_start();
	php_info_print_table_row(2, "pdfium support", "enabled");
	php_info_print_table_row(2, "Extension version", PHP_PDFIUM_VERSION);
	php_info_print_table_row(2, "PDFium version", PHP_PDFIUM_LIB_VERSION);
	php_info_print_table_end();
}

static const zend_module_dep pdfium_deps[] = {
	ZEND_MOD_REQUIRED("spl")
	ZEND_MOD_END
};

zend_module_entry pdfium_module_entry = {
	STANDARD_MODULE_HEADER_EX,
	NULL,
	pdfium_deps,
	"pdfium",
	NULL,
	PHP_MINIT(pdfium),
	PHP_MSHUTDOWN(pdfium),
	PDFIUM_RINIT,
	NULL,
	PHP_MINFO(pdfium),
	PHP_PDFIUM_VERSION,
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PDFIUM
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(pdfium)
#endif
