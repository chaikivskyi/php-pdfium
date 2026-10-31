"""Sphinx configuration for the pdfium PHP extension documentation."""

import pathlib
import re

ROOT = pathlib.Path(__file__).resolve().parent.parent


def _read(path, pattern):
    match = re.search(pattern, (ROOT / path).read_text())
    return match.group(1) if match else "unknown"


project = "php-pdfium"
author = "Petro Chaikivskyi"
copyright = "2026, Petro Chaikivskyi"
release = _read("php_pdfium.h", r'#define PHP_PDFIUM_VERSION "([^"]+)"')
version = release
pdfium_version = (ROOT / "PDFIUM_VERSION").read_text().strip()

extensions = [
    "sphinxcontrib.phpdomain",
]

primary_domain = "php"
highlight_language = "php"
pygments_style = "sphinx"

rst_epilog = f"""
.. |release| replace:: {release}
.. |pdfium_version| replace:: ``{pdfium_version}``
"""

exclude_patterns = ["_build"]
templates_path = []

html_theme = "sphinx_rtd_theme"
html_title = f"php-pdfium {release}"
html_short_title = "php-pdfium"
html_show_sourcelink = True
html_theme_options = {
    "navigation_depth": 3,
    "collapse_navigation": False,
}

# PHP's built-in types and exceptions have no target in these docs.
_builtins = [
    "int", "float", "string", "bool", "array", "void", "?string",
    "\\ValueError", "\\TypeError", "\\RuntimeException", "\\Countable", "\\Iterator",
]
nitpick_ignore = [(f"php:{role}", name) for role in ("obj", "exc", "class") for name in _builtins]


def setup(app):
    # Snippets start without "<?php", so highlight them as inline PHP.
    from sphinx.highlighting import lexers
    from pygments.lexers.php import PhpLexer

    lexers["php"] = PhpLexer(startinline=True)
