STOCKWISE PHP - SETUP GUIDE

Requirements
- XAMPP (Apache, PHP, MySQL/MariaDB)
- A database named stockwise with the tables/columns expected by the PHP files

Run
1. Extract the stockwise folder into C:\xampp\htdocs\stockwise (Windows XAMPP).
2. Start Apache and MySQL in XAMPP.
3. Check connection.php. Default settings are localhost / root / blank password / stockwise.
4. Open http://localhost/phpmyadmin and confirm the stockwise database has the required tables and columns.
5. Open http://localhost/stockwise/index.php.
6. Register an account, then use phpMyAdmin to change that account's role to admin if you need to manage users/categories:
   UPDATE users SET role='admin' WHERE email='your-email@example.com';

DATABASE EXPECTED BY THE APP
users: id, name, email, password_hash, role
categories: id, name
items: id, user_id, category_id, name, quantity, price, description, updated_at

Important
- This package does not include your classmate's database. Do not import a new schema over an existing database without backing it up first.
- If Bootstrap's full CSS file (bootstrap.min.css) is available, put it beside header.php for the intended Bootstrap design. Otherwise offline.css provides a basic offline layout.
- The project has not been live-tested against your classmate's database. Check that the column names and foreign keys match the list above.

An optional database_template.sql is included as a reference for a NEW/EMPTY database.
Do not import it over the classmate's existing database; compare the schema first.

RECENT UI UPDATES
- Login page keeps Register links in both the top-right navigation and below the login form; both open register.php.
- The signed-in navbar has a labeled Logout button with a confirmation dialog.
- Admin and user dashboards use the same summary-card layout; regular users see totals for their own items, while admins see all inventory items.
- Add/Edit Stock form uses clear field labels and Save Changes/Add Stock buttons.
- Deleting an inventory item asks for confirmation first.
- Inventory table includes the calculated stock value for each item.
