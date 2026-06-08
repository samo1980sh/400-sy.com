# 400-sy.com — Syria Arabic name update

## Purpose
Update Arabic country display text from:

- سوريا

To:

- سورية

## Scope
The package includes a maintenance script that scans project source text files under:

- app
- config
- database
- lang
- resources
- routes

It replaces the exact Arabic text `سوريا` with `سورية`.

## Notes
- The script creates a timestamped backup under `_backup/syria-name-update-01/`.
- Temporary `tools/` and `_backup/` directories should not be committed.
- If the old value exists inside live database records, update those records from the admin panel or database separately.
