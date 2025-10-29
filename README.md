## Dwiprasetia API

RESTful API for a personal blog platform with public content, user interactions, and an admin CMS. Built on Laravel 12 with Sanctum authentication.

---

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ (for asset build, optional during API-only work)
- PostgreSQL (default connection)
- PHP extensions: openssl, pdo_pgsql, mbstring, tokenizer, xml, ctype, json, fileinfo

---

## 1. Setup

```bash
cp .env.example .env          # or configure your own
composer install
php artisan key:generate

# adjust DB_* values in .env to point at your Postgres instance

php artisan migrate           # runs all tables, including sessions/personal tokens
php artisan storage:link      # allows serving uploaded images
```

Optional development assets:

```bash
npm install
npm run dev
```

---

## 2. Seeding an Admin

All new accounts default to role `user`. Promote an account manually (replace the ID/email as needed):

```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'admin@example.com')->first();
>>> $user->role = 'admin';
>>> $user->save();
>>> exit
```

---

## 3. Authentication Flow (Bearer Tokens)

1. Register or login to receive a Sanctum plain-text token.

   ```http
   POST /api/v1/auth/register
   {
     "name": "Jane Doe",
     "email": "jane@example.com",
     "password": "secret123",
     "password_confirmation": "secret123",
     "bio": "Optional"
   }

   POST /api/v1/auth/login
   {
     "email": "jane@example.com",
     "password": "secret123"
   }
   ```

2. Store the returned `token` on the client.
3. Send it with subsequent requests:

   ```
   Authorization: Bearer <token>
   Accept: application/json
   ```

4. `POST /api/v1/auth/logout` invalidates the current token.

Cookie-based Sanctum SPA mode is disabled by default; use bearer tokens for integrations.

---

## 4. Core Features

### Public

- View published posts with author, like count, comment count.
- View nested comments (visible status only).
- Submit contact messages.
- Read site settings (name, logo, about).

### Authenticated User

- Update profile (name, email, password, bio, avatar upload).
- Comment and reply on posts, edit/delete own comments.
- Report inappropriate comments.
- Like posts (idempotent).
- Save/bookmark posts.
- View own saved posts list.

### Admin

- Full post CRUD, including draft status and featured image uploads.
- Review comment reports (mark pending/reviewed/dismissed, hide/restore comment).
- View dashboard stats and recent posts snapshot.
- Manage site settings (name, about, logo upload).
- Review and delete contact messages.

---

## 5. API Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/v1/auth/register` | Public | Register new user and return token |
| POST | `/api/v1/auth/login` | Public | Login and return token |
| POST | `/api/v1/auth/logout` | Bearer | Revoke current token |
| GET | `/api/v1/auth/me` | Bearer | Fetch current user |
| GET | `/api/v1/profile` | Bearer | Profile details |
| PUT/PATCH | `/api/v1/profile` | Bearer | Update profile (supports avatar upload) |
| GET | `/api/v1/posts` | Optional | List posts (admins can filter status/drafts) |
| GET | `/api/v1/posts/{post}` | Optional | Single post (drafts admin-only) |
| POST | `/api/v1/posts` | Admin | Create post |
| PUT/PATCH | `/api/v1/posts/{post}` | Admin | Update post |
| DELETE | `/api/v1/posts/{post}` | Admin | Delete post |
| GET | `/api/v1/posts/{post}/comments` | Optional | Paginated top-level comments with replies |
| POST | `/api/v1/posts/{post}/comments` | Bearer | Create comment or reply |
| PATCH | `/api/v1/comments/{comment}` | Bearer | Update comment (owner/admin) |
| DELETE | `/api/v1/comments/{comment}` | Bearer | Delete comment (owner/admin) |
| POST | `/api/v1/comments/{comment}/report` | Bearer | Report comment |
| GET | `/api/v1/admin/comment-reports` | Admin | List reports |
| PATCH | `/api/v1/admin/comment-reports/{report}` | Admin | Update report status and moderate |
| POST | `/api/v1/posts/{post}/like` | Bearer | Like post |
| DELETE | `/api/v1/posts/{post}/like` | Bearer | Unlike post |
| POST | `/api/v1/posts/{post}/save` | Bearer | Bookmark post |
| DELETE | `/api/v1/posts/{post}/save` | Bearer | Remove bookmark |
| GET | `/api/v1/me/saved-posts` | Bearer | View saved posts |
| POST | `/api/v1/messages` | Public | Submit contact form |
| GET | `/api/v1/admin/messages` | Admin | List contact messages |
| GET | `/api/v1/admin/messages/{message}` | Admin | View message detail |
| DELETE | `/api/v1/admin/messages/{message}` | Admin | Delete message |
| GET | `/api/v1/settings` | Public | Retrieve site settings |
| PUT | `/api/v1/settings` | Admin | Update settings (name/about/logo) |
| GET | `/api/v1/admin/dashboard` | Admin | Summary metrics and recent posts |

> All file upload endpoints expect multipart/form-data with the relevant file field (`avatar`, `featured_image`, `site_logo`).

---

## 6. Postman Tips

- Send `Accept: application/json` to ensure JSON responses.
- Clear cookies for `127.0.0.1` if you ever see HTML responses; HTML indicates the request hit a web route.
- Use Postman tests to persist bearer tokens:

  ```js
  const data = pm.response.json();
  if (data.token) {
    pm.collectionVariables.set('apiToken', data.token);
  }
  ```

  Then set `Authorization: Bearer {{apiToken}}` on protected requests.

---

## 7. Storage & File Management

- Uploaded avatars → `storage/app/public/avatars`
- Post images → `storage/app/public/posts`
- Site logo → `storage/app/public/settings`
- Run `php artisan storage:link` so `/storage/...` URLs in responses resolve.
- Old files are deleted automatically when replaced.

---

## 8. Testing

```bash
php artisan test
```

Add feature tests under `tests/Feature` to cover key workflows (auth, posts, comments, admin actions).

---

## 9. Troubleshooting

- **401 Unauthorized:** Missing/invalid bearer token. Login again and update `Authorization` header.
- **405 on `/api/v1/auth/me`:** Request redirected to `/login` because token header absent. Include bearer token and `Accept: application/json`.
- **HTML error page:** API request missing `Accept: application/json`; Laravel returns the debug HTML view.
- **`relation "sessions" does not exist`:** Run `php artisan migrate`; we provide a migration for the sessions table.

---

## 10. License

This project inherits the Laravel MIT license. You may adapt and redistribute with attribution.
