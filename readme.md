# Anonymous System - ICCT Forum

A PHP-based anonymous forum system featuring real-time messaging, administrative controls, and a master portal for system management.

## 🌐 Live Demo

You can view the live version of this system here: [https://icct-forumjoo.fwh.is/](https://icct-forumjoo.fwh.is/)

## 🚀 Features

- **Real-time Messaging**: Instant message updates using AJAX polling.
- **Anonymous Interaction**: Users can interact within the forum environment.
- **Admin Dashboard**: Comprehensive tools for managing users, messages, and system logs.
- **Security**: CSRF protection, rate limiting, and session management.
- **Maintenance Mode**: Ability to put the system under maintenance with a master bypass.
- **Database Migrations**: Built-in system to manage database schema updates.

## 🛠️ Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx) - Recommended: XAMPP for local development

### Steps

1. **Clone the repository**:
   ```bash
   git clone https://github.com/jasperorquiza-dev/anonymous-proj.git
   ```

2. **Configure Database**:
   - Create a new MySQL database (e.g., `icct_forum`).
   - Copy `config.php` (if you need to customize) or edit the existing one.
   - Update `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE` in `config.php`.

3. **Initialize Database**:
   - Run the migrations by accessing `database_migrations.php` in your browser or via CLI to set up the tables.

4. **Access the System**:
   - Point your web server to the project directory.
   - Default entry point is `welcome.php` or `index.php`.

## 🛡️ Security Notice

All sensitive credentials and master account secrets have been removed from this repository for security. To use administrative features, ensure you configure your own environment variables or update `config.php` locally.

## 📄 License

This project is for educational/history purposes.