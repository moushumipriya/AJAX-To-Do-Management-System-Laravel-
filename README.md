# AJAX-To-Do-Management-System-Laravel-
# AJAX To-Do Management System (Laravel)

## 🎯 Objective
Dynamic To-Do Management System using **AJAX + Database** (instead of session).
Users can **add, edit, delete, and view tasks without page reload**, with full authentication, CSRF, validation, and theme preference (cookies).

---

## 📌 Features
- ✅ Authentication (Laravel Breeze/UI Auth)  
- ✅ Only logged-in users can access `/tasks`  
- ✅ Tasks stored in database (`tasks` table)  
- ✅ AJAX CRUD (Add/Edit/Delete/View without reload) using Fetch API  
- ✅ CSRF protection on all requests  
- ✅ Validation:  
  - Title → required, min:3  
  - Description → optional, max:255  
- ✅ Theme preference (Light/Dark) stored in Cookies  
- ✅ Clean card-based UI (Google Form style)  
- ✅ Dynamic success/error messages  

---

## ⚙️ Tech Stack
- Laravel 11  
- PHP 8+  
- MySQL / MariaDB  
- Breeze / UI Auth (Authentication)  
- Fetch API (AJAX requests)  

---

## 📂 Setup Instructions
```bash
# 1. Clone the repository
git clone https://github.com/<your-username>/<your-repo>.git
cd todo-ajax

# 2. Install dependencies
composer install
npm install && npm run build

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure your database in .env
DB_DATABASE=todo_app
DB_USERNAME=root
DB_PASSWORD=

# 5. Run migrations
php artisan migrate

# 6. Start server
php artisan serve
