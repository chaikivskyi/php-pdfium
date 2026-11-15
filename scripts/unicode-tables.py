#!/usr/bin/env python3
"""Writes src/unicode_tables.h for TextPage::search(), from this Python's Unicode data.

pdfium_casefold: the case folding of every Basic Multilingual Plane character that folds to one other character.
pdfium_word: the letters, marks and digits of every plane that make up words, without the scripts written with no spaces
between words.
"""
import pathlib
import unicodedata

UNSPACED = [(0x0E00, 0x0FFF), (0x1000, 0x109F), (0x1780, 0x17FF), (0x2E80, 0xA4CF), (0xF900, 0xFAFF), (0xFF66, 0xFF9F),
            (0x16FE0, 0x18DFF), (0x1AFF0, 0x1B2FF), (0x20000, 0x3FFFF)]


def fold(cp):
    folded = chr(cp).casefold()
    return ord(folded) if len(folded) == 1 else ord(chr(cp).lower()[0])


def word(cp):
    category = unicodedata.category(chr(cp))
    return (category[0] in "LM" or category in ("Nd", "Nl")) and not any(a <= cp <= b for a, b in UNSPACED)


folds = []
for cp in range(0x80, 0x10000):
    if 0xD800 <= cp <= 0xDFFF:
        continue
    folded = fold(cp)
    if folded != cp and folded < 0x10000:
        delta = (folded - cp) & 0xFFFF
        if folds:
            first, last, kept, step = folds[-1]
            if kept == delta and (step == cp - last or (step == 0 and cp - last in (1, 2))):
                folds[-1] = (first, cp, kept, cp - last)
                continue
        folds.append((cp, cp, delta, 0))

words = []
for cp in range(0x80, 0x110000):
    if word(cp):
        if words and words[-1][1] == cp - 1:
            words[-1][1] = cp
        else:
            words.append([cp, cp])

lines = ["#ifndef PDFIUM_UNICODE_TABLES_H", "#define PDFIUM_UNICODE_TABLES_H", "",
         '#define PDFIUM_UNICODE_VERSION "%s"' % unicodedata.unidata_version, "",
         "static const unsigned short pdfium_casefold[][4] = {"]
lines += ["\t{0x%04X, 0x%04X, 0x%04X, %d}," % (first, last, delta, step or 1) for first, last, delta, step in folds]
lines += ["};", "", "static const unsigned int pdfium_word[][2] = {"]
lines += ["\t{0x%04X, 0x%04X}," % (first, last) for first, last in words]
lines += ["};", "", "#endif", ""]
pathlib.Path(__file__).resolve().parent.parent.joinpath("src", "unicode_tables.h").write_text("\n".join(lines))
