# Course Builder Plugin — README

**Plugin Version:** 1.4.7  
**Requires WordPress:** 6.0+  
**Requires PHP:** 8.0  
**Author:** Course Builder Team

---

## 📦 Plugin Overview

Course Builder is a custom WordPress plugin that provides a full course management system with its own admin UI, AJAX-powered CRUD, WooCommerce integration, and beautiful frontend templates — independent of Tutor LMS.

---

## 🚀 Installation

1. Go to **Plugins → Add New → Upload Plugin**
2. Upload `course-builder.zip`
3. Click **Replace current with uploaded** (if updating) or **Activate**
4. Go to **Settings → Permalinks → Save Changes** once after first install

> ⚠️ Never delete + reinstall the plugin. Always use **Replace current with uploaded** to preserve your data.

---

## 🗂️ Plugin Structure

```
course-builder/
├── course-builder.php              ← Main entry, constants, activation hooks
├── uninstall.php                   ← DB cleanup (only if enabled in Settings)
├── includes/
│   ├── class-plugin.php            ← Bootstrap, CPT registration, template routing
│   ├── class-cpt-courses.php       ← cb_course CPT + CRUD + set_post_thumbnail
│   ├── class-cpt-teachers.php      ← cb_teacher CPT (public, slug: /teacher/)
│   ├── class-taxonomy-category.php ← cb_category taxonomy + cb_subcategories table
│   ├── class-ajax-handler.php      ← 10 AJAX endpoints + nonce refresh
│   ├── class-shortcodes.php        ← [cb_categories] + [cb_latest_course]
│   └── class-seed-data.php         ← Manual purge only
├── admin/
│   ├── class-admin.php             ← Menu, settings, enqueue, version banner
│   └── views/
│       ├── courses-list.php        ← All Courses table with filters + pagination
│       ├── course-add.php          ← Add/Edit course form with featured image
│       ├── categories.php          ← Departments management
│       ├── teachers.php            ← Teachers management
│       └── settings.php            ← Data management + shortcode reference
├── templates/
│   ├── single-cb_course.php        ← Full course frontend page
│   └── single-cb_teacher.php       ← Teacher profile page
└── assets/
    ├── css/admin.css               ← Admin design system
    ├── css/course.css              ← Frontend course + teacher styles
    └── js/admin.js                 ← AJAX CRUD, media uploader, preview button
```

---

## 📋 Course Fields (meta keys)

| Field | Meta Key | Type |
|---|---|---|
| Subtitle | `_cb_subtitle` | string |
| Teacher | `_cb_teacher_id` | int (post ID) |
| WooCommerce Product | `_cb_wc_product_id` | int (product ID) |
| Min Age | `_cb_age_min` | int |
| Duration (months) | `_cb_duration_months` | int |
| Live Classes | `_cb_live_classes` | int |
| Video URL | `_cb_video_url` | URL string |
| Featured Image | `_cb_photo_id` + `_thumbnail_id` | int (attachment ID) |
| Learning Objectives | `_cb_learning_objectives` | JSON array |
| Programme Overview | `_cb_programme_overview` | JSON array |
| Course Content | `_cb_course_content` | JSON array of units |
| Additional Support | `_cb_additional_support` | JSON array |

---

## 👩‍🏫 Teacher Fields (meta keys)

| Field | Meta Key | Type |
|---|---|---|
| Designation | `_cb_designation` | string |
| Photo | `_cb_photo_id` | int (attachment ID) |
| Bio | `post_content` | HTML |
| Departments | `_cb_categories` | JSON array of term IDs |


## 🔗 Frontend URLs

| URL | Template |
|---|---|
| `/course/course-slug/` | `templates/single-cb_course.php` |
| `/teacher/teacher-slug/` | `templates/single-cb_teacher.php` |

> After installing or updating, go to **Settings → Permalinks → Save Changes** to flush rewrite rules.

---

## ⚙️ Admin Pages

| Page | Slug |
|---|---|
| All Courses | `admin.php?page=course-builder` |
| Add/Edit Course | `admin.php?page=course-builder-add` |
| Departments | `admin.php?page=course-builder-categories` |
| Teachers | `admin.php?page=course-builder-teachers` |
| Settings | `admin.php?page=course-builder-settings` |

---

## 🔌 AJAX Endpoints

All endpoints require `manage_options` capability + nonce `cb_admin_nonce`.

| Action | Description |
|---|---|
| `cb_get_courses` | Paginated course list (returns `permalink`) |
| `cb_get_course` | Single course data for edit form |
| `cb_save_course` | Create/update course + featured image |
| `cb_delete_course` | Delete course |
| `cb_save_category` | Create/update department |
| `cb_delete_category` | Delete department |
| `cb_save_teacher` | Create/update teacher |
| `cb_delete_teacher` | Delete teacher |
| `cb_get_wc_products` | List WooCommerce products |
| `cb_refresh_nonce` | Refresh expired nonce |
| `cb_dismiss_version_banner` | Dismiss changelog banner |

---

## 📌 Built-in Shortcodes (in plugin)

| Shortcode | Description |
|---|---|
| `[cb_categories]` | Department slider (carousel) |
| `[cb_latest_course]` | Latest single course card |

---

## 📌 Extra Shortcodes (add to functions.php)

These are standalone PHP files to paste into `functions.php` or Code Snippets:

### `cb-courses-shortcode.php`
```
[cb_courses]
[cb_courses limit="10"]
```
Infinite-loop slider of CB courses. Same design as Tutor LMS `tutor_essential_courses_v3`. Shows Live Classes, Duration, Suitable Age, Learn More.

---

### `cb-departments-shortcode.php`
```
[cb_categories]
```
Department category slider. Exact replica of `tutor_parent_categories_v3`. Uses `cb_category` taxonomy + `cb_image_id` term meta.

---

### `cb-department-courses.php`
```
[cb_department_courses]
```
All departments as pill tabs. Click a tab → courses appear below in a grid. Same card design as `[cb_courses]`.

---

### `cb-category-courses.php`
```
[cb_category_courses]
```
**Single page handles ALL departments automatically.**

**Setup (one time only):**
1. Create a WordPress page with slug: `course-category`
2. Add shortcode `[cb_category_courses]`
3. Go to Settings → Permalinks → Save Changes

Then every URL like `/course-category/english-language/` auto-shows matching courses with a full-width hero banner.

---

### `cb-teachers-carousel.php`
```
[cb_teachers]
[cb_teachers count="10"]
```
Teacher carousel. Pixel-perfect replica of `teachers_carousel` shortcode. Uses `cb_teacher` post type, reads `_cb_photo_id` and `_cb_designation`. 4 cards on desktop, 2 on tablet, 1 on mobile.

---

### `cb-mycourses-thumbnail.php`
Replaces the LMSACE My Courses thumbnail lookup. Instead of fetching from Tutor LMS (`_tutor_course_product_id`), fetches from Course Builder (`_cb_wc_product_id` → `cb_course` thumbnail).

---

## 🛡️ Data Safety

By default, **all data is preserved** when you update or reinstall the plugin.

To enable full cleanup on uninstall:
1. Go to **Course Builder → Settings**
2. Tick "Delete ALL data when plugin is uninstalled"
3. Save

---

## 🔄 Updating the Plugin

1. Upload new zip via **Plugins → Add New → Upload Plugin**
2. Click **Replace current with uploaded**
3. Done — all courses, teachers, and departments are preserved

---

## 🐞 Troubleshooting

### Teacher page 404
Go to **Settings → Permalinks → Save Changes**

### Courses stuck on "Loading..."
Hard refresh the page (Ctrl+Shift+R). If still loading, check browser console for JS errors.

### Featured image not showing
Open the course in admin, set the featured image, and save once. The image is stored in both `_thumbnail_id` and `_cb_photo_id`.

### Category courses page not working
Make sure:
- Page slug is exactly `course-category`  
- Department slug in Course Builder matches the URL segment
- Go to Settings → Permalinks → Save Changes after creating the page

---

## 📊 Database Tables

| Table | Purpose |
|---|---|
| `wp_posts` | cb_course, cb_teacher posts |
| `wp_postmeta` | All `_cb_*` meta fields |
| `wp_terms` | cb_category terms |
| `wp_term_taxonomy` | cb_category taxonomy rows |
| `wp_termmeta` | `cb_image_id` per category |
| `wp_cb_subcategories` | Custom subcategories table |
| `wp_options` | `cb_version`, `cb_db_version` |

---

## 📝 Changelog

### v1.1.0
- Initial public release