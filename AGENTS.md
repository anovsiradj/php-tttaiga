## penamaan
- Bulk = Batch/Multiple
- copas = Copy+Paste
- Usor = User Story
- Epik = Epic
- Isu = Issue

# operating system

## OS & Local Server
- **DILARANG** menjalankan *local server* bawaan (seperti `php -S localhost:8000`).
- local server selalu menggunakan apache/httpd.
- local server sudah disediakan, untuk mengetahuinya URL nya, selalu cek `APP_URL` di `.env`.

i am using windows, when you need to execute CLI commands, dont use bash CLI commands, use powershell CLI commands.

this project is already running withing subfolder,
the URL access is at <http://localhost:8400/anoop/tttaiga/>.

## Environment & CLI
- penentuan versi PHP berdasarkan `./composer.json`
- untuk menjalankan perintah PHP gunakan `php84`
- untuk menjalankan perintah composer gunakan `php84c`

## jenis dan modul
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
- jangan pernah tulis HTML di JS, gunakan `<template>`.
- gunakan `anovsiradj/wiet`
- gunakan `anovsiradj/skit`
- gunakan `anovsiradj/web-skit`
- modifikasi langsung library yang masih lokal (wiet,skit,web-skit) jika perlu penyesuaian/perbaikan/perubahan.
- gunakan session PHP untuk otentikasi dan otorisasi
- auto logout jika session expired/timeout, cek response dari API.
