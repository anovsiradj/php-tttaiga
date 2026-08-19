# TTTaiga

proxy taiga API untuk mempermudah dan menyederhanakan CRUD,
salah satunya adalah dengan adanya fitur bulk form untuk create/update dan bulk delete.

dokumentasi resmi API <https://docs.taiga.io/api.html>.

## development

menjadikan proyek ini sebagai pilot proyek sekaligus menyempurnakan php-skit dan web-skit.

## assets

karena belum pakai `deno bundle` jadi untuk sekarang pakai symlink dulu.

(windows)

```sh
New-Item -ItemType Junction -Path "C:\projects\anoop\tttaiga\web\assets" -Value "C:\projects\anoop\tttaiga\assets"
```
