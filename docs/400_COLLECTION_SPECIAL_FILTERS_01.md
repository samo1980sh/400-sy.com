# 400-sy.com — Collection and Special Offer Front Filters

## Scope

Adds storefront filters for:

1. Collection / التشكيلة
2. Special offers / العروض الخاصة

## Business rule

The customer asked not to add a new Excel column for special offers.

Therefore, the existing `top` product import value remains the source for special offers:

- `top = Offer` means the product is treated as a special offer.
- Internally, this continues to map to `is_special_offer = true`.

## What changes

- The product listing controller reads `collections[]` and `special_offers[]` request filters.
- The product listing query filters by `collection` and `is_special_offer`.
- The shop sidebar shows available collections within the current category/filter scope.
- The shop sidebar shows a special-offers checkbox when matching products exist.
- Active filter chips include collection and special offer labels.
- Database indexes are added for `products.collection` and `products.is_special_offer` to keep filters responsive.

## No changes

- No product Excel column is added.
- No existing import behavior is removed.
- `top = Offer` remains the approved data-entry rule.
