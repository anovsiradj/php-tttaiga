## penamaan
- Bulk = Batch/Multiple
- copas = Copy+Paste
- Usor = User Story
- Epik = Epic
- Isu = Issue

# operating system

## OS & Local Server
- **DILARANG** menjalankan custom local server (seperti `php -S localhost:8000` atau `python -m http.server` atau sejenisnya).
- local server harus menggunakan apache/httpd.
- local server sudah disediakan, untuk mengetahuinya URL nya, selalu cek `APP_URL` di `.env`.

## Environment & CLI
- penentuan versi PHP berdasarkan `./composer.json`
- untuk menjalankan perintah PHP gunakan `php84`
- untuk menjalankan perintah composer gunakan `php84c`

I use Windows. If you need to run CLI commands, don't use bash commands, use CMD or PowerShell commands.

## jenis/fitur/modul
- user
- member
- sprint
- project
- epik
- usor
- task
- isu

## visi & misi
- menyederhanakan workflow Taiga menjadi flat

## kebutuhan
- bulk create semua jenis
- bulk update semua jenis
- bulk delete semua jenis
- custom bulk action semua jenis
- shared filter semua list page
- shared input semua form view, yaitu form untuk CRUD termasuk bulk.

shared filter adalah mempertahankan filter yang digunakan pada suatu modul,
ketika ganti laman ke modul lain, gunakan filter yang sama, sehingga data yang muncul konsisten.

shared input adalah autofill semua input di form berdasarkan shared filter.

custom bulk action,
salah satu tujuannya adalah memberi prefix kesemua judul pada suatu kelompok jenis.

## development
- jangan tulis HTML di JS kecuali hanya kode simpel atau beberapa baris kode. gunakan `<template>` atau gunakan `anovsiradj/wiet`.
- gunakan `anovsiradj/php-skit`
- gunakan `anovsiradj/web-skit`
- modifikasi langsung library yang masih lokal (wiet,skit,web-skit) jika perlu penyesuaian/perbaikan/perubahan.
- gunakan session PHP untuk otentikasi dan otorisasi
- auto logout jika session expired/timeout, cek response dari API.
