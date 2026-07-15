# InfinityFree Deployment

Use this project structure on InfinityFree:

- Put the Laravel application files outside the web root if possible.
- Point the web root to `public_html/`.
- Set `APP_PUBLIC_PATH=public_html` in your `.env` on the server.
- Set `APP_BASE_PATH` to the Laravel root relative to the web root.
  - Example: `..` if the app lives one level above `public_html`.
  - Example: `../laravel-app` if your app folder is a sibling of the web root.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `APP_URL` to your InfinityFree domain.
- Set `FILESYSTEM_DISK=public`.
- Set `SESSION_DRIVER=file`.
- Set `CACHE_STORE=file`.
- Set `QUEUE_CONNECTION=sync`.

Before upload, install PHP dependencies locally and build assets:

```bash
composer install --no-dev --optimize-autoloader
npm run build:infinityfree
```

Then upload the generated `vendor/` directory and `public_html/build` directory.

Do not rely on Docker, Render-specific files, or queue workers on InfinityFree.
