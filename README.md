# Municipal Budget Query Tool

A full-stack web application that enables municipal government staff to explore and query budget data using natural language. The app uses a local LLM (Ollama + Llama 3.2) to convert plain-English questions into SQL queries, returning results as tables and charts. Role-based access ensures administrators see all departments while department heads see only their own data.

## Tech Stack

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS
- **Build:** Vite
- **Database:** SQLite
- **AI/LLM:** Ollama with Llama 3.2 (local, free)
- **Charts:** Chart.js via vue-chartjs

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- Ollama (https://ollama.com)

## Setup Instructions

1. **Clone the repo**
   ```bash
   git clone <repo-url>
   cd budget-tool
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JS dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Create and seed the database**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   php artisan db:seed
   ```

6. **Install and start Ollama**
   ```bash
   # Install from https://ollama.com, then:
   ollama pull llama3.2
   ollama serve
   ```

7. **Start the development servers**
   ```bash
   # Terminal 1
   php artisan serve

   # Terminal 2
   npm run dev
   ```

8. **Visit** http://localhost:8000

## Demo Credentials

| Role            | Email           | Password   | Access              |
|-----------------|-----------------|------------|---------------------|
| Administrator   | admin@city.gov  | password   | All departments     |
| Department Head | parks@city.gov  | password   | Parks & Recreation  |

## Features

- **Natural Language Budget Queries** — Ask questions like "How much did we spend on contractors in Q3?" and get SQL + results
- **Interactive Dashboard** — Stat cards, bar charts, line charts, top vendors
- **Role-Based Access** — Admins see all departments; department heads see only their own
- **Data Visualization** — Automatic chart generation for query results with numeric data
- **Responsive Design** — Sidebar navigation, mobile-friendly layout

## Screenshots

<!-- Add screenshots here -->

## Note

Ollama must be running locally (`ollama serve`) with the `llama3.2` model pulled for the natural language query feature to work. No paid APIs or external services are required.
