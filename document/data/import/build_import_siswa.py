r"""
Transform file data siswa format Kemenag/Dinas (8355 TK / 8355 MI) menjadi
file xlsx sesuai template import aplikasi (App\Exports\SiswaImportTemplate).

Keputusan yang di-hardcode di sini (hasil kesepakatan dengan user):
  - Sheet ANG 1-4 pada file MI di-skip (angkatan lama, bukan siswa aktif).
  - NIS TK di-generate numerik 2026001+ berurutan (urutan rombel: Bulan,
    Bintang, Matahari), hanya untuk baris yang ikut diimpor.
  - Alamat siswa TK tidak ada di sumber -> placeholder "-".
  - Baris yang kehilangan tempat_lahir / tanggal_lahir / agama dikeluarkan
    dari import (tidak boleh dikarang, dipakai portal & kwitansi).
  - Kolom wali TK diisi dari data ayah (fallback ke ibu bila ayah kosong).
"""

import argparse
import datetime
import json
import re
import sys
from pathlib import Path

import openpyxl
from openpyxl import Workbook

BASE_COLS = [
    "nis", "nama", "jenis_kelamin", "tempat_lahir", "tanggal_lahir", "agama",
    "alamat", "jenjang", "kelas", "kategori", "status", "keterangan_siswa",
]
MI_COLS = [
    "nisn", "asal_sekolah", "kelas_diterima", "tahun_diterima",
    "nama_ayah", "pendidikan_terakhir_ayah", "pekerjaan_ayah", "email_ayah",
    "nama_ibu", "pendidikan_terakhir_ibu", "pekerjaan_ibu", "email_ibu",
]
TK_COLS = [
    "tahun_diterima",
    "nama_wali", "pekerjaan_wali", "no_hp_wali", "alamat_wali",
    "keterangan_wali", "email_wali",
]

ROMAN = {1: "I", 2: "II", 3: "III", 4: "IV", 5: "V", 6: "VI"}
# Termasuk salah tulis yang benar-benar muncul di file sumber ("Khatolik").
AGAMA_VALID = {"islam": "Islam",
               "kristen": "Kristen", "protestan": "Kristen",
               "kristen protestan": "Kristen",
               "katolik": "Katolik", "katholik": "Katolik",
               "khatolik": "Katolik", "kristen katolik": "Katolik",
               "hindu": "Hindu",
               "buddha": "Buddha", "budha": "Buddha", "budhha": "Buddha",
               "konghucu": "Konghucu", "khonghucu": "Konghucu"}

DATA_START_ROW = 12
NAMA_COL = 4


def cell(ws, r, c):
    """Ambil nilai sel sebagai string bersih, atau None."""
    if not c:
        return None
    v = ws.cell(r, c).value
    if v is None:
        return None
    if isinstance(v, (datetime.datetime, datetime.date)):
        return v
    s = str(v).strip()
    return s or None


def parse_tanggal(v):
    """Normalisasi tanggal ke YYYY-MM-DD. Return None kalau tidak terbaca."""
    if v is None:
        return None
    if isinstance(v, datetime.datetime):
        return v.strftime("%Y-%m-%d")
    if isinstance(v, datetime.date):
        return v.strftime("%Y-%m-%d")
    s = str(v).strip()
    if not s:
        return None
    # "2019-12-10 00:00:00" -> ambil bagian tanggalnya
    m = re.match(r"^(\d{4})-(\d{2})-(\d{2})", s)
    if m:
        return f"{m.group(1)}-{m.group(2)}-{m.group(3)}"
    # "26/06/2022" atau "27/5/2022"
    m = re.match(r"^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$", s)
    if m:
        d, mo, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        try:
            return datetime.date(y, mo, d).strftime("%Y-%m-%d")
        except ValueError:
            return None
    return None


def parse_agama(v):
    if v is None:
        return None
    return AGAMA_VALID.get(str(v).strip().lower())


def parse_tahun(v):
    if v is None:
        return None
    m = re.search(r"(\d{4})", str(v))
    return m.group(1) if m else None


def parse_kelas_diterima(v):
    """MI: '1'..'6' -> 'I'..'VI'. Nilai romawi yang sudah benar dilewatkan."""
    if v is None:
        return None
    s = str(v).strip().upper()
    if s in ROMAN.values():
        return s
    m = re.search(r"(\d)", s)
    if m:
        return ROMAN.get(int(m.group(1)))
    return None


# Peta kolom per layout sheet. Kelas 6 punya kolom STTB ekstra sehingga
# kolom setelah "Asal MI" bergeser 2 posisi.
MI_LAYOUT = {
    "default": dict(nis=2, nisn=3, nama=4, jk=5, tempat=6, tgl=7, agama=8,
                    ayah=9, pend_ayah=10, kerja_ayah=11,
                    ibu=12, pend_ibu=13, kerja_ibu=14,
                    asal=15, dikls=16, thn=17, alamat=18, ket=19),
    "sttb": dict(nis=2, nisn=3, nama=4, jk=5, tempat=6, tgl=7, agama=8,
                 ayah=9, pend_ayah=10, kerja_ayah=11,
                 ibu=12, pend_ibu=13, kerja_ibu=14,
                 asal=15, dikls=18, thn=19, alamat=20, ket=21),
}
TK_LAYOUT = dict(nis=2, nisn=3, nama=4, jk=5, tempat=6, tgl=7, agama=8,
                 ayah=9, pend_ayah=10, kerja_ayah=11,
                 ibu=12, pend_ibu=13, kerja_ibu=14,
                 dikls=15, thn=16)


def detect_mi_layout(ws):
    """Kelas 6 menandai kolom STTB di baris header ke-9."""
    for c in range(1, ws.max_column + 1):
        if str(ws.cell(9, c).value or "").strip().upper() == "STTB":
            return MI_LAYOUT["sttb"]
    return MI_LAYOUT["default"]


def read_rows(ws, layout):
    """Baca baris data mentah dari satu sheet."""
    out = []
    for r in range(DATA_START_ROW, ws.max_row + 1):
        nama = cell(ws, r, NAMA_COL)
        if not nama:
            continue
        row = {k: cell(ws, r, c) for k, c in layout.items()}
        row["_row"] = r
        out.append(row)
    return out


def build_mi(path, kelas_map, kategori, skipped):
    wb = openpyxl.load_workbook(path, data_only=True)
    rows = []
    for sheet, kelas_nama in kelas_map.items():
        ws = wb[sheet]
        layout = detect_mi_layout(ws)
        for src in read_rows(ws, layout):
            tempat = src["tempat"]
            tgl = parse_tanggal(src["tgl"])
            agama = parse_agama(src["agama"])
            if not tempat or not tgl or not agama:
                skipped.append((sheet, src["_row"], src["nama"],
                                _missing(tempat, tgl, agama)))
                continue
            rows.append({
                "nis": str(src["nis"] or "").strip(),
                "nama": src["nama"],
                "jenis_kelamin": (str(src["jk"]).strip().upper()
                                  if src["jk"] else None),
                "tempat_lahir": tempat,
                "tanggal_lahir": tgl,
                "agama": agama,
                # 1 baris MI tidak punya alamat -> placeholder sama seperti TK
                "alamat": src["alamat"] or "-",
                "jenjang": "MI",
                "kelas": kelas_nama,
                "kategori": kategori,
                "status": "Aktif",
                "keterangan_siswa": src["ket"],
                "nisn": src["nisn"],
                "asal_sekolah": src["asal"],
                "kelas_diterima": parse_kelas_diterima(src["dikls"]),
                "tahun_diterima": parse_tahun(src["thn"]),
                "nama_ayah": src["ayah"],
                "pendidikan_terakhir_ayah": src["pend_ayah"],
                "pekerjaan_ayah": src["kerja_ayah"],
                "email_ayah": None,
                "nama_ibu": src["ibu"],
                "pendidikan_terakhir_ibu": src["pend_ibu"],
                "pekerjaan_ibu": src["kerja_ibu"],
                "email_ibu": None,
            })
    return rows


def build_tk(path, kelas_map, kategori, nis_start, skipped):
    wb = openpyxl.load_workbook(path, data_only=True)
    rows = []
    nis = nis_start
    for sheet, kelas_nama in kelas_map.items():
        ws = wb[sheet]
        for src in read_rows(ws, TK_LAYOUT):
            tempat = src["tempat"]
            tgl = parse_tanggal(src["tgl"])
            agama = parse_agama(src["agama"])
            if not tempat or not tgl or not agama:
                skipped.append((sheet, src["_row"], src["nama"],
                                _missing(tempat, tgl, agama)))
                continue
            # Wali diambil dari ayah; kalau ayah kosong, jatuh ke ibu.
            wali_nama = src["ayah"] or src["ibu"]
            wali_kerja = src["kerja_ayah"] if src["ayah"] else src["kerja_ibu"]
            rows.append({
                "nis": str(nis),
                "nama": src["nama"],
                "jenis_kelamin": (str(src["jk"]).strip().upper()
                                  if src["jk"] else None),
                "tempat_lahir": tempat,
                "tanggal_lahir": tgl,
                "agama": agama,
                "alamat": "-",
                "jenjang": "TK",
                "kelas": kelas_nama,
                "kategori": kategori,
                "status": "Aktif",
                "keterangan_siswa": None,
                "tahun_diterima": parse_tahun(src["thn"]),
                "nama_wali": wali_nama,
                "pekerjaan_wali": wali_kerja,
                # walis.no_hp dan walis.alamat NOT NULL tanpa default
                # (2025_11_08_085831_create_walis_table.php) -- nilai kosong
                # membuat Wali::create gagal dan me-rollback seluruh import.
                "no_hp_wali": "-",
                "alamat_wali": "-",
                "keterangan_wali": None,
                "email_wali": None,
            })
            nis += 1
    return rows


def _missing(tempat, tgl, agama):
    m = []
    if not tempat:
        m.append("tempat_lahir")
    if not tgl:
        m.append("tanggal_lahir")
    if not agama:
        m.append("agama")
    return "+".join(m)


def write_xlsx(path, cols, rows):
    wb = Workbook()
    ws = wb.active
    ws.title = "siswa"
    ws.append(cols)
    for r in rows:
        ws.append([r.get(c) for c in cols])
    # NIS & NISN ditulis sebagai teks supaya tidak jadi angka ilmiah di Excel
    for idx, c in enumerate(cols, start=1):
        if c in ("nis", "nisn", "tahun_diterima"):
            for row in range(2, len(rows) + 2):
                ws.cell(row, idx).number_format = "@"
    wb.save(path)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--config", required=True)
    ap.add_argument("--outdir", required=True)
    args = ap.parse_args()

    cfg = json.loads(Path(args.config).read_text(encoding="utf-8"))
    outdir = Path(args.outdir)
    outdir.mkdir(parents=True, exist_ok=True)

    skipped = []
    mi_rows = build_mi(cfg["mi_file"], cfg["mi_kelas"], cfg["kategori"], skipped)
    tk_rows = build_tk(cfg["tk_file"], cfg["tk_kelas"], cfg["kategori"],
                       cfg["tk_nis_start"], skipped)

    mi_out = outdir / "import-siswa-mi-2026-2027.xlsx"
    tk_out = outdir / "import-siswa-tk-2026-2027.xlsx"
    write_xlsx(mi_out, BASE_COLS + MI_COLS, mi_rows)
    write_xlsx(tk_out, BASE_COLS + TK_COLS, tk_rows)

    print(f"MI : {len(mi_rows):3d} baris -> {mi_out}")
    print(f"TK : {len(tk_rows):3d} baris -> {tk_out}")
    if tk_rows:
        print(f"     NIS TK {tk_rows[0]['nis']} .. {tk_rows[-1]['nis']}")
    print(f"\nDikeluarkan ({len(skipped)} baris, field wajib kosong):")
    for sheet, row, nama, miss in skipped:
        print(f"  {sheet:10s} baris {row:3d}  {nama:40s} kosong: {miss}")

    # Cek duplikat NIS di dalam gabungan kedua file
    all_nis = [r["nis"] for r in mi_rows + tk_rows]
    dupes = {n for n in all_nis if all_nis.count(n) > 1}
    if dupes:
        print(f"\nPERINGATAN duplikat NIS: {sorted(dupes)}", file=sys.stderr)
        return 1
    print("\nTidak ada NIS duplikat antar kedua file.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
