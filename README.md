<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Dumaraog Bag Collection

Online storefront and administration portal for product listings, variants and inventory, customer carts and orders, order fulfillment, user accounts, sales summaries, and administrative activity logs. MySQL is the default application database; feature tests use an isolated in-memory SQLite database.

### Local setup

1. Create a MySQL database named `bagshopcollection_onlineordering_and_adminportal` (or set `DB_DATABASE` to an existing database).
2. Copy `.env.example` to `.env`, set the MySQL username and password, then run:

```sh
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build
```

Register a user through the site, then promote that account to the first administrator:

```sh
php artisan users:promote-admin your-email@example.com
```

Run the feature suite with `php artisan test --compact`.

### API

The versioned API is under `/api/v1`. `POST /auth/register` or `POST /auth/token` returns an expiring Laravel Sanctum bearer token; send it as `Authorization: Bearer <token>` for cart, order, profile, and admin endpoints. Admin endpoints require a Sanctum token belonging to an active administrator. Product and category listings are public. Password reset is available through `/auth/password/forgot` and `/auth/password/reset`; configure a working mailer before using it outside local development. Admin endpoints cover catalog/category changes, order status updates, user status/role management, and reports.

#### Product catalog and inventory CRUD

The internal product API uses the existing `Product` / `ProductVariant` models and `products` / `product_variants` tables. Its single admin resource URL, `/api/v1/admin/products`, supports create, list, read, update, and permanent delete operations. All admin operations require a Laravel Sanctum bearer token issued to an active administrator. The public catalog remains available at `/api/v1/products`.

| Operation | Method | Endpoint | Success response |
| --- | --- | --- | --- |
| Create product and variants | `POST` | `/api/v1/admin/products` | `201 Created`, product JSON |
| Read products, including inactive but excluding archived | `GET` | `/api/v1/admin/products` | `200 OK`, paginated product JSON |
| Read one product by ID | `GET` | `/api/v1/admin/products/{id}` | `200 OK`, product JSON |
| Update product and variants | `PUT` or `PATCH` | `/api/v1/admin/products/{id}` | `200 OK`, updated product JSON |
| Permanently delete product | `DELETE` | `/api/v1/admin/products/{id}` | `200 OK`, deletion confirmation |
| Browse active public products | `GET` | `/api/v1/products` | `200 OK`, paginated product JSON |
| Read one active public product by slug | `GET` | `/api/v1/products/{slug}` | `200 OK`, product JSON |

Create and PUT requests accept `name`, optional `category_id`, `description`, `image`, `is_active`, and a `variants` array. Each variant includes `name`, optional unique `sku`, `price`, `stock`, and `is_active`. To update an existing variant with either PUT or PATCH, include its existing `id` from the product response; this updates the same database row and keeps its ID. A variant without an `id` is treated as new and receives a new ID. PUT replaces the product's variant list, so existing variants omitted from a PUT request are removed. PATCH accepts only the fields being changed and leaves omitted fields and variants unchanged. DELETE permanently removes the product and its variants (and cart entries that reference them); historical order-item snapshots are retained, with their product-variant references set to null. The admin web interface continues to offer its separate archive behavior.

Validation failures return `422 Unprocessable Content` with JSON field errors. Missing products return `404 Not Found`; protected requests without a valid Sanctum bearer token return `401 Unauthorized`, and customer or inactive-user attempts to access admin resources return `403 Forbidden`. API administrators can list and view all orders and carts, update or remove cart items across users, and use the existing product, category, order, user, and report administration routes. Regular users remain limited to their own orders and cart items.

Admin tokens are issued by `POST /api/v1/auth/token` after validating the administrator's email and password. Tokens are stored hashed in Sanctum's `personal_access_tokens` table and expire after 30 days by default; clients may request a different `expires_in_days` value from 1 to 365. Deactivating a user or resetting their password revokes their tokens.

#### Testing with Postman or Thunder Client

To browse the public catalog without authentication, create a `GET` request to `http://localhost:8000/api/v1/products` and set Postman's Authorization type to **No Auth**. This public endpoint only returns active products. The full admin CRUD resource is at `http://localhost:8000/api/v1/admin/products` and requires a Sanctum bearer token for an active admin.

For the Lab 4 workflow, import `postman/collections/product-internal-api.postman_collection.json` and `postman/environments/local-internal-api.postman_environment.json` into Postman. Select the **Bag Collection Local Sanctum API** environment and fill in `adminEmail` and `adminPassword` for an active admin account. Run the collection requests in order: get Sanctum token, create, read all, read one, patch update, public read, validation error, not-found error, permanently delete, then verify the deleted item returns `404` from the internal and public product reads. The token request automatically saves `sanctumToken`, and the create request saves `productId` and `productSlug`. Admin requests use Bearer token authorization; public product GET requests use **No Auth**.

To get a token manually in Postman:

1. Send `POST http://localhost:8000/api/v1/auth/token` with `Accept: application/json` and `Content-Type: application/json`.
2. Use this JSON body with an active administrator's credentials:

   ```json
{
  "email": "admin@example.com",
  "password": "your-admin-password",
  "name": "Postman Product API"
}
   ```

3. Copy the response `token`. For each admin request, set Postman's **Authorization** type to **Bearer Token** and paste it there. Do not use the old `X-API-KEY` header.

For example, create a product with `POST http://localhost:8000/api/v1/admin/products`. Set the request body to raw JSON:

```json
{
  "name": "Postman Sample Bag",
  "description": "Created through Postman or Thunder Client.",
  "is_active": true,
  "variants": [
    {
      "name": "Default",
      "sku": "POSTMAN-API-001",
      "price": 29.99,
      "stock": 10,
      "is_active": true
    }
  ]
}
```

For product create, update, and delete requests, add `Accept: application/json`, `Content-Type: application/json`, and `Authorization: Bearer <paste the returned token here>`. Replace the text inside angle brackets with the `token` value only, keeping the `Bearer` prefix. Admin product collection and detail GET requests also require the bearer token. Public product collection and detail GET requests do not require the Authorization header. The token is returned only when issued, so request a new one if it is lost.

Run `php artisan test --compact tests/Feature/productcatalogapi.php` to exercise product creation, collection/detail reads, updates, variant inventory changes, permanent deletion, validation, missing records, and write authorization. The PHPUnit Feature suite scans PHP files, including `productcatalogapi.php`.

### Store workflow

Administrators create categories and products with one or more independently priced and stocked variants. Customer checkout supports pickup or delivery and records cash/manual payment without charging a payment gateway. Inventory is reserved inside the order transaction, order items preserve product and price snapshots, and cancelling an eligible order restores the reserved stock.

### Built with Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
