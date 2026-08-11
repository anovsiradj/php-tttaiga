# Tahap 5: Solidifikasi & Integrasi php-skit + web-skit

Setelah audit 001a, beberapa janji arsitektur belum terpenuhi. Fase ini fokus:
- Menepati janji arsitektur yang sudah direncanakan (TTTaiga.API, TTTaiga.Form)
- Mulai integrasi php-skit + web-skit secara konkret
- Posisikan tttaiga sebagai PoC yang proper untuk kedua lib

---

## 5.1: Implementasi `TTTaiga.API` — Sentralisasi AJAX

**ALASAN:** `taiga-core.js` punya `TTTaiga.API: {}` kosong padahal Tahap 1 menjanjikan sentralisasi AJAX. Semua module duplikasi header boilerplate di setiap `$.ajax()`.

**AKSI:**
- `TTTaiga.API.request(method, endpoint, data, opts)` — handle header (Authorization, Content-Type, X-Taiga-Api-Url) otomatis
- `TTTaiga.API.get(endpoint, params)` — GET wrapper
- `TTTaiga.API.post(endpoint, data)` — POST wrapper
- `TTTaiga.API.patch(endpoint, data)` — PATCH wrapper
- `TTTaiga.API.delete(endpoint)` — DELETE wrapper
- Auto-handle 401 → redirect login
- Refactor `app-*.js` secara bertahap

**TERKAIT:** `assets/taiga-core.js`, `assets/app-*.js`

---

## 5.2: Implementasi `TTTaiga.Form` — Sentralisasi Form CRUD

**ALASAN:** Sama, `TTTaiga.Form: {}` kosong. Single create/update modals tidak standar, `.serializeArray()` tidak dipakai.

**AKSI:**
- `TTTaiga.Form.create(endpoint, data, opts)` — POST + reload + toast
- `TTTaiga.Form.update(endpoint, id, version, data, opts)` — PATCH + reload + toast
- `TTTaiga.Form.populate(formId, data)` — isi form dari objek (edit mode)
- Integrasikan `.serializeArray()` untuk ambil data form
- Refactor single form handler di setiap module JS

**TERKAIT:** `assets/taiga-core.js`, `app/partials/*_single_form.php`, `assets/app-*.js`

---

## 5.3: Ekstrak Shared Filter Logic ke `taiga-core.js` + Hapus Duplikasi `app.js`

**ALASAN:** Semua module masih include `assets/app.js` + `assets/taiga-core.js`. Logika shared filter, sanitizer, auth ada di `app.js` — harusnya di `taiga-core.js`.

**AKSI:**
- Pindahkan `tttaigaInstallHtmlSanitizer()` ke `taiga-core.js`
- Pindahkan shared filter functions (`tttaiga*`) ke `taiga-core.js` (atau file `taiga-shared-filter.js` jika terlalu besar)
- Pindahkan auth check / 401 handler ke `TTTaiga.API`
- Hapus `<script src="assets/app.js">` dari semua `*.php` (kecuali `login.php` jika masih perlu)

---

## 5.4: Gunakan web-skit untuk Widget Bootstrap 5

**ALASAN:** `widgets/twbs/` di web-skit sudah punya dark mode toggle. tttaiga harus jadi PoC pemakaian web-skit.

**AKSI:**
- Load `widgets/twbs/v5-dark-mode-toggle.*` di layout
- Identifikasi komponen UI lain yang bisa diekstrak ke web-skit (toast notification, modal patterns, card grid)
- Jika ada komponen yang reusable, pindahkan ke web-skit, lalu consume dari sana

**TERKAIT:** `C:\projects\anoop\web-skit`, `app/layouts/`, `assets/`

---

## 5.5: Gunakan php-skit untuk Backend Logic

**ALASAN:** `app/helpers/App.php` saat ini extend pattern dari php-skit's `App.php`. Tapi php-skit's `App.php` isinya kosong (placeholder).

**AKSI:**
- Implementasi proper di `php-skit/src/App.php` (config loader, env resolver, basic bootstrap)
- Refactor `app/helpers/App.php` untuk extend `anovsiradj\skit\App` secara langsung
- Identifikasi helper lain di tttaiga yang bisa dipindahkan ke php-skit (TaigaApiConfig bisa jadi contoh)

**TERKAIT:** `C:\projects\anoop\php-skit`, `app/helpers/`

---

## 5.6: Standarisasi Bulk Delete — Hapus Sisa `confirm()`

**ALASAN:** Sisa dari audit. Semua bulk delete harus pakai modal, bukan `confirm()`.

**AKSI:** Audit & fix semua `app-*.js` — pastikan tidak ada `confirm()`.

---

## 5.7: Upgrade `taigaExecuteBulk` dengan Visual Progress

**ALASAN:** Tahap 3 menjanjikan progress bar/spinner per-item.

**AKSI:** Tambah parameter `showProgress` di `taigaExecuteBulk`, inject progress bar ke container.

---

## Prioritas

1. **5.1 + 5.2** — Janji arsitektur inti yang belum ditepati
2. **5.3** — Bersihkan duplikasi JS
3. **5.5** — Actuate php-skit sebagai PoC backend
4. **5.4** — Actuate web-skit sebagai PoC frontend
5. **5.6** — Konsistensi UX (quick win)
6. **5.7** — Improvement opsional
