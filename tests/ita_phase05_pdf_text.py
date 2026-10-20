"""Inspect synthetic PDFs produced by ita_phase05_pdf.php; no DB or private data."""
from pathlib import Path
from pypdf import PdfReader
import pdfplumber

root = Path(__file__).resolve().parents[1] / "tmp/pdfs"
for name, expected_pages in [("handover-short.pdf", 1), ("handover-long.pdf", 2)]:
    path = root / name
    reader = PdfReader(path)
    assert len(reader.pages) == expected_pages, (name, "page count")
    text = "\n".join(page.extract_text() for page in reader.pages)
    for expected in ["BIÊN BẢN BÀN GIAO THIẾT BỊ CNTT", "QA-BG-20261020", "Nguyễn Văn Giao", "Trần Thị Nhận", "0000123456", "20/10/2026 10:15", "20/10/2026 11:20"]:
        assert expected in text, (name, expected)
    assert "BÊN GIAO" in reader.pages[-1].extract_text()
    assert "BÊN NHẬN" in reader.pages[-1].extract_text()
    assert "\ufffd" not in text, (name, "missing glyph")
    action = reader.trailer["/Root"].get("/OpenAction")
    # mPDF sets an ordinary page/zoom destination; executable action dictionaries are forbidden.
    assert action is None or (isinstance(action, list) and len(action) >= 2 and action[1] in ["/XYZ", "/Fit", "/FitH"])
    assert "/AA" not in reader.trailer["/Root"]
    for page in reader.pages:
        assert not page.get("/Annots"), (name, "unexpected annotations")
        assert "/AA" not in page, (name, "unexpected automatic action")
    with pdfplumber.open(path) as pdf:
        for page in pdf.pages:
            assert all(0 <= char["x0"] <= char["x1"] <= page.width and 0 <= char["top"] <= char["bottom"] <= page.height for char in page.chars), (name, "text outside page")
    print(f"{name}: {expected_pages} page(s), Vietnamese/identifiers/time/signatures/bounds verified")
