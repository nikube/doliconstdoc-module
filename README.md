# DoliConstDoc Module

Dolibarr module that exposes the [DoliConstDoc](https://github.com/nikube/doliconstdoc) constant documentation database directly inside the admin UI.

## What it does

- Browse 3000+ documented Dolibarr global constants with search and filters
- Distinguish active / inactive / default / customized states
- **Activate hidden settings** straight from the module (no more `INSERT INTO llx_const`)
- Redirect standard constants to their native admin page
- Banner on `admin/const.php` to point users at the catalog
- Update the embedded doc from the latest GitHub release

## Install

1. Drop into `htdocs/custom/doliconstdoc/` (or install via DMM).
2. Activate in **Home → Setup → Modules**.
3. Open **Home → Admin tools → DoliConstDoc**.

## Out of scope (v0)

- Editing arbitrary non-hidden constants (the module sends you to the native UI)
- Multi-language doc (FR/EN UI labels only — descriptions stay EN)
- Diff / export of configuration

## License

GPL v3.
