# AGENT INITIALIZATION
Welcome to the project. Your core operating instructions, behavioral boundaries, and project memory are strictly managed by a separate configuration.

**CRITICAL DIRECTIVE:**
Before executing ANY task, analyzing the workspace, or answering the user, you MUST thoroughly read and assimilate the instructions located at:
👉 `./.agents/BRAINS.md`

*Note: Do NOT blindly load all files inside `./.agents/brains/` upfront. Read `BRAINS.md` first to apply the required lazy-loading and lookup procedures.*

# Agent Development Guidelines

## Terminologi
- copas = Copy+Paste
- Bulk = Batch/Multiple
- Usor = User Story
- Epik = Epic
- Isu = Issue

## Local Server
- **DILARANG** menjalankan manual local server seperti `php -S localhost:8000` atau `python -m http.server` atau sejenisnya.
- local server harus menggunakan apache/httpd.
- local server sudah disediakan, untuk mengetahuinya URL nya, selalu cek `APP_URL` di `.env`.

## Environment & CLI
- penentuan versi PHP berdasarkan `./composer.json`
- untuk menjalankan perintah PHP gunakan `php84`.
- untuk menjalankan perintah composer gunakan `php84c`.

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
- jangan tulis HTML di JS kecuali hanya kode simpel atau hanya beberapa baris kode. gunakan `<template>` atau gunakan `anovsiradj/wiet`.
- gunakan `anovsiradj/php-skit`
- gunakan `anovsiradj/web-skit`
- modifikasi langsung vendor lokal (wiet,php-skit,web-skit) jika perlu penyesuaian/perbaikan/perubahan.
- gunakan session PHP untuk otentikasi dan otorisasi
- auto logout jika session expired/timeout, cek response dari API.

always provides unit testing, browser testing, integration testing.

gunakan `DEBUG_USERNAME` dan `DEBUG_PASSWORD` di `.env` untuk login.
