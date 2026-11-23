import os
from fontTools.fontBuilder import FontBuilder
from fontTools.pens.ttGlyphPen import TTGlyphPen


def square(size):
    pen = TTGlyphPen(None)
    if size:
        pen.moveTo((0, 0))
        pen.lineTo((0, size))
        pen.lineTo((size, size))
        pen.lineTo((size, 0))
        pen.closePath()
    return pen.glyph()


def build(family, path):
    fb = FontBuilder(1000, isTTF=True)
    fb.setupGlyphOrder([".notdef", "A"])
    fb.setupCharacterMap({0x41: "A"})
    fb.setupGlyf({".notdef": square(0), "A": square(1000)})
    fb.setupHorizontalMetrics({".notdef": (1000, 0), "A": (1000, 0)})
    fb.setupHorizontalHeader(ascent=1000, descent=0)
    fb.setupNameTable({"familyName": family, "styleName": "Regular"})
    fb.setupOS2(sTypoAscender=1000, sTypoDescender=0, usWinAscent=1000, usWinDescent=0)
    fb.setupPost()
    fb.save(path)

here = os.path.dirname(os.path.abspath(__file__))
build("PdfiumTestSquare", os.path.join(here, "PdfiumTestSquare.ttf"))
os.makedirs(os.path.join(here, "..", "fonts-standard"), exist_ok=True)
build("Arial", os.path.join(here, "..", "fonts-standard", "Arial.ttf"))
