# MENUJU — Google Stitch Master Design Prompt
**Use with:** `01_PRODUCT_PRD.md`, `02_INFORMATION_ARCHITECTURE.md`, `03_USER_FLOWS_AND_SCREENS.md`, `04_DESIGN_SYSTEM.md`, `05_SCREEN_ACCEPTANCE.md`.

> If Stitch cannot ingest all documents in the same session, paste the screen inventory and navigation first, then this prompt; continue work in batches in the same project. The product documents are proposed specifications, not current implementation claims.

## PROMPT (paste into Google Stitch)

Act as a Principal Product Designer, Creative Director, UX Architect, and Design Systems Lead. Create a complete multi-screen product design for **MENUJU**, a premium Indonesian wedding planning app for couples.

Use the attached PRD, Information Architecture, Screen Inventory/User Flows, Design System, and Acceptance Checklist as your source of truth. **Do not reuse old MENUJU screens or generic admin dashboard patterns.** All visible interface copy must be natural, concise Indonesian.

### Primary direction
**Warm Editorial Utility**: calm, refined, intimate, functional. Beautiful typography and meaningful whitespace. Each screen has exactly one dominant purpose. Avoid excessive cards, stacked forms, nested forms, long explanations, and unrelated features unified into mega-pages.

### Mandatory IA
Use distinct destinations: Beranda; Perjalanan; Checklist; Acara & Jadwal; Kebutuhan; Tamu; Undangan; Ringkasan Keuangan; Tabungan; Anggaran; Transaksi; Akun; Pengaturan. Present categories as navigation grouping only—not combined mega-pages. Connected data can be cross-linked; separate user tasks remain separate screens. Public invitation is a separate immersive mobile-first experience.

### Mandatory screen construction
Generate **EVERY SINGLE APPLICABLE SCREEN** from `03_USER_FLOWS_AND_SCREENS.md`. Include each screen ID in the frame name. This means all index, detail, create/edit modal or drawer, confirmation, relevant empty/loading/error states, onboarding, financial management, guest management, invitation library/editor/preview/publish, and public invitation/RSVP.

An index must show its list, table, timeline, or gallery, **not** a large inline create/edit form. Create/edit goes to a well-designed modal, drawer, or separate page based on complexity. Detail views must be distinct for complex entities. Avoid mockups showing all flows in one page.

### Visual craft
Editorial but readable typography; warm neutral palette; botanical accent if it serves the composition; restrained surfaces; clear form input borders; Font Awesome-inspired iconography; precise alignment; deliberate visual rhythm; familiar actions labeled in Indonesian. For secondary actions use icon buttons with clear meaning; primary CTAs have concise text. Currency is shown as Indonesian Rupiah (`Rp 1.500.000`). Mobile reflows content rather than shrinking desktop.

### Special invitation creative direction
Create an additional signature public wedding invitation: **Cinematic Garden Editorial**. Full-bleed photography, names and ceremony details, optional love story, event/location information, RSVP, closing. The long-scroll experience should feel continuous, with *motion annotations* for selective parallax, photo mask reveal, subtle floral movement, and graceful section transitions. Scrolling stays natural and functional. Do not apply wedding-decoration visual clutter to the app dashboard.

### Deliverables
1. Main navigation and design foundations.
2. All screen frames from the inventory with consistent IDs.
3. Desktop views for each main screen and complex form/editor.
4. Mobile variants for onboarding, Home, Tabungan, Kebutuhan, Tamu, Undangan, public invitation, RSVP, and other critical flows.
5. Modal/drawer overlays shown in context—not isolated generic boxes.
6. Populated/empty/error examples where appropriate.
7. Prototypable transitions and clear flow connections.
8. A coverage report listing generated IDs, missing IDs, and next batch IDs.

Do not claim completion if any inventory IDs are missing. If output limits prevent generating all screens at once, generate coherent batches in this sequence: (1) foundations/auth/onboarding/home, (2) preparation, (3) finance, (4) guests/invitations/editor, (5) public invitation/settings/states. **Continue using the same design system, naming, and screen inventory.**

Final objective: A genuinely usable, beautiful, well-structured wedding planning app; **not** a giant dashboard, not a collection of forms, and not a visual mockup without user flows.
