# Ewa_luxe Closet — local XAMPP setup

1. In the XAMPP Control Panel, start **Apache** and **MySQL**.
2. Visit `http://localhost/SHEWA/setup.php`.
3. Create the first administrator account. Use a unique password of at least 12 characters; the password is stored as a hash.
4. Open `http://localhost/SHEWA/`. Customers can register or sign in from the storefront navigation.
5. Open `/admin/login.php` to sign in and manage the catalog and orders.

The setup page creates the `SHEWA` MySQL database and sample catalog. It runs only from localhost and is locked after the administrator is created.

By default, the local app uses the standard XAMPP MySQL account (`root` with an empty password). For anything beyond local development, create a dedicated MySQL account and set `SHEWA_DB_HOST`, `SHEWA_DB_USER`, and `SHEWA_DB_PASSWORD` in the Apache environment before starting Apache. Do not expose XAMPP or this development setup to the public internet.

Customer order submissions are saved as **awaiting payment**. This local app does not process payments; configure and verify a payment provider before accepting paid orders.
