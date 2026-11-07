all: pdfium-lib
install-modules: pdfium-lib

pdfium-lib:
	@mkdir -p $(phplibdir)
	@cmp -s "$(PDFIUM_LIB_DIR)/$(PDFIUM_LIB_NAME)" "$(phplibdir)/$(PDFIUM_LIB_NAME)" || cp "$(PDFIUM_LIB_DIR)/$(PDFIUM_LIB_NAME)" "$(phplibdir)/$(PDFIUM_LIB_NAME)"

.PHONY: pdfium-lib
