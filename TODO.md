# TODO

## Seeder usage
- [x] Inspect existing seeders (DatabaseSeeder, RolesAndPermissionsSeeder, GranularAdminRolesAndPermissionsSeeder, SampleUsersSeeder, CreateCatalogAdminSeeder).
- [ ] Update `database/seeders/DatabaseSeeder.php` to call `SampleUsersSeeder::class` so `php artisan db:seed` fully populates RBAC + sample users.
- [ ] Clear caches and run seeders to validate created roles/permissions and sample accounts.

