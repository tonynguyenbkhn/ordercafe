Hero Banner section

Fields (ACF):
- `image` (Image) — return format: ID
- `title` (Text)
- `description` (Textarea)
- `button_text` (Text)
- `button_link` (URL)

Usage:
- The template is `templates/sections/hero-banner/section.php`.
- Include this file from a flexible content loop or directly. The template will prefer `get_sub_field()` when inside a flexible content layout, falling back to `get_field()`.
- Add or import `style.css` into your theme build or enqueue it from `functions.php`.

Notes:
- The image field returns the attachment ID; the template outputs an `<img>` (no JS required).
- Description is a plain textarea; line breaks are preserved.
