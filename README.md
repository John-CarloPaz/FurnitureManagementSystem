# Cedarside Holding Corp. — Furniture Management System

A web-based real-time monitoring system with **3D model integration** for furniture product manufacturing and delivery. Single-tenant: one furniture company, its staff, and its customers.

The whole lifecycle runs end-to-end: **publish product → customer places a multi-item order → confirm → per-item production (DSS-scheduled) → QC → delivery with proof → completed** — every step owned by the right role, with live KPIs, a bottleneck-aware Decision Support System, and in-app + email notifications throughout.

## Monorepo layout
| Folder | Stack |
|---|---|
| [`backend/`](./backend) | Laravel 12 API (PHP 8.2) · PostgreSQL · Sanctum · spatie/permission · domain-driven (SOLID) |
| [`frontend/`](./frontend) | React 18 · Vite · TypeScript · Tailwind v4 · react-three-fiber (3D) · TanStack Query |
| [`docs/`](./docs) | Design foundation, per-module docs, setup/security/deployment guides |

## Quick start
See **[docs/SETUP.md](./docs/SETUP.md)**. In short:
```bash
# backend  (needs PostgreSQL)
cd backend && composer install && cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php -d upload_max_filesize=25M -d post_max_size=26M artisan serve      # :8000

# frontend
cd frontend && npm install && npm run dev                              # http://localhost:5173
```
Log in as the seeded admin (`admin@cedarside.local` / `password`).

## Modules (all 11 quotation modules)
3D Model Viewer · Manufacturing Tracking · Delivery Tracking · Order Management (FSM) ·
Role-Based Auth & User Management · KPI & Analytics · Notifications · Model-Driven DSS ·
Database & API Layer · Production Scheduling & Work Orders · QA & Testing

## Quality
- **43** backend feature tests + **8** frontend unit tests
- **Larastan level 6** (zero errors) · Pint · ESLint · CI on every push

## Docs
Start at [`docs/README.md`](./docs/README.md) — architecture, data model, FSM, RBAC, API contract, design system, and one doc per module.

---
_IT Capstone — Development of a Web-Based Real-Time Monitoring System with 3D Model Integration for Furniture Product Manufacturing and Delivery._
