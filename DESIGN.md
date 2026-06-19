# DESIGN.md - Web Dev Agency Management System

## 1. Overview
Sistem internal terpadu untuk manajemen operasional jasa pembuatan website. Mencakup manajemen proyek, alat visualisasi (sketch), manajemen tugas (kanban), dan pencatatan keuangan.

## 2. Tech Stack
- **Environment:** Laragon (Localhost)
- **Backend:** Laravel 11 (PHP)
- **Frontend:** Vue.js 3 + Inertia.js (Composition API)
- **Styling:** Tailwind CSS
- **Database:** MySQL
- **Key Libraries:** - Fabric.js / Konva.js (Whiteboard/Sketch)
  - SortableJS / vue-draggable (Kanban Board)
  - Chart.js (Finance Dashboard)

## 3. Core Modules & UI Layout
### A. Dashboard
- Ringkasan metrik (Proyek Aktif, Revenue, Task Pending).
- Akses cepat ke proyek yang sedang berjalan.

### B. Project & Kanban (To-Do)
- Tampilan list proyek.
- Detail proyek menggunakan layout Kanban (To Do, In Progress, Review, Done).
- Drag and drop fungsionalitas untuk task.

### C. Whiteboard / Sketch
- Canvas area penuh.
- Tools: Pen, Rectangle, Circle, Text, Arrow.
- Fitur save state (simpan sebagai JSON ke database) dan Export to PNG.

### D. Finance
- Tabel arus kas (In/Out).
- Fitur generate Invoice PDF.
- Grafik sederhana untuk tren pendapatan bulanan.

## 4. Database Schema (Draft)
- `users`: id, name, email, password.
- `projects`: id, name, client_name, deadline, status.
- `tasks`: id, project_id, title, description, status (column_name), order.
- `sketches`: id, project_id, canvas_data (JSON).
- `transactions`: id, type (income/expense), amount, description, date.