# Attachment Feature — Teammate Setup Guide

The attachment feature (paperclip icon in the reply bar) needs three things set up correctly on each machine: a database column, a way to serve uploaded files, and a PHP setting most default installs get wrong. Follow these in order — skipping the last one is the #1 reason this silently fails.

---

## 1. Pull the latest code

Make sure you have the latest versions of:
- `database/migrations/2026_07_12_000001_add_attachment_to_posts_table.php`
- `app/Models/Post.php`
- `app/Http/Controllers/PostController.php`
- `resources/views/forum/show.blade.php`

## 2. Run the migration

```
php artisan migrate
```

This adds three columns to `posts`: `attachment` (where the file lives), `attachment_type` (so images render differently from documents), `attachment_name` (the original filename).

## 3. Link storage

```
php artisan storage:link
```

Uploaded files get saved to `storage/app/public/attachments/`, which isn't reachable by a browser on its own. This command creates a shortcut (`public/storage`) pointing into it, so uploaded images and files actually load. **Easy to forget** — if you skip this, uploads succeed but every attachment shows as broken.

## 4. Fix PHP's upload temp folder (the tricky one)

This is the step that took the longest to track down, so don't skip it. When a file is uploaded, PHP needs to briefly stage it in a scratch folder *before* Laravel ever sees it. If that folder is missing, wrong, or not writable, the upload silently fails — no error in `laravel.log`, no crash, the page just reloads with nothing happening.

**If you're on Mac or Linux**, your system's default temp folder (`/tmp`) usually already works fine — try uploading an attachment first, and only do the steps below if it fails.

**If you're on Windows** (especially with Laravel Herd, XAMPP, Laragon, or any bundled PHP tool), do this:

**4a. Find out which `php.ini` your PHP is actually using:**
```
php --ini
```
Look specifically at the **"Loaded Configuration File"** line. This part matters a lot — Windows setups often have *multiple* `php.ini` files sitting around from different tools (Herd, XAMPP, a standalone PHP install, etc.), and it's easy to edit the wrong one and have nothing change. Only the file named on that exact line is the one PHP is really reading.

**4b. Create a folder PHP can write to:**
```
mkdir C:\php-tmp
```
(Any folder you know is writable works — this is just a clean, simple choice.)

**4c. Open the `php.ini` file from step 4a**, search for `upload_tmp_dir`, and set it to:
```ini
upload_tmp_dir = "C:\php-tmp"
```
Make sure there's **no `;` at the start of the line** — that character comments the line out, which is the same as not setting it at all.

**4d. Fully stop and restart your server** — `Ctrl+C` in the terminal running `php artisan serve`, then run it again. This is not optional: `php.ini` is only read once, when PHP starts up. A browser refresh will never pick up this change.

## 5. (Optional) Attachments in PDF export

If you also want attached images to show up when someone clicks "Export PDF" on a topic, run:
```
composer require barryvdh/laravel-dompdf
```
The PDF template already knows how to render attachments — it just needs this package installed to generate PDFs at all.

---

## 6. Test it

- [ ] Open any topic
- [ ] Click the paperclip icon, pick an image
- [ ] Send the reply
- [ ] Confirm the image shows up inline inside your message bubble (not just a filename)
- [ ] Try a non-image file too (PDF, doc, zip) — it should appear as a small downloadable "📎 filename" chip instead

## If it's still not working

Check the terminal running `php artisan serve` right after a failed attempt. If you see:
```
PHP Warning: PHP Request Startup: File upload error – unable to create a temporary file
```
that means step 4 didn't fully take — go back and double-check:
- You edited the *exact* file named in "Loaded Configuration File" (not a different `php.ini` elsewhere on your machine)
- The `;` is actually gone from the front of `upload_tmp_dir`
- The server was fully restarted, not just refreshed in the browser
