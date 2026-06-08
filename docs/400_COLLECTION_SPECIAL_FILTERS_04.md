# 400 Collection + Special Offer Filters - Patch 04

This patch completes a partially applied collection/special-offer filter update.

It adds/ensures:
- `buildCollectionOptions()`
- `buildSpecialOfferOptions()`
- collection and special-offer logic inside `applyProductsFilters()`
- collection and special-offer filter widgets inside `shop-filter.blade.php`
- active filter chips support when applicable

No database changes are included in this package. The migration from the previous package may already be applied.
