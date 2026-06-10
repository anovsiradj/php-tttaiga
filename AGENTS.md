
## jenis dan modul
- user
- member
- sprint
- project
- epic
- usor (alias for User Story)
- task
- isu

## visi & misi
- menyederhanakan workflow Taiga menjadi flat

## kebutuhan
- bulk create semua jenis
- bulk update semua jenis
- bulk delete semua jenis
- custom bulk action semua jenis
- shared filter semua jenis

shared filter adalah mempertahankan filter yang digunakan pada suatu modul,
ketika ganti laman ke modul lain, gunakan filter yang sama, sehingga data yang muncul konsisten.

custom bulk action,
salah satu tujuannya adalah memberi prefix kesemua judul pada suatu kelompok jenis.

## development
- jangan pernah tulis HTML di JS, gunakan `<template>`.
- gunakan `anovsiradj/wiet` jika butuh custom component
- custom `anovsiradj/wiet` jika perlu penyesuaian/perbaikan/perubahan.
- gunakan session PHP untuk otentikasi dan otorisasi
- auto logout jika session expired/timeout, cek response dari API.
