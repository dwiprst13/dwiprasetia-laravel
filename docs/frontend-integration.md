## Frontend Integration Guide

Panduan ini menjelaskan bagaimana tim FE berinteraksi dengan Dwiprasetia API. Seluruh contoh URL menggunakan basis `http://127.0.0.1:8000/api/v1`; ganti sesuai environment.

---

## 1. Konfigurasi Dasar

- **Base URL:** `http://127.0.0.1:8000/api/v1`
- **Header default**
  - `Accept: application/json`
  - `Content-Type: application/json` (ubah ke multipart saat mengirim file)
  - `Authorization: Bearer <token>` (untuk endpoint yang butuh login)
- **Error JSON**
  ```json
  {
    "message": "Human readable",
    "errors": {
      "field": ["Validation detail"]
    }
  }
  ```

---

## 2. Alur Pengguna

### 2.1 Pengunjung (tanpa login)

| Langkah | Tujuan | HTTP |
|---------|--------|------|
| 1 | Ambil daftar post | `GET /posts?search=&author_id=&per_page=` |
| 2 | Lihat detail post | `GET /posts/{post}` |
| 3 | Baca komentar publik | `GET /posts/{post}/comments?per_page=` |
| 4 | Kirim pesan kontak | `POST /messages` |
| 5 | Baca pengaturan situs | `GET /settings` |
| 6 | Registrasi akun | `POST /auth/register` (langsung menerima token) |
| 7 | Login | `POST /auth/login` (menerima token) |

### 2.2 Pengguna Login (role = user)

Setelah menerima token dari register/login:

| Langkah | Tujuan | HTTP |
|---------|--------|------|
| 1 | Simpan token FE | — (simpan di storage aplikasi) |
| 2 | Muat profil | `GET /auth/me` atau `GET /profile` |
| 3 | Update profil | `PUT /profile` (multipart jika kirim avatar) |
| 4 | Logout | `POST /auth/logout` (hapus token lokal) |
| 5 | Tambah komentar/reply | `POST /posts/{post}/comments` |
| 6 | Edit komentar sendiri | `PATCH /comments/{comment}` |
| 7 | Hapus komentar sendiri | `DELETE /comments/{comment}` |
| 8 | Laporkan komentar | `POST /comments/{comment}/report` |
| 9 | Like / Unlike post | `POST` / `DELETE /posts/{post}/like` |
| 10 | Simpan / batal simpan post | `POST` / `DELETE /posts/{post}/save` |
| 11 | Lihat daftar post tersimpan | `GET /me/saved-posts` |

### 2.3 Admin (role = admin)

Admin memiliki semua hak user biasa plus akses ke dashboard & CMS:

| Langkah | Tujuan | HTTP |
|---------|--------|------|
| 1 | Buka dashboard ringkasan | `GET /admin/dashboard` |
| 2 | Kelola post | `POST /posts`, `PUT/PATCH /posts/{post}`, `DELETE /posts/{post}` |
| 3 | Kelola status post (draft/publish) | Update field `status` via `PUT/PATCH /posts/{post}` |
| 4 | Review laporan komentar | `GET /admin/comment-reports` |
| 5 | Tindak lanjuti laporan | `PATCH /admin/comment-reports/{report}` dengan `status` & `moderation_action` |
| 6 | Kelola pengaturan situs | `PUT /settings` (nama, deskripsi, logo) |
| 7 | Kelola pesan kontak | `GET/GET/DELETE /admin/messages[/ {message}]` |
| 8 | Kelola akun user | `GET /admin/users`, `GET /admin/users/{user}`, `PUT/PATCH /admin/users/{user}` |
| 9 | Kelola media library | `GET /admin/media`, `POST /admin/media`, `PUT/PATCH /admin/media/{media}`, `DELETE /admin/media/{media}` |

---

## 3. Daftar Endpoint Menurut Role

### 3.1 Public (tanpa token)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/register` | Registrasi + auto login (token dikembalikan) |
| POST | `/auth/login` | Login (token dikembalikan) |
| GET | `/posts` | Daftar post terbit |
| GET | `/posts/{post}` | Detail post (draft tidak terlihat) |
| GET | `/posts/{post}/comments` | Komentar publik dengan reply |
| POST | `/messages` | Kirim pesan kontak |
| GET | `/settings` | Info situs |

### 3.2 Authenticated User (token wajib)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/logout` | Cabut token |
| GET | `/auth/me` | Data user login |
| GET | `/profile` | Sama dengan `/auth/me` |
| PUT/PATCH | `/profile` | Update profil + upload avatar |
| POST | `/posts/{post}/comments` | Buat komentar/reply |
| PATCH | `/comments/{comment}` | Edit komentar sendiri / admin |
| DELETE | `/comments/{comment}` | Hapus komentar sendiri / admin |
| POST | `/comments/{comment}/report` | Laporkan komentar orang lain |
| POST | `/posts/{post}/like` | Like post |
| DELETE | `/posts/{post}/like` | Unlike post |
| POST | `/posts/{post}/save` | Simpan post |
| DELETE | `/posts/{post}/save` | Hapus dari post tersimpan |
| GET | `/me/saved-posts` | Tampilkan post tersimpan |

### 3.3 Admin Only

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/dashboard` | Statistik user/post/komentar/report |
| POST | `/posts` | Buat post baru (multipart jika upload gambar) |
| PUT/PATCH | `/posts/{post}` | Update post (include status draft/publish) |
| DELETE | `/posts/{post}` | Hapus post |
| GET | `/admin/comment-reports` | Daftar laporan komentar |
| PATCH | `/admin/comment-reports/{report}` | Set status `pending/reviewed/dismissed`, optional `moderation_action` (`hide/restore`) |
| PUT | `/settings` | Update site name/about/logo |
| GET | `/admin/messages` | Daftar pesan masuk |
| GET | `/admin/messages/{message}` | Detail pesan |
| DELETE | `/admin/messages/{message}` | Hapus pesan |
| GET | `/admin/users` | Daftar user (`search`, `role`, `per_page`) |
| GET | `/admin/users/{user}` | Detail user |
| PUT/PATCH | `/admin/users/{user}` | Update nama, email, bio, password, role (tidak bisa menurunkan admin terakhir) |
| GET | `/admin/media` | Daftar media (`search`, `user_id`, `mime`, `per_page`) |
| POST | `/admin/media` | Upload gambar (field `file`, optional `alt_text`, `caption`) |
| GET | `/admin/media/{media}` | Detail media untuk preview |
| PUT/PATCH | `/admin/media/{media}` | Update metadata / ganti file |
| DELETE | `/admin/media/{media}` | Hapus media beserta file fisik |

---

## 4. Detail Payload & Response

### 4.1 Auth

#### Register
```http
POST /auth/register
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "bio": "Optional"
}
```
Response `201`:
```json
{
  "token": "plain-text-token",
  "token_type": "Bearer",
  "user": { ...UserResource }
}
```

#### Login
```http
POST /auth/login
{
  "email": "jane@example.com",
  "password": "secret123"
}
```
Response `200` sama formatnya.

#### User Resource
```json
{
  "id": 1,
  "name": "Jane Doe",
  "email": "jane@example.com",
  "role": "user",
  "bio": "Optional",
  "avatar_path": "avatars/file.jpg",
  "avatar_url": "http://127.0.0.1:8000/storage/avatars/file.jpg",
  "created_at": "2025-10-29T11:20:01.000000Z",
  "updated_at": "2025-10-29T11:20:01.000000Z"
}
```

### 4.2 Post & Comment

#### List Post
- Endpoint: `GET /posts`
- Query params umum: `search`, `author_id`, `per_page` (maks 100)
- Tambahan khusus admin: `status=all|published|draft`  
  - default `all` (menampilkan semua)  
  - `published` hanya publik  
  - `draft` hanya draf
- Pengguna biasa selalu otomatis mendapat post berstatus `published`.
- Response menggunakan format paginasi Laravel (`data`, `links`, `meta`).

#### Post Resource
```json
{
  "id": 5,
  "title": "Judul Post",
  "slug": "judul-post",
  "content": "Isi panjang...",
  "status": "published",
  "featured_image_path": "posts/xxx.jpg",
  "featured_image_url": "http://127.0.0.1:8000/storage/posts/xxx.jpg",
  "created_at": "2025-10-29T12:00:00.000000Z",
  "updated_at": "2025-10-29T12:05:00.000000Z",
  "author": { ...UserResource },
  "likes_count": 10,
  "comments_count": 4
}
```

#### Comment Resource
```json
{
  "id": 12,
  "post_id": 5,
  "parent_id": null,
  "body": "Komentar utama",
  "status": "visible",
  "reports_count": 0,
  "created_at": "2025-10-29T12:10:00.000000Z",
  "updated_at": "2025-10-29T12:10:00.000000Z",
  "author": { ...UserResource },
  "replies_count": 2,
  "replies": [
    {
      "id": 13,
      "parent_id": 12,
      "body": "Reply komentar",
      "...": "..."
    }
  ]
}
```

#### Comment Report Resource (Admin)
```json
{
  "id": 3,
  "comment_id": 12,
  "status": "pending",
  "reason": "Spam link",
  "handled_by": null,
  "handled_at": null,
  "created_at": "2025-10-29T12:30:22.000000Z",
  "updated_at": "2025-10-29T12:30:22.000000Z",
  "comment": { ...CommentResource },
  "reporter": { ...UserResource },
  "handler": null
}
```

### 4.3 Settings & Messages

#### Setting Resource
```json
{
  "id": 1,
  "site_name": "Dwiprasetia",
  "site_logo_path": "settings/logo.png",
  "site_logo_url": "http://127.0.0.1:8000/storage/settings/logo.png",
  "about": "Deskripsi",
  "updated_at": "2025-10-29T12:40:00.000000Z"
}
```

#### Message Resource
```json
{
  "id": 4,
  "name": "Visitor",
  "email": "visitor@example.com",
  "subject": "Pertanyaan",
  "body": "Halo admin...",
  "created_at": "2025-10-29T12:50:00.000000Z"
}
```

### 4.4 Media

#### Media Resource
```json
{
  "id": 7,
  "disk": "public",
  "path": "media/banner.png",
  "url": "http://127.0.0.1:8000/storage/media/banner.png",
  "original_name": "banner.png",
  "mime_type": "image/png",
  "extension": "png",
  "size": 245678,
  "alt_text": "Banner hero",
  "caption": "Banner untuk halaman utama",
  "uploaded_by": { ...UserResource },
  "created_at": "2025-10-30T05:12:00.000000Z",
  "updated_at": "2025-10-30T05:12:00.000000Z"
}
```

#### Catatan
- File disimpan pada disk `public` dengan path `storage/app/public/media`.
- Endpoint upload & update membutuhkan multipart/form-data dengan field `file`.
- FE dapat menyimpan daftar URL untuk dipakai di rich editor atau picker gambar.

---

## 5. Contoh Implementasi (Axios)

```js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api/v1',
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('dwiprasetiaToken');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default api;
```

```js
// Login
const { data } = await api.post('/auth/login', credentials);
localStorage.setItem('dwiprasetiaToken', data.token);

// Ambil posts
const posts = await api.get('/posts', { params: { search: 'laravel' } });

// Admin: filter draft
const draftPosts = await api.get('/posts', { params: { status: 'draft' } });

// Simpan post
await api.post(`/posts/${postId}/save`);

// Admin: update role user
await api.patch(`/admin/users/${userId}`, { role: 'admin' });
```

---

## 6. Catatan File Upload

- Gunakan multipart/form-data.
- Field file:
  - `avatar` (profil)
  - `featured_image` (post)
  - `site_logo` (settings)
  - `file` (media library)
- Server menghapus file lama ketika file baru diunggah.
- Pastikan `Storage::link` sudah dibuat agar URL `.../storage/...` dapat diakses.

---

## 7. Tips Pengujian FE

1. Registrasi → pastikan token diterima → simpan & cek `/auth/me`.
2. Login ulang → token baru replace lama.
3. Update profil + upload avatar → cek `avatar_url` berubah.
4. Like/Unlike → pantau respon `{ liked, likes_count }`.
5. Tambah komentar & reply → UI menampilkan nested structure.
6. Laporkan komentar → cek admin list laporan meningkat.
7. Simpan post → muncul di `/me/saved-posts`.
8. Admin: buat post, ubah status, upload featured image.
9. Admin: update site settings (logo tampil di FE).
10. Admin: cek pesan masuk & hapus.

---

## 8. Troubleshooting Umum

- **HTML (Laravel 12.36.0) muncul:** request salah URL atau header `Accept` hilang → periksa path `/api/v1/...`.
- **401 Unauthorized:** token hilang/expired → login ulang.
- **403 Forbidden:** role tidak memiliki izin (butuh admin).
- **405 pada `/auth/me`:** token tidak dikirim sehingga redirect ke `/login` → pastikan header bearer.
- **422 Validation Error:** baca `errors` untuk tampilkan pesan di form.
- **500 Server Error:** lihat `storage/logs/laravel.log` dan koordinasi dengan backend.

---

Hubungi tim backend untuk kebutuhan endpoint tambahan atau perubahan payload. Gunakan Postman untuk verifikasi sebelum implementasi FE guna menghindari salah format request. Semangat bangun FE-nya! 💪
