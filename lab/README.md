# DevPulse Admin & Multi-PHP Infrastructure

A lightweight, enterprise-grade admin control dashboard and multi-container environment configured to run simultaneously across four different PHP versions (**PHP 5.6**, **PHP 8.2**, **PHP 8.4**, and **PHP 8.4.26**) connected to a **MySQL 8.0** database container.

This setup is ideal for testing legacy backward compatibility, verifying cross-version PHP behavior, or developing local microservices in isolated environments.

---

## 🌟 Key Features

- **Multi-Version PHP Stack**: Run PHP 5.6, 8.2, and 8.4 concurrently via Docker Compose.
- **Universal Codebase Compatibility**: Built using clean, backwards-compatible PHP syntax that runs seamlessly from PHP 5.6 to PHP 8.4 without deprecation or parse errors.
- **Dynamic Timezone Detection**: Automatically detects the client's browser timezone using JavaScript and passes it to the backend via secure cookies, eliminating PHP `date.timezone` warnings dynamically without hardcoding settings.
- **Authentication System**: Built-in session and cookie management with login/logout support and CSRF protection.
- **Live System Metrics**: Native server memory usage, PHP execution specs, system OS detection, and container uptime status.
- **MySQL Database Integration**: Built-in PDO health check that auto-connects to the MySQL container service.
- **Tailwind CSS & FontAwesome**: Modern, responsive UI designed for enterprise administrative control panels.

---

## 🏗 Project Structure

```text
.
├── docker-compose.yml   # Multi-container service definitions
├── index.php            # Combined Auth & Control Panel Application
├── router.php           # Built-in PHP server routing script
└── README.md            # System documentation
```

---

## 🚀 Quick Start & Installation

### Prerequisites

Ensure you have the following installed on your host machine:

* [Docker](https://docs.docker.com/get-docker/)
* [Docker Compose](https://docs.docker.com/compose/install/)

### 1. Clone or Download the Project

Navigate to your workspace directory containing `docker-compose.yml`, `router.php`, and `index.php`.

```bash
cd /path/to/project
```

### 2. Start the Docker Services

Boot up all four PHP container engines and the MySQL database in detached mode:

```bash
docker-compose up -d
```

### 3. Check Running Containers

Verify that all five containers are running properly:

```bash
docker-compose ps
```

---

## 🌐 Container Endpoints & Access

Each PHP version serves the dashboard independently on its assigned port:

| Service Container | PHP Version | Local URL | Port |
| --- | --- | --- | --- |
| `php_5_6` | **PHP 5.6 CLI** | `http://localhost:8080` | `8080` |
| `php_8_4` | **PHP 8.4 CLI** | `http://localhost:8081` | `8081` |
| `php_8_2` | **PHP 8.2 CLI** | `http://localhost:8082` | `8082` |
| `php_8_4_26` | **PHP 8.4.26 CLI** | `http://localhost:8083` | `8083` |
| `app_mysql` | **MySQL 8.0** | `localhost:3306` | `3306` |

---

## 🔑 Default Credentials

Use the following credentials to log into any of the dashboard ports:

* **Username**: `admin`
* **Password**: `admin123`

---

## 🗄 Database Credentials (MySQL)

The default MySQL configuration created by `docker-compose.yml`:

* **Host**: `mysql` *(Internal container network name)*
* **Port**: `3306`
* **Database**: `app_db`
* **User**: `app_user`
* **Password**: `app_password`
* **Root Password**: `rootpassword`

---

## 🛠 Management & Troubleshooting

### Stopping the Services

To shut down all running containers without removing persisted volumes:

```bash
docker-compose down
```

To stop containers and wipe the MySQL database volume:

```bash
docker-compose down -v
```

### Viewing Real-time Container Logs

To stream logs from a specific container (e.g., PHP 8.4):

```bash
docker-compose logs -f php84
```

To view logs for all running services:

```bash
docker-compose logs -f
```

---

## 📄 License

Distributed under the MIT License. Free for educational and commercial use.
