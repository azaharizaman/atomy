# Canary Atomy Next.js

A Next.js 16 frontend for the [Canary Atomy API](../canary-atomy-api), providing a UI for the Nexus enterprise management system. Displays modules, users, and feature flags from the API Platform backend.

## Prerequisites

- The [canary-atomy-api](../canary-atomy-api) must be running (default: `http://localhost:8000`)
- API database migrated and seeded (see [canary-atomy-api README](../canary-atomy-api/README.md#3-set-up-the-database))
- Node.js 18+

## Getting Started

### 1. Configure environment

```bash
cp .env.example .env.local
```

Edit `.env.local` if your API runs on a different URL or requires HTTP Basic auth:

```
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_API_AUTH=   # Optional: base64(username:password)
```

### 2. Install and run

```bash
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) to view the dashboard.

## Features

- **Dashboard** – Overview with counts for modules, users, and feature flags
- **Modules** – List available and installed Nexus modules
- **Users** – Tenant-scoped user accounts
- **Feature Flags** – Feature toggles and rollout configuration

## Tech Stack

- Next.js 16 (App Router)
- React 19
- Tailwind CSS 4
- TypeScript

## Learn More

- [Next.js Documentation](https://nextjs.org/docs)
- [Canary Atomy API](../canary-atomy-api/README.md)
