## Frontend Integration Guide

This guide summarizes everything the FE team needs to connect to the Dwiprasetia API. All URLs below assume the backend runs at `http://127.0.0.1:8000`—adjust the base URL as needed.

---

## 1. Base Configuration

- **Base URL:** `http://127.0.0.1:8000/api/v1`
- **Default Headers on every request:**
  - `Accept: application/json`
  - `Content-Type: application/json` (or multipart boundary for file uploads)
  - `Authorization: Bearer <token>` (only after user logs in/registers; omit on public endpoints)
- **Timeout:** 30 s recommended
- **Error handling:** Treat non-2xx responses as failures. API returns JSON in the format:
  ```json
  {
    "message": "Readable error message",
    "errors": {
      "field_name": ["Validation detail..."]
    }
  }
  ```

---

## 2. Auth Flow

### Register
- **Endpoint:** `POST /auth/register`
- **Body:**
  ```json
  {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "secret123",
    "password_confirmation": "secret123",
    "bio": "Optional bio"
  }
  ```
- **Response:** `201 Created` with `{ token, token_type: "Bearer", user }`.
- **FE Action:** Store `token` securely (state store or secure storage). User object contains role (`admin` or `user`).

### Login
- **Endpoint:** `POST /auth/login`
- **Body:**
  ```json
  {
    "email": "jane@example.com",
    "password": "secret123"
  }
  ```
- **Response:** `200 OK` with new `{ token, token_type, user }`.

### Logout
- **Endpoint:** `POST /auth/logout`
- **Headers:** `Authorization: Bearer <token>`
- **Response:** `200 OK` with `{ "message": "Logged out successfully." }`
- **FE Action:** Remove token from storage.

### Current User
- **Endpoint:** `GET /auth/me`
- **Headers:** `Authorization: Bearer <token>`
- **Response:** `{ user: { ... } }`
- **Use case:** Refresh profile state on app boot.

---

## 3. Profile Management

- **Get Profile:** `GET /profile`
  - `Authorization: Bearer <token>`
  - Same payload as `/auth/me`.

- **Update Profile:** `PUT /profile`
  - `Authorization: Bearer <token>`
  - **Body:** multipart/form-data when sending `avatar`; otherwise JSON.
    - Fields: `name?`, `email?`, `password?`, `password_confirmation?`, `bio?`, `avatar?` (file)
  - **Response:** `200 OK` with `{ message, user }`.
  - **FE Action:** After success, update stored user data.

---

## 4. Posts

### List Posts
- **Endpoint:** `GET /posts`
- **Query Params (optional):** `search`, `author_id`, `per_page` (max 100)
- **Admin extra param:** `status=published|draft` (only works for admin tokens)
- **Response:** Laravel pagination format with `data` array of post resources.

### Post Resource Structure
```json
{
  "id": 1,
  "title": "...",
  "slug": "...",
  "content": "...",
  "status": "published",
  "featured_image_path": "posts/...",
  "featured_image_url": "http://.../storage/posts/..",
  "created_at": "ISO8601",
  "updated_at": "ISO8601",
  "author": { ...UserResource },
  "likes_count": 5,
  "comments_count": 10
}
```

### View Post
- **Endpoint:** `GET /posts/{id}`
- Draft posts only visible to admins.

### Create/Update/Delete (Admin only)
- **Create:** `POST /posts`
  - multipart/form-data
  - Fields: `title` (required), `content`, `status` (`published|draft`), `slug?`, `featured_image?` (file)
- **Update:** `PUT /posts/{id}`
  - Same fields, optional.
- **Delete:** `DELETE /posts/{id}`

### Likes
- **Like:** `POST /posts/{id}/like` (Bearer token)
- **Unlike:** `DELETE /posts/{id}/like`
- **Response:** `{ liked: true/false, likes_count }`

### Saved Posts (Bookmarks)
- **Save:** `POST /posts/{id}/save`
- **Remove:** `DELETE /posts/{id}/save`
- **List saved:** `GET /me/saved-posts` (paginated, includes nested post resource).

---

## 5. Comments

### Fetch Comments
- **Endpoint:** `GET /posts/{id}/comments`
- **Query Params:** `per_page` (default 10, max 100)
- **Response:** paginated top-level comments with replies (only `visible` status for regular users).

### Comment Resource
```json
{
  "id": 12,
  "post_id": 1,
  "parent_id": null,
  "body": "Comment text",
  "status": "visible",
  "reports_count": 0,
  "created_at": "...",
  "updated_at": "...",
  "author": { ...UserResource },
  "replies_count": 2,
  "replies": [ ...nested CommentResource ]
}
```

### Create Comment
- **Endpoint:** `POST /posts/{id}/comments`
- **Headers:** `Authorization: Bearer <token>`
- **Body:** `{ "body": "text", "parent_id": <optional comment id> }`
- **Response:** `201 Created` with full comment resource.

### Edit/Delete Comment
- **Edit:** `PATCH /comments/{id}` body `{ "body": "updated text" }`
- **Delete:** `DELETE /comments/{id}`
- Only comment owner or admin can modify/delete.

### Report Comment
- **Endpoint:** `POST /comments/{id}/report`
- **Body:** `{ "reason": "optional text" }`
- User cannot report own comment; duplicate reports update reason.

---

## 6. Admin-Only Features

Require token belonging to a user with `role = admin`.

### Dashboard Snapshot
- `GET /admin/dashboard`
- Returns:
  ```json
  {
    "totals": {
      "users": 12,
      "admins": 1,
      "posts": 32,
      "published_posts": 29,
      "draft_posts": 3,
      "comments": 100,
      "pending_comment_reports": 4,
      "messages": 5
    },
    "recent_posts": [ ...PostResource (latest 5) ]
  }
  ```

### Comment Reports
- **List:** `GET /admin/comment-reports`
- **Update:** `PATCH /admin/comment-reports/{id}`
  - Body: `{ "status": "pending|reviewed|dismissed", "moderation_action": "hide|restore|null" }`
  - `moderation_action = hide` sets comment status to `hidden`; `restore` sets to `visible`.

### Site Settings
- **View:** `GET /settings` (public)
- **Update:** `PUT /settings`
  - multipart/form-data
  - Fields: `site_name` (required), `about?`, `site_logo?` (file)
  - Response: updated settings resource (`site_logo_url` is ready to display).

### Contact Messages
- **Submit (public):** `POST /messages`
- **Admin list:** `GET /admin/messages`
- **Show:** `GET /admin/messages/{id}`
- **Delete:** `DELETE /admin/messages/{id}`

---

## 7. File Upload Notes

- Use multipart/form-data and include files under their respective field names:
  - `avatar` (profile)
  - `featured_image` (post)
  - `site_logo` (settings)
- Backend returns both path and `..._url` for immediate use.
- Existing files are deleted when a new file is uploaded for the same record.

---

## 8. State Management Recommendations

1. **Authentication token**
   - Save plain-text token in a secure store (e.g., Vuex/Pinia + `localStorage` or cookies with httpOnly flag through proxy).
   - Attach token to `Authorization` header via HTTP client interceptors (axios/fetch).

2. **User object**
   - Store the `user` object from login/register.
   - Refresh with `GET /auth/me` on app boot if token exists.

3. **Error handling**
   - Validation errors come with `422` status; read `errors` object for field messages.
   - 401/403: redirect user to login or show permission message.
   - 404: resource not found (e.g., post slug mismatched).

4. **Pagination**
   - Laravel pagination responses include `meta` and `links` objects. Use `meta.current_page`, `meta.last_page`, `links.next` etc. for UI controls.

---

## 9. Sample Axios Setup

```js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api/v1',
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('dwiprasetiaToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

Usage example:

```js
// login
const { data } = await api.post('/auth/login', credentials);
localStorage.setItem('dwiprasetiaToken', data.token);

// fetch posts
const posts = await api.get('/posts', { params: { search: 'laravel' } });
```

---

## 10. Frequently Asked Questions

**Q: Why am I receiving HTML instead of JSON?**  
A: Add `Accept: application/json` and ensure the request path matches `/api/v1/...`. HTML response means the request hit a web route (usually due to redirect or missing token).

**Q: Why do I get 405 after hitting `/auth/me`?**  
A: The request likely redirected to `/login` because the bearer token is missing/invalid. Ensure the token header is present.

**Q: How do I know if a user is admin?**  
A: Check `user.role`. Admins see additional UI (post management, reports, settings, etc.).

**Q: How do we handle comment replies?**  
A: When displaying comments, show `data` (top-level). Each comment includes `replies` array already loaded, plus `replies_count` for lazy loading if needed.

---

Contact backend team if new endpoints are required or if you encounter unexpected responses. Always test requests with Postman first to verify payloads and headers before implementing on the frontend.
