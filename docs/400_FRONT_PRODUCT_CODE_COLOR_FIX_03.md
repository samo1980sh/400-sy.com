# 400 Front Product Code Color Fix 03

Fixes the product detail page behavior after merging the color code into the displayed product code.

## Problem
On the product detail page, changing colors appended new color codes to the already displayed product code, producing values such as:

`W5R237-A3-A1-A2-A1`

## Expected behavior
The product detail page must always rebuild the displayed product code from the original product code base and the currently selected color code:

`W5R237-A1`

The standalone color-code row remains hidden on the frontend, while the hidden data target remains available for existing scripts.
