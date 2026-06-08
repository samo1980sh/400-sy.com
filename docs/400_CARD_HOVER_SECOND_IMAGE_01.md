# 400 - Product Card Desktop Hover Second Image

## Purpose

This package adds frontend-only behavior for product cards on desktop.

When a customer hovers the main product image in a product card, the image changes to the second image candidate of the current product/color.

Example:

- Current image: `961S6R150-A1.jpg`
- Hover image candidate: `961S6R150-A1-1.jpg`

If the current image filename already ends with a number such as `-2`, the hover candidate uses the same base with `-1`.

## Scope

Frontend only.

No database changes.
No admin panel changes.
No image file changes.

## Files touched

- `resources/views/frontend/partials/product-scripts.blade.php`
- `public/css/styles.css`

## Notes

The behavior is limited to devices that support real hover and fine pointer input, so mobile/touch devices are not affected.

The script preloads the hover image candidate and only switches if the candidate exists. If no second image exists, the product card keeps the normal image.
