# MENUJU development guide

- This repository is a new Laravel + Vue 3 (JavaScript only) + Tailwind CSS application. Follow `docs/` as product truth and `references/` as design handoff; do not infer that documented features already exist.
- Preserve the product boundaries in `docs/02_INFORMATION_ARCHITECTURE.md`: index, detail, create/edit, and public invitation are distinct screens when specified.
- Use workspace-scoped authorization for every private query and mutation. Keep money numeric in storage and format only at the UI boundary (`id-ID`, Rupiah).
- Reuse the design tokens and accessible component patterns in `docs/04_DESIGN_SYSTEM.md`. Keep operational UI Indonesian and respect reduced motion.
- Track every Screen ID in `docs/DEVELOPMENT_PROGRESS.md`. Only advance status when evidence exists; build success alone is not functional or visual verification.
- Keep Vue source in `resources/js`, Laravel HTTP/domain code in `app`, and schema changes in `database/migrations`. JavaScript only; do not introduce TypeScript.
- Do not add permanent mock data to production screens. Fixtures for tests and local development must be clearly separated from real application data.
