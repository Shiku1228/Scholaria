# Scholaria ngrok Setup

This guide explains the correct way to expose Scholaria through ngrok without storing an ngrok authtoken in the project.

## Important rules

- The ngrok authtoken authenticates your local `ngrok` CLI.
- The authtoken must not be stored in the project files or in `.env`.
- The authtoken does not automatically give you a custom public URL.
- To use a paid or custom ngrok URL, the ngrok account owner must reserve the domain in the ngrok dashboard first.

## 1. Add the authtoken locally

Run this on your machine only:

```powershell
ngrok config add-authtoken YOUR_NGROK_AUTHTOKEN
```

This saves the token in the local ngrok config, not in the Scholaria project.

## 2. Reserve a domain in the ngrok dashboard

The owner of the paid ngrok account must create or reserve the public domain in ngrok first.

Example reserved domain:

```text
scholaria.ngrok.app
```

If no reserved domain is created, ngrok will still generate a default `ngrok-free.app` or `ngrok-free.dev` URL even if the token belongs to a paid account.

## 3. Start Laravel locally

Run Scholaria locally with Laravel's development server:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

## 4. Start ngrok using the reserved domain

After the reserved domain exists, start ngrok against port `8000`:

```powershell
ngrok http --url=<RESERVED_NGROK_DOMAIN> 8000
```

Example:

```powershell
ngrok http --url=scholaria.ngrok.app 8000
```

## 5. Update Laravel environment values

After the final public HTTPS URL is confirmed, update `.env`:

```env
APP_URL=https://<RESERVED_NGROK_DOMAIN>
SESSION_DRIVER=file
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

If Sanctum is used, also add:

```env
SANCTUM_STATEFUL_DOMAINS=<RESERVED_NGROK_DOMAIN_WITHOUT_HTTPS>
```

Example:

```env
APP_URL=https://scholaria.ngrok.app
SESSION_DRIVER=file
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=scholaria.ngrok.app
```

## 6. Clear Laravel caches

Run:

```powershell
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 7. Test the public URL

Verify these flows using the reserved HTTPS ngrok URL:

- Open `/login`
- Login with valid credentials
- Login with invalid credentials
- Redirect to the correct dashboard
- Session persists across protected pages
- Logout works correctly
- Protected pages redirect back to login after logout

## Notes

- Do not commit the ngrok authtoken into the repository.
- Do not store the ngrok authtoken in `.env`.
- The final public URL depends on the ngrok account's reserved domain configuration, not only on the token.
