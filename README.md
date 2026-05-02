# Anonymous Forum System

[![Live Demo](https://img.shields.io/badge/Live-Demo-brightgreen?style=for-the-badge&logo=googledrive&logoColor=white)](https://icct-forumjoo.fwh.is/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge)](LICENSE)

A professional, secure, and highly organized anonymous discussion platform designed for community engagement. This system allows users to share thoughts and feedback anonymously while providing robust administrative controls.

---

## Live Demo
Experience the system live here: **[https://icct-forumjoo.fwh.is/](https://icct-forumjoo.fwh.is/)**

---

## Key Features

*   **Complete Anonymity**: User identities are protected with generated anonymous handles.
*   **Robust Administration**: Multi-tier admin system (Admin & Master) with moderation tools.
*   **Real-time Discussions**: AJAX-powered live feed for seamless communication.
*   **Dynamic Theming**: Built-in dark mode and light mode support.
*   **Advanced Analytics**: Detailed statistics dashboard for administrators.
*   **Modular Architecture**: Clean, folder-based structure for maximum maintainability.

---

## Project Structure

The project follows a modern modular directory layout:

| Folder | Description |
| :--- | :--- |
| **`/pages`** | Main entry points (Login, Register, Dashboard, Forum). |
| **`/core`** | System logic, database connections, and security guards. |
| **`/admin`** | Administrative tools and moderation panels. |
| **`/master`** | High-level system control and master authentication. |
| **`/ajax`** | Backend API handlers for real-time updates. |
| **`/assets`** | Frontend resources (CSS, JS, Images). |
| **`/utils`** | Migration scripts and developer utilities. |
| **`/docs`** | Detailed documentation and feature lists. |

---

## Installation & Setup

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/jasperorquiza-dev/anonymous-proj.git
    ```

2.  **Database Configuration**:
    *   Import the provided SQL schema from `/utils`.
    *   Configure your credentials in `core/config.php`.

3.  **Environment Variables**:
    Set the following variables for production security:
    *   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
    *   `MASTER_USERNAME`, `MASTER_PASSWORD_HASH`

4.  **Local Development**:
    Place the folder in your XAMPP `htdocs` directory and access via `localhost/anonymous-system`.

---

## Security Notice

This repository has been **sanitized**. No hardcoded credentials or sensitive session data are stored in the codebase. All security-critical configurations are handled via environment variables or encrypted database storage.

---

## Contribution

Feel free to fork this project and submit pull requests. For major changes, please open an issue first to discuss what you would like to change.

---

*Developed by [Jasper Orquiza](https://github.com/jasperorquiza-dev)*
