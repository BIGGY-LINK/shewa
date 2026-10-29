# Ewa_luxe Closet

This project is configured for Vercel PHP serverless functions. The storefront, customer accounts, admin catalog, orders, and sessions use a hosted MySQL database.

## Deploy to Vercel

Vercel does not provide an official PHP runtime. This project uses the community-maintained `vercel-php@0.9.0` runtime, which runs PHP 8.5. The deployment needs a reachable, persistent MySQL database.

1. Create a MySQL database with a provider that accepts connections from Vercel Functions. Keep its hostname, port, database name, username, and password.
2. Push this project to a Git provider and import it into Vercel. Keep the project root at this directory; `vercel.json` configures the PHP runtime and routes.
3. In the Vercel project settings, add these environment variables for the environments you will deploy:

   | Variable | Value |
   | --- | --- |
   | `SHEWA_DB_HOST` | Hosted MySQL hostname |
   | `SHEWA_DB_PORT` | MySQL port, usually `3306` |
   | `SHEWA_DB_NAME` | The database name you created |
   | `SHEWA_DB_USER` | Database username |
   | `SHEWA_DB_PASSWORD` | Database password |
   | `SHEWA_SETUP_SECRET` | A strong, unique, one-time secret for initial setup |

   If your database requires a custom CA certificate, set `SHEWA_DB_SSL_CA` to the CA certificate contents (PEM) or to a certificate file path available to the PHP runtime. Follow your database provider's TLS requirements.
4. Deploy the project. Open `/setup.php`, enter the setup secret, and create the first admin account. Setup creates the tables and sample catalog inside the database you provisioned.
5. After setup succeeds, remove `SHEWA_SETUP_SECRET` from the Vercel project settings and redeploy. Then sign in at `/admin/login.php`. Customers register from the storefront.

If the site shows "The store is temporarily unavailable," the PHP function cannot connect to MySQL. Check that the Vercel variables above contain the hosted database's real connection details, that the database allows connections from Vercel, and that the latest deployment was created after saving the variables. Do not use `127.0.0.1` or the XAMPP `root` account in Vercel.

Do not commit database credentials or the setup secret. The Vercel setup endpoint is disabled after an admin account exists. A database user with permission to create tables, indexes, and foreign keys is needed for the initial setup; it can be restricted after initialization if desired. Vercel sessions are stored in the MySQL `app_sessions` table rather than the function's temporary filesystem.

The Vercel PHP runtime is a third-party project, not a Vercel-maintained runtime. Review its current documentation and limits before production use.

## Payments

Customer orders are saved as **awaiting payment**. This application does not process payments; configure and verify a payment provider before accepting paid orders.
