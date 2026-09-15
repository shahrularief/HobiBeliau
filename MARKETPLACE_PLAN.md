# Arcana Vault Marketplace Plan

This document is the reference for evolving HobiBeliau into a Malaysian trading card marketplace. The experience is inspired by KadHunt, with HobiBeliau's own branding and design.

Current branding: **Arcana Vault**. This project is a portfolio demo; real payments and production configuration are outside the user's requested scope.

Status: Initial accounts, seller approval, listings, marketplace, local orders, listing moderation, and selling suspension implemented. Real payments remain pending.

## Product direction

Registered users can buy cards and apply to become sellers. Approved sellers can publish and manage their own card listings. Admins oversee seller applications, listings, users, and orders.

A seller remains a buyer and uses the same account for both activities.

## Roles and permissions

| Role | Capabilities |
| --- | --- |
| Visitor | Browse the marketplace, search cards, and view public listings and seller profiles. |
| Buyer | All visitor capabilities, plus place orders and view their own purchases. |
| Approved seller | All buyer capabilities, plus manage their own listings and fulfil their own sales. |
| Admin | Review seller applications, moderate listings, inspect orders, handle reports, and suspend accounts. |

Permissions must be enforced on the server. Hiding a button is insufficient. Sellers must never be able to edit another seller's listings or access unrelated orders. Buyers must only access their own private purchases.

## Proposed first-release scope

- Admin approval before a user can sell.
- Fixed-price card listings with prices in Malaysian ringgit (RM).
- One seller per checkout to simplify shipping and order handling.
- Public marketplace, listing details, and seller profiles.
- Separate buyer and seller dashboard functions within the same account.
- Admin management through the existing Filament panel.

Multi-seller checkout, auctions, trading, gacha pack opening, and advanced collection features are outside the initial milestone.

## Build phases

### 1. Accounts and permissions

- Reuse the existing registration and login system.
- Add seller applications with pending, approved, and rejected states.
- Provide an admin review workflow.
- Establish explicit admin permissions and seller access rules.
- Define how seller or account suspension affects existing listings and orders.

### 2. Seller-owned listings

- Add card name, game, set, condition, description, photos, price, quantity, and shipping information.
- Associate every listing with its seller.
- Let sellers create, edit, publish, pause, and mark listings sold.
- Validate uploads, prices, quantities, and listing ownership.
- Assess existing stock records before deciding how to migrate or reuse them; preserve existing data.

### 3. Marketplace and dashboards

- Build public browsing, search, and filters.
- Add listing details and public seller profiles.
- Add buyer purchase history and seller listing management.
- Design responsive pages with HobiBeliau branding.
- Clearly distinguish real listings from any development sample data.

### 4. Orders

- Allow a buyer to order available cards from one seller.
- Record purchased items and their prices as order snapshots so later listing edits do not alter past orders.
- Prevent overselling with atomic inventory checks and updates.
- Give sellers fulfilment and tracking controls.
- Give buyers order status and purchase history.
- Define cancellation, stock restoration, and permitted status transitions.
- Keep payment state separate from fulfilment state.

### 5. Admin oversight

- Review seller applications and moderate listings.
- Handle listing and user reports.
- Suspend accounts and selling privileges.
- Inspect orders and maintain an audit trail for sensitive admin actions.

### 6. Payments and trust

- Select the payment model and provider before implementing real checkout payments.
- Integrate payments and, where applicable, seller payouts.
- Verify payment notifications on the server and process them idempotently.
- Define platform fees, refunds, disputes, and payout timing.
- Add reviews linked to completed purchases.

## First implementation milestone

Complete this flow locally:

1. A user registers and applies to become a seller.
2. An admin approves the application.
3. The seller publishes a card listing.
4. Another registered user finds the listing and places an order.
5. The seller and buyer see that order in their respective dashboards.
6. An admin can inspect the application, listing, and order.

Real payment collection is not part of this milestone. Any development checkout must clearly explain that it does not charge money, and must not mark an order paid without a verified payment integration.

## Existing foundation

- Laravel backend with Livewire.
- Jetstream/Fortify account features.
- Filament admin panel.
- Existing stock management and a Stock model.
- Local Laravel and Vite development servers.

Inspect the existing implementation before making schema or authorization changes. The README contains roadmap features that should not be assumed to exist.

## Proposed data structures

These are starting points for implementation, not a finalized schema:

| Entity | Purpose |
| --- | --- |
| User | Authentication, buyer identity, and explicit admin access. |
| Seller application | Application details, review status, reviewer, and review timestamps. |
| Seller profile | Approved seller's public shop information and selling status. |
| Listing | Seller-owned card details, price, quantity, and publication status. |
| Listing image | Photos belonging to a listing. |
| Order | Buyer, seller, totals, shipping details, and fulfilment status. |
| Order item | Purchased card, quantity, and immutable purchase-time details. |
| Payment | Provider reference and verified payment status when payments are introduced. |
| Report / audit entry | Moderation reports and records of sensitive actions. |

## Decisions still needed

1. **Payment model:** buyers pay sellers directly, or the platform collects payments and arranges payouts.
2. **Seller application information:** what details admins need to approve a seller.
3. **Card categories:** which games and condition labels to support first.
4. **Shipping:** seller-defined rates, supported destinations, and whether pickup is allowed.
5. **Moderation:** whether approved sellers can publish immediately or each listing requires review.
6. **Commercial rules:** platform fees, cancellation policy, refunds, and disputes before accepting real payments.

The payment decision blocks real payment integration, but does not block building accounts, seller applications, listings, and the local order flow.

## Verification criteria

- Registration and existing authentication continue to work.
- Unapproved users cannot publish listings.
- Sellers can only change their own listings and fulfil their own orders.
- Buyers can only access their own private order details.
- Suspended users cannot bypass restrictions by calling endpoints directly.
- Unavailable inventory cannot be ordered, including concurrent requests.
- Past order prices remain unchanged after listing edits.
- Admin approval and moderation work through the UI.
- The milestone works through the UI on desktop and mobile layouts.

## Progress tracking

- [x] Create this reference plan.
- [x] Inspect existing authentication, stock management, database, and admin access.
- [x] Implement initial admin access, seller applications, and approval permissions.
- [x] Implement seller-owned listings.
- [x] Implement initial marketplace pages and seller listing dashboard.
- [x] Implement the local order flow.
- [ ] Verify the first milestone and permission boundaries.
- [ ] Resolve payment and commercial decisions.
- [ ] Integrate real payments and trust features.

Update this document as decisions are confirmed and phases are completed.

## Initial implementation inspection — 16 September 2026

### Authentication and user creation

- Public registration is enabled through Fortify at `/register`. It collects name, email, password, and password confirmation, validates the input, and hashes the password through `app/Actions/Fortify/CreateNewUser.php`.
- Login redirects users to `/dashboard`. The dashboard currently renders the default Jetstream welcome component.
- Profile updates, password reset, password updates, and two-factor authentication are configured.
- Email verification is disabled in Fortify, and `User` does not implement `MustVerifyEmail`. The dashboard's `verified` middleware therefore does not require these users to verify their email.
- `User` has no admin flag, seller status, suspension status, or seller relationships.

### Admin access

- The Filament panel lives at `/admin`, uses the existing users, and enables a second registration UI at `/admin/register`.
- `User` does not implement Filament's `FilamentUser` access contract. The installed Filament authentication middleware permits authenticated users without that contract in the local environment and rejects them outside local. There is no application-defined admin role check.
- Before marketplace development, implement explicit panel access for admins and remove public registration from the admin panel. Public `/register` remains the buyer registration entry point.
- Do not automatically promote every existing account. Provision a specific administrator deliberately; the account to promote remains to be identified.

### Stock management

- `stocks` contains title, description, one image path, quantity, and timestamps. It has no seller ownership, price, game, set, condition, publication state, or shipping fields.
- Custom Filament pages provide adding, editing, listing, and deleting stocks. They query shared stock records and contain no per-user authorization checks.
- `DELETE /delete-stock/{id}` is outside the authenticated route group and has no authorization check. The web middleware provides CSRF protection, but CSRF does not establish permission to delete a stock.
- `EditStock::updateStock()` attempts to delete the new image when an image changes, rather than the previous image. Storage disk/path handling should also be made explicit during repair.
- Preserve existing stocks as legacy inventory initially. Build seller-owned listings separately so records do not acquire guessed owners or prices.

### Orders and marketplace

- The inspected application models are `User` and `Stock`. No seller application, listing, order, order item, or payment models/migrations were found.
- Marketplace search, seller dashboards, and purchasing need implementation. README roadmap entries are not evidence of completed functionality.

### Verification and next implementation sequence

The registration routes were verified with Laravel's route inventory. Findings above come from application source, stock migrations, and installed Filament middleware; no accounts or stock records were changed during inspection.

1. Add explicit admin access and restrict all existing stock mutations, including the standalone delete endpoint. Disable admin self-registration.
2. Configure an isolated test database before running database-resetting tests. The current `phpunit.xml` leaves its test database overrides commented out, so database tests must not be run against the existing local database.
3. Add seller application records and a buyer-facing application/status page. Start with shop name and a short application description; confirm additional information requirements before collecting it.
4. Add admin approval/rejection UI and enforce review transitions on the server. A pending or rejected application must not grant selling access.
5. Verify buyer, applicant, approved seller, and admin boundaries with focused tests, then proceed to seller-owned listings.

The issues above were identified during inspection; see the implementation update below for resolved items.

## Implementation update — 16 September 2026

- Added `users.is_admin`, defaulting to false. Public registration cannot assign this flag.
- Granted admin access to the existing `shahrul.arief.sa@gmail.com` account. Its password was preserved.
- Implemented the Filament panel access contract and disabled admin self-registration.
- Restricted the standalone stock delete endpoint and the stock page mutation methods to admins.
- Made stock uploads/deletions use the public disk and corrected replacement-image deletion to target the old image.
- Added one seller application per user with pending, approved, or rejected status, reviewer identity, and review timestamp.
- Added `/seller/apply` with application submission and status display, linked from the dashboard.
- Added `/admin/seller-applications` with Filament approval/rejection actions and confirmation dialogs.
- Review requires an admin and only transitions a pending application once. Approved sellers remain buyers; listing creation is not yet available.
- Added the CLI command `php artisan app:make-admin EMAIL` for deliberate promotion of an existing account.
- Isolated database tests using SQLite in memory. On the current PHP runtime, run the checks with `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter='MarketplaceAccessTest|RegistrationTest|AuthenticationTest'`.
- Verification: 10 tests, 30 assertions, 0 failures, 1 expected skip for registration-disabled behavior when registration is enabled. Tests covered guest/buyer restrictions, duplicate applications, ignored privilege input, review authorization, repeated-review protection, admin review-page rendering, and existing login/registration behavior.

Remaining scope: suspension and appeals/resubmission, seller-owned listings, marketplace browsing, orders, and real payments. Seller approval currently grants a status checked by `User::isApprovedSeller()`; future listing endpoints must enforce it. No manual browser interaction test of approval/rejection was performed in this update; the review-page render and server review behavior were tested.

## Listing and marketplace update — 16 September 2026

- Added a separate `listings` table; existing legacy stocks were preserved.
- Approved sellers can create and edit only their own cards at `/seller/listings`, with title, game, set, condition, description, RM price, quantity, shipping price/details, and up to five photos.
- Listings support draft, published, paused, and sold states. Public visibility requires publication, positive quantity, and an approved seller.
- Photos accept JPEG, PNG, or WebP up to 4 MB each. Replacement uploads replace the complete photo collection; leaving uploads empty during editing preserves existing photos.
- Created the public storefront at `/`, search by card/set, game and condition filters, listing detail pages at `/cards/{id}`, and public shops at `/shops/{user_id}`.
- Linked approved sellers to listing management from their dashboard and application status page.
- Initial game choices: Pokémon, One Piece, Magic: The Gathering, Yu-Gi-Oh!, and Other. Initial conditions range from near mint through damaged. These can be revised as catalog requirements are confirmed.
- Enabled the public storage link for card images.
- No real or sample listings were inserted, and no accounts were automatically approved as sellers.
- Verification: 6 listing tests with 40 assertions passed using an isolated in-memory database. Coverage includes seller approval, ownership tampering, unauthorized edits, photo replacement, unpublished/unavailable visibility, filters, shop/detail rendering, and invalid input.
- The running storefront returned HTTP 200 with the new marketplace content. No manual browser visual check was performed in this phase.

Next: the local one-seller order flow, inventory protection, buyer purchase history, seller fulfilment controls, and admin order inspection. Checkout remains unavailable until that phase. Listing moderation controls, account suspension, and appeals are still pending.

## Local order update — 16 September 2026

- Added development checkout for one card listing/one seller per order, supporting multiple copies of that card.
- Checkout requires login, recipient name, contact phone, and shipping address. A seller cannot purchase their own listing.
- Only available cards from approved sellers can be ordered. Totals are calculated on the server in integer cents; purchase-time item title, game, condition, quantity, and price are recorded as snapshots.
- Stock is reserved transactionally with a conditional decrement. Row locks protect reservation and order transitions on databases supporting them. A unique checkout token prevents duplicate orders from repeat submissions.
- Buyer purchase history is at `/orders`; seller sales are at `/seller/orders`. Private order pages are limited to the buyer and seller.
- Workflow: placed → processing → shipped → completed. Sellers control processing/shipping; tracking details are required to ship; the buyer confirms receipt.
- Buyers and approved sellers can cancel before shipping. Cancellation restores quantity once, without automatically republishing paused or sold listings.
- Admins can inspect orders and their private details in Filament at `/admin/orders` through a read-only details modal.
- All orders retain `payment_status = not_collected`. Checkout and fulfilment pages explicitly describe development-only orders; no real payments or shipments should occur.
- Order and item foreign keys preserve order history by preventing deletion of referenced users/listings. Account deletion/anonymization policy needs work before production.
- Verification: the listing/order suite exercised 11 tests and 86 assertions. Ten tests passed initially; the remaining modal assertion used a helper that closed the modal. Changed the assertion to mount the modal, then reran that test: passed with 5 assertions. No application changes were required for that test correction.
- Frontend production build passed; migrations applied locally; guest order access redirects to login.
- Concurrent load testing against the deployment database and manual end-to-end browser testing remain outstanding. The local milestone's backend and rendered page checks pass; the UI verification checklist is still open.

Next steps: manually exercise the complete local flow with separate buyer/seller accounts, add listing moderation and suspension, and resolve payment model, shipping policies, refunds, and account retention before real-money transactions.

## Moderation update — 16 September 2026

- Filament's Card listings section at `/admin/listings` supports inspection and hide/restore controls.
- `admin_hidden` is independent of seller-controlled publication status and cannot be assigned through the seller listing form. Editing or republishing a hidden listing does not make it publicly visible.
- Seller applications now have Suspend selling and Restore selling controls. Suspension uses a separate `selling_suspended` flag, preserving the approval decision.
- Suspended sellers' cards and shops disappear publicly. New listing management, catalog lookup, and purchases of their cards are blocked on the server.
- Selling suspension does not disable login or buying. Existing sales remain accessible and can be fulfilled or cancelled; dashboards communicate the restriction.
- Every hide/restore or suspension/restoration requires a reason and records administrator, target, action, and timestamp in `moderation_logs`. Admin flags and restriction flags are excluded from public mass assignment.
- Restoration does not change seller-controlled draft/paused/sold status or undo a separate listing restriction.
- Verification: moderation, listing, and order tests passed (14 tests, 117 assertions), including an actual Filament hide action, unauthorized moderation, seller attempts to override hiding, blocked checkout, and existing-order resolution during suspension. Frontend build passed and migration applied.
- No real account was suspended or listing hidden during verification; tests used an isolated in-memory database.

Remaining: buyer reports, moderation appeals, a dedicated audit-log UI, production concurrency checks, real payments, and shipping/refund/account-retention policies.

## Buyer experience update — 16 September 2026

- Added minimum/maximum RM price filters and newest/ascending-price/descending-price sorting. Filters persist through pagination; inverted price ranges return a validation message.
- Marketplace cards now show shop name and available quantity alongside their photos and price. Seller shop queries eager-load their related details.
- Public navigation includes purchases, sales for approved sellers (including suspended sellers resolving orders), and a native POST sign-out button. Navigation wraps on narrow screens.
- Checkout previews subtotal, shipping, and total as quantity changes, using integer cents. The server remains authoritative for the amount; no payment is collected.
- Verification: 8 targeted listing/order tests passed with 65 assertions; frontend build passed. Browser checks confirmed navigation and filters, actual card photo display, and no horizontal overflow at a 390-pixel mobile viewport. Browser viewport restored after testing.
- New listings now publish automatically on creation; status controls are available only on the edit form. Existing drafts are not automatically published.

Next: buyer listing reports and an admin moderation history interface, followed by payment-model decisions.

## Steps 1–4 completed — 16 September 2026

1. **Buyer reports:** logged-in users can report another seller's visible listing with a reason. Reports are throttled and limited to one per user/listing; duplicate submissions do not overwrite the original. Admins review and resolve reports with a note at `/admin/listing-reports`. Resolution does not automatically hide a listing; existing moderation controls provide that decision separately.
2. **Moderation history:** read-only Filament history at `/admin/moderation-logs` shows administrator, target type/ID, action, reason, and timestamp. Report resolutions join listing/selling restrictions in the audit trail. Report and history pages are restricted to admins.
3. **Seller profiles:** approved sellers edit their own shop name, public introduction, city/state, and shipping policy at `/seller/profile`. Public shop pages show those details and join date. Private application descriptions are no longer used as public introductions. Selling suspension blocks profile updates; existing records are preserved.
4. **Card catalog:** TCGdex selections save verified card ID, card number, set ID, and rarity on listings. The server verifies the selected printing matches the name/game/set; metadata supplied by a client cannot override provider values. Use manual entry to clear catalog selection when changing those identity fields or when lookup is unavailable. Existing listings are not assigned guessed card IDs. Other games retain manual entry; YGOPRODeck is a potential later provider, requiring local data/image caching under its documented usage rules.

Verification: 12 targeted tests passed with 93 assertions, covering report authentication/duplicates, admin report resolution and audit-page access, profile ownership/privacy, catalog verification, and existing listing/search behaviour. Frontend production build passed and migration applied. Tests used an isolated in-memory database; no real reports or shop/profile edits were created during verification.

Next is step 5: full UI testing across buyer/seller/admin and desktop/mobile, then payment-model decisions (steps 6 onward).

## Report decisions update — 16 September 2026

Resolve now offers Dismiss report, Hide listing, or Suspend seller's selling access, with a required decision reason. The chosen decision is stored in `resolution_action` and displayed in the report table. Restrictions, report resolution, and audit records are committed atomically. Already-resolved reports cannot apply a second restriction. Dismissal does not undo existing moderation. Existing resolved reports retain their original notes without guessed decisions; use separate moderation controls when additional action is needed.

## Arcana rebrand and landing page

- Used the supplied `C:/Projects/shahrularief.github.io/works/arcana/index.html`, CSS, and JavaScript as the landing page at `/`. Copied assets into this app without changing the portfolio source files.
- Preserved the original concept collection and interactions; added Enter marketplace links to `/marketplace`, retaining the concept collection link separately.
- Updated marketplace, account/login logos and titles, admin branding, and demo placeholder labels to Arcana Vault.
- Matched public marketplace colors to the landing page's dark green, cream, and mint palette. Removed old forced Filament background overrides so admin light/dark contrast uses native styles.
- Existing app listings, accounts, and orders were preserved. Updated tests for the marketplace's new route.
- Verification: 10 targeted tests passed with 81 assertions; production asset build passed (asset compilation only, no deployment). Browser verification confirmed original landing content and working marketplace button.
- Further portfolio polish and simulated payment UI may follow; real payment integrations and production setup remain out of scope.

## TCGdex integration — 16 September 2026

- Added optional English Pokémon card lookup to create/edit listing forms, using TCGdex REST search and card detail endpoints.
- Sellers search by name, page through 12 results at a time, and select a printing by its catalog ID/card number. Selection fills card name, game, and set; these fields remain editable.
- The integration is a form helper: catalog IDs and card numbers are not yet stored on listings. Sellers must check the printing and describe variants in their listing as needed.
- Prices, quantities, condition, shipping, and actual-card photo uploads remain seller supplied. No catalog images replace seller photos.
- Laravel proxies API requests for approved sellers only, throttles requests, caches search results for one hour and card details for one day, and applies connection/request timeouts.
- If lookup fails, manual entry remains available. No API key or new PHP dependency is required.
- Verification: catalog and listing tests passed (8 tests, 52 assertions), production frontend build passed, and live browser search returned Pikachu printings.
- Provider documentation: https://tcgdex.dev/rest/cards and https://tcgdex.dev/rest/filtering-sorting-pagination.

## Portfolio polish and payment simulation

- Account, seller forms, login, and order pages share Arcana's cream/green/mint palette, with demo labels and light/dark contrast support.
- Buyers can simulate success or failure on their own placed orders. No payment credentials, provider, real money, or production configuration are involved.
- Failure keeps stock reserved so the buyer can retry or cancel. Success cannot be changed into failure; cancelled or progressed orders cannot simulate a new payment.
- Stored states are explicitly `simulated_success` and `simulated_failure`, never real paid status. Stock and fulfilment states are independent; all fulfilment remains a demonstration.
- Existing orders and user data were preserved; simulations were exercised only in an isolated test database.

### Interactive collector dashboard
- Replaced the plain account link list with an Arcana collector hub: illustrated card stack, live account metrics, recent order activity, role-aware next actions and admin report count.
- Buying/selling tabs and game filter chips run locally through Alpine; marketplace previews only include publicly visible listings. Purchases and sales are scoped to the signed-in user.
- Responsive card grid, hover effects, keyboard focus and reduced-motion support. No real payments or production configuration added.
