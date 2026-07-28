# ACES — Academic Collaboration and Evaluation System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Java-21-ED8B00?style=flat-square&logo=openjdk&logoColor=white" alt="Java 21">
  <img src="https://img.shields.io/badge/JavaFX-21-007396?style=flat-square&logo=java&logoColor=white" alt="JavaFX 21">
  <img src="https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL-003B57?style=flat-square&logo=sqlite&logoColor=white" alt="Database">
  <img src="https://img.shields.io/badge/Python-3.10+-3776AB?style=flat-square&logo=python&logoColor=white" alt="Python">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="MIT License">
</p>

<p align="center">
  <a href="https://recessproj-production.up.railway.app/">Live Demo</a> ·
  <a href="#getting-started">Getting Started</a> ·
  <a href="#system-architecture">Architecture</a> ·
  <a href="#testing--verification">Testing</a>
</p>

---

## Overview

**ACES (Academic Collaboration and Evaluation System)** is a full-stack educational platform designed to streamline academic discourse, automate student assessment, and facilitate collaborative learning.

The system combines a Laravel web application, an offline-first JavaFX desktop client, and a Python machine learning microservice into a single ecosystem with full feature parity between the web and desktop experiences.

**Live application:** [recessproj-production.up.railway.app](https://recessproj-production.up.railway.app/)

---

## Table of Contents

- [Key Features](#key-features)
- [System Architecture](#system-architecture)
- [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
- [Testing & Verification](#testing--verification)
- [Building for Production](#building-for-production)
- [Contributing](#contributing)
- [License](#license)

---

## Key Features

- **Role-Based Workspaces** — Dedicated navigation and dashboards for Students (progress tracking, quizzes, study groups), Lecturers (quiz creation, grading, analytics), and Administrators (moderation, compliance, group management).
- **Discussion Forums & Study Groups** — Academic forums with markdown support, threaded replies, topic categorization, and collaborative study groups.
- **Interactive Quiz & Assessment Engine** — End-to-end quiz lifecycle management, including automated grading, participation scoring, and focus-mode test administration.
- **Web–Desktop Parity with Offline-First Sync** — The native JavaFX client synchronizes with the Laravel REST API (`/api/*`) via Laravel Sanctum while caching data locally in SQLite, so students can keep working offline.
- **Profile Management** — Username updates, secure password changes, profile picture uploads (JPG, PNG, WebP), and account deletion, available consistently across both clients.
- **ML-Powered Topic Classification** — A dedicated Python microservice automatically classifies discussion topics and powers content recommendations.

---

## System Architecture

ACES is organized as a multi-client monorepo:

```
recessProj/
├── web-app/                  # Laravel 12 web application & REST API
├── desktop-app/
│   └── ACESDesktop/          # Java 21 / JavaFX 21 offline-first desktop client
├── ml-service/                # Python / Flask topic classification service
├── run-desktop.bat            # Windows launcher for the desktop application
└── README.md
```

### Technology Stack

| Component | Technology | Description |
| :--- | :--- | :--- |
| Web Application & API | Laravel 12, PHP 8.2+, Tailwind CSS, Blade | Web UI and backend REST API server (`http://localhost:8000/api`), authenticated via Laravel Sanctum |
| Desktop Application | Java 21, JavaFX 21, Maven, SQLite-JDBC | Cross-platform desktop client with offline caching, custom theming (`aces.css`), and REST sync |
| ML Microservice | Python 3, Flask, scikit-learn | REST service for topic classification and academic content recommendations |
| Database | SQLite 3 / MySQL 8 | Relational storage for the server, with SQLite used for offline desktop caching |

---

## Getting Started

### Prerequisites

- PHP `8.2` or higher, with Composer
- Node.js `18+` and npm
- Java Development Kit (JDK) `21` or higher
- Apache Maven `3.8+`
- Python `3.10+` (optional, required only for the ML service)

### 1. Web Application & API Server

```bash
cd web-app
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The web application is available at `http://localhost:8000`, with the REST API served under `http://localhost:8000/api`.

### 2. Desktop Application

The JavaFX desktop client communicates with the Laravel API and maintains an offline SQLite cache.

**Option A — Windows quick launch:**

```cmd
run-desktop.bat
```

A custom API URL can be passed as an argument, e.g. `run-desktop.bat http://localhost:8000/api`.

**Option B — Launch via Maven:**

```bash
cd desktop-app/ACESDesktop
mvn javafx:run
```

### 3. ML Microservice (Optional)

```bash
cd ml-service
pip install -r requirements.txt
python app.py
```

---

## Testing & Verification

**Web application (PHPUnit / Artisan Test):**

```bash
cd web-app
php artisan test
```

Covers authentication, REST API endpoints, profile and avatar uploads, and account deletion (50+ tests).

**Desktop application (Maven / JUnit 5):**

```bash
cd desktop-app/ACESDesktop
mvn test
```

Covers offline SQLite DAO operations, DTO serialization, and password hashing (25+ tests).

---

## Building for Production

**Web application bundle:**

```bash
cd web-app
npm run build
php artisan optimize
```

**Desktop executable:**

```bash
cd desktop-app/ACESDesktop
mvn clean package
```

Produces a standalone desktop executable with a bundled Java runtime.

---

## Contributing

Contributions are welcome. Please open an issue to discuss proposed changes, or submit a pull request directly.

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
