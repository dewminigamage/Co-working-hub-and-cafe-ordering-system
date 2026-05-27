# HubSpace – Co-Working Hub & Café Ordering System

A PHP + MySQL web application for managing co-working space bookings and café ordering,
built as part of the PHP Intern Developer Practical Test.

---

## ✅ Features Implemented

### Core Requirements
| Requirement | Status |
|---|---|
| User registration with hashed passwords (`password_hash`) | ✅ |
| Session-based login / logout | ✅ |
| Create, edit, delete own bookings | ✅ |
| View all active bookings across the hub | ✅ |
| Browse café menu (12 items) | ✅ |
| Add items to cart | ✅ |
| View cart with prices & total | ✅ |
| Remove items from cart | ✅ |
| PDO prepared statements (SQL injection prevention) | ✅ |
| `htmlspecialchars()` XSS prevention on all output | ✅ |
| CSRF token protection on all forms | ✅ |

### Bonus Tasks
| Bonus Feature | Status |
|---|---|
| AJAX "Add to Cart" (smooth, no page reload) | ✅ |
| Session cart for non-logged-in guests | ✅ |
| Cart merge on login | ✅ |
| Café menu search | ✅ |
| Category filter tabs | ✅ |
| Checkout & printable receipt | ✅ |
| Quantity +/- controls in cart | ✅ |
| Booking time-slot (start / end time) | ✅ |
| Delete confirmation modal | ✅ |

---

## 🗂️ Project Structure

```
Co-working-hub-and-cafe-ordering-system/
├── config/
│   ├── app.php          ← Session, BASE_URL, helpers
│   └── database.php     ← PDO connection (edit credentials here)
├── includes/
│   ├── header.php       ← Navbar, flash messages
│   └── footer.php
├── assets/
│   └── css/style.css    ← Custom theme (Bootstrap 5 + Poppins)
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── bookings/
│   ├── index.php        ← All bookings (compiled list)
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── cafe/
│   ├── menu.php         ← Menu + search + AJAX add-to-cart
│   ├── add_to_cart.php  ← AJAX handler (JSON) + fallback POST
│   ├── cart.php         ← View cart, qty controls
│   ├── update_cart.php  ← +/- quantity handler
│   ├── remove_item.php  ← Remove item handler
│   └── checkout.php     ← Place order + printable receipt
├── database/
│   └── schema.sql       ← Full DB schema + 12 sample café items
└── index.php            ← Dashboard
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP **7.4+** or **8.x**
- MySQL / MariaDB
- A local server: **XAMPP**, **WAMP**, **Laragon**, or any PHP server

### 2. Clone / Copy the project
Place the project folder inside your web root:
- XAMPP: `C:\xampp\htdocs\Co-working-hub-and-cafe-ordering-system\`
- WAMP:  `C:\wamp64\www\Co-working-hub-and-cafe-ordering-system\`

### 3. Create the database
Open **phpMyAdmin** (or any MySQL client) and run:
```sql
SOURCE /path/to/database/schema.sql;
```
Or paste the contents of `database/schema.sql` directly into phpMyAdmin's SQL tab.

### 4. Configure database credentials
Edit **`config/database.php`**:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'coworking_hub');
define('DB_USER', 'root');         // ← your MySQL username
define('DB_PASS', '');             // ← your MySQL password
```

### 5. Open in browser
Navigate to:
```
http://localhost/Co-working-hub-and-cafe-ordering-system/
```

---

## 🔐 Security Measures

| Threat | Mitigation |
|---|---|
| SQL Injection | PDO prepared statements with bound parameters on every query |
| XSS | All output wrapped in `e()` → `htmlspecialchars(ENT_QUOTES)` |
| CSRF | Random 32-byte token in every form, verified with `hash_equals()` |
| Session Fixation | `session_regenerate_id(true)` called on successful login |
| Password Storage | `password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12])` |
| Cookie Security | `httponly: true`, `samesite: Lax` session cookie flags |

---

## 📋 Database Schema (Summary)

| Table | Key Columns |
|---|---|
| `users` | id, name, email, password |
| `bookings` | id, user_id, space_name, booking_date, start_time, end_time, notes |
| `cafe_items` | id, name, description, price, image, category, is_available |
| `cart` | id, user_id, item_id, quantity |
| `orders` | id, user_id, order_number, total_amount, status |
| `order_items` | id, order_id, item_id, item_name, quantity, unit_price, subtotal |

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.x (PDO, sessions, prepared statements)
- **Database**: MySQL / MariaDB
- **Frontend**: Bootstrap 5.3, Bootstrap Icons 1.11, Google Fonts (Poppins)
- **AJAX**: Vanilla `fetch()` API for add-to-cart

---

## 📸 Space Types Available

- 🖥️ Hot Desk
- 🏢 Private Office
- 🗣️ Meeting Room
- 🎉 Event Space
- 📞 Phone Booth

---

*Built by Dewmini Navodya · HubSpace PHP Practical Test*
