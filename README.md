## 📚 Symfony Blog Publisher & Messaging Platform

A **modern blog publishing platform** built with **Symfony**, featuring user authentication, article management, comments, likes, categories, and **real-time one-to-one messaging** between users.

---

## ✨ Features

✅ **User Authentication** — Register, log in, verify email, reset password, and manage your profile.
✅ **Role Management** — Admin & user roles, with an admin dashboard for managing users and content.
✅ **Articles** — Create, edit, delete, and list articles with categories.
✅ **Comments** — Comment on articles with AJAX-powered forms and real-time updates.
✅ **Likes** — Like/unlike articles with instant feedback and live like counts.
✅ **Categories** — Organize articles, with full CRUD for admins.
✅ **Real-Time Messaging** — One-to-one chat between users, powered by **Mercure** for instant delivery.
✅ **Responsive UI** — Built with **Bootstrap 5** and **DataTables** for a modern, mobile-friendly experience.
✅ **RESTful API** — Endpoints for articles, comments, likes, users, and categories.

---

## 📸 Screenshots

| Blog Home                                      | Admin Dashboard                                            | Real-Time Chat                       |
| ---------------------------------------------- | ---------------------------------------------------------- | ------------------------------------ |
| ![Blog Home](public/screenshots/blog_home.png) | ![Admin Dashboard](public/screenshots/admin_dashboard.png) | ![Chat](public/screenshots/chat.png) |

---

## ⚙️ Getting Started

### ✅ Prerequisites

* PHP **8.1+**
* **Composer**
* **Node.js & npm**
* **PostgreSQL** or **MySQL**
* [**Mercure Hub**](https://mercure.rocks/docs/hub/install) (for real-time chat)
* **Docker** (optional, for development)

---

### 🚀 Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/yourusername/your-blog-project.git
   cd your-blog-project
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Install JavaScript & CSS assets**

   ```bash
   npm install
   npm run build
   ```
4. **Configure environment**

   Copy the example environment file and create your own local configuration:

   ```bash
   cp .env .env.local
   ```

   Edit `.env.local` and set your **database** and **Mercure credentials**.

   ✅ **Example `.env.local`**:

   ```dotenv
   ###> symfony/framework-bundle ###
   APP_ENV=dev
   APP_SECRET=YOUR_RANDOM_SECRET_KEY
   ###< symfony/framework-bundle ###

   ###> Doctrine ###
   DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/your_database_name?serverVersion=8.0"
   ###< Doctrine ###

   ###> Mercure ###
   MERCURE_PUBLISH_URL="http://localhost:3000/.well-known/mercure"
   MERCURE_JWT_SECRET="YOUR_MERCURE_JWT_SECRET"
   ###< Mercure ###
   ```

   **What to change:**

   * `APP_SECRET`: Generate a random string, e.g. run `php -r "echo bin2hex(random_bytes(16));"`.
   * `DATABASE_URL`: Use your DB credentials (`db_user`, `db_password`, `your_database_name`).
   * `MERCURE_PUBLISH_URL`: Should point to your Mercure hub.
   * `MERCURE_JWT_SECRET`: Must match the secret used by your Mercure hub.

   ✨ **Tip:** Never commit your `.env.local`. It should only live on your local machine or server.


5. **Prepare the database**

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Run the Symfony server**

   ```bash
   symfony server:start
   ```

7. **Start Mercure**
   Follow the [Mercure installation guide](https://mercure.rocks/docs/hub/install).

---

## 🚦 Usage

* Open [http://localhost:8000](http://localhost:8000)
* Register a new user or log in.
* As an **admin**, visit `/admin` to manage users and content.
* Use the **chat feature** to send real-time messages to other users.

---

## 📂 Project Structure

```
src/Entity/       Doctrine entities (User, Article, Comment, Message, etc.)
src/Controller/   Symfony controllers (web, API, chat)
templates/        Twig templates for the UI
assets/           JS, SCSS, and frontend assets
public/           Public web root
migrations/       Doctrine migrations
```

---

## 🔔 Real-Time Messaging

This project uses **Mercure** for real-time chat between users.
Make sure the **Mercure Hub** is running and properly configured in your `.env.local`.

---

## 📜 License

This project is licensed under the **GNU Affero General Public License v3.0**.
See [LICENSE](LICENSE) for details.

---

## ✏️ Credits

Developed by **Aneliot Ramamonjisoa** \[Pokaneliot].
Inspired by the **Symfony ecosystem** and the **Mercure protocol**.

---

