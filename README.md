# 🌐 SMK MEDIKACOM — Official Student Council (OSIS & MPK) Web Portal

<p align="center">
  <img src="https://img.shields.io/badge/Release-Stable%20Open--Source-10b981?style=for-the-badge&logo=git&logoColor=white" alt="Release Status" />
  <img src="https://img.shields.io/badge/Home_Ver-3.7.7-3b82f6?style=for-the-badge&logo=lighthouse&logoColor=white" alt="Home Version" />
  <img src="https://img.shields.io/badge/Forum_Ver-5.1.1-8b5cf6?style=for-the-badge&logo=discourse&logoColor=white" alt="Forum Version" />
  <img src="https://img.shields.io/badge/Security_Ver-27.2.2-ef4444?style=for-the-badge&logo=shield&logoColor=white" alt="Security Version" />
  <img src="https://img.shields.io/badge/License-MIT-f59e0b?style=for-the-badge" alt="License" />
</p>

<p align="center">
  <i>A comprehensive, modern, and open-source web application ecosystem built specifically for School Student Councils (OSIS & MPK). Open-sourced so other schools can adopt, adapt, and build their own digital ecosystem.</i>
</p>

<p align="center">
  🔗 <b>Live Demo / Production Preview:</b> <a href="https://osis-medikacom.rf.gd/"><b>osis-medikacom.rf.gd</b></a>
</p>

---

## 📌 Web Information & Telemetry

> **Snapshot State (Stable Classic Release)**  
> Captured on the legendary late-night deployment push.

| Metric | Version / Info | Notes |
|---|---|---|
| **Home Platform** | `v3.7.7` | Liquid glass styling, responsive layout, dynamic landing |
| **Community Forum** | `v5.1.1` | Real-time thread feeds, notification center, voting |
| **Security Layer** | `v27.2.2` | 27 Major Security Iterations + 2 Major & 2 Minor patches |
| **Last Synchronized** | `01:00 AM — 2025/07/12` | Production hotfix & security lockdown |
| **Architecture** | PHP Native + MySQL + Tailwind CSS | Pure performance with server-level hardening |

---

## 💡 What is this Project?

This repository contains the **stable pre-update release** of the official website for **OSIS & MPK SMK Medikacom**.

Tujuan proyek ini di-open-source-kan adalah **sebagai referensi dan blueprint bagi sekolah-sekolah di seluruh Indonesia** yang ingin bertransformasi secara digital dan membangun portal OSIS/kegiatan siswa mereka sendiri—mulai dari sistem pemilihan ketua (e-voting) yang anti-manipulasi, pendaftaran ekstrakurikuler terpusat, hingga forum interaktif siswa.

---

## ⚡ Key Highlights & Core Modules

### 💬 1. Interactive Student Forum (`Forum v5.1.1`)
- Discussion boards for students to exchange ideas, share opinions, and collaborate.
- Post feeds, comment trees, thread categorization, and unread notification counter.
- Role-based moderation system for OSIS administrators and moderators.

### 🗳️ 2. Digital E-Voting System (Pemilihan OSIS)
- Secure, transparent, and streamlined election system for OSIS & MPK candidates.
- Real-time vote tallying with duplicate voting prevention mechanism.
- Tamper-resistant database structure to ensure democratic elections.

### 🎯 3. Online Extracurricular Registration (Pendaftaran Eskul)
- Paperless student recruitment system for all clubs and extracurricular activities.
- Centralized data management so organizers can monitor member intake without messy Google Forms or manual paper logs.

### 📸 4. Media Gallery & Documentation Hub
- Dedicated space to archive school memories, competitions, and internal events.
- High-quality photo album viewer with responsive Lightroom-inspired preview support.

---

## 🛠️ Tech Stack & Architecture

- **Backend:** PHP (Pure native, optimized for high throughput without heavy framework overhead)
- **Database:** MySQL / MariaDB (Relational schema with normalized indices)
- **Frontend:** Modern Tailwind CSS & Custom CSS (Liquid Glassmorphism aesthetic)
- **Animation & Typography:** AOS (Animate on Scroll) & Google *Plus Jakarta Sans*
- **Server Environment:** Apache with optimized `.htaccess` rewrites and directory security guards

---

## 🛡️ Security Hardening Evolution (`v27.2.2`)

Security wasn't an afterthought—it became an obsession.

Saat pertama kali dirilis ke publik, disadari bahwa banyak celah keamanan standar yang masih terbuka. Sejak rilis `v1.2`, arsitektur keamanan dirombak habis-habisan secara berkala hingga mencapai versi **`v27.2.2`**:
- **27 Major Security Overhauls:** Rombakan struktural autentikasi, sanitasi input, dan proteksi server.
- **`.2.2` Semantic Patches:** 2 Major security hardening updates + 2 Minor targeted patches.
- **Defenses Implemented:** SQL Injection prevention, directory traversal prevention via strict `.htaccess` rules, XSS mitigation, secure session validation, and sensitive upload quarantining.

---

## 📖 Behind The Code: The Journey

```
  [ Kelas 10 ]  ──>  Ideation & 1st Prototype Presentation
                           │
                           ▼
  [ Kelas 11 ]  ──>  Public Launch & The Wake-Up Call: Security Overhaul
                           │
                           ▼
  [ Kelas 12 ]  ──>  Stable v3.7.7 / v27.2.2 & Open-Source Release
```

> *"Gwe bikin web osis ini murni karena kecintaan buat bikin project. Kalau gabut 3-5 hari ga ada yang dikerjain, badan bisa kerasa sakit ga tau kenapa."*

Project ini tidak lahir instan dalam semalam:
1. **The Presentation:** Di awal masa pengembangannya pas masih kelas 10, prototipe website ini dipresentasikan langsung secara solo ke para stakeholder sekolah:
   - 👨‍🏫 Pembina OSIS
   - 💻 Kepala Program Keahlian RPL (Rekayasa Perangkat Lunak)
   - 📋 Wakasek Kesiswaan & Wakasek Kurikulum
   - 🏫 Kepala Sekolah SMK Medikacom
2. **4x UI Theme Iterations:** Tampilan antarmuka (UI/UX) telah berganti konsep hingga 4 kali. Dimulai dari template opensource biasa, dirombak total menjadi custom UI buatan tangan sendiri, terus dievolusi hingga mencapai tema modern **Liquid Glassmorphism** saat ini.
3. **The TKJ Pride:** 
   > *"Banyak guru nanya, 'Kenapa ga masuk jurusan RPL?' Tapi gwe emang dari dulu anak TKJ (Teknik Komputer dan Jaringan). Gwe jauh lebih cinta sama backend dan server administration... apalagi suara deru kipas server di rack, itu bener-bener candu."*

---

## 🚀 Quick Setup & Local Deployment

Mau coba deploy di localhost atau server sekolah kamu?

### 1. Clone the Repository
```bash
git clone https://github.com/tjiani101/osis-website-smkmedikacom.git
cd osis-website-smkmedikacom
```

### 2. Database Configuration
1. Import file database SQL (jika tersedia) ke server MySQL / phpMyAdmin kamu.
2. Buka `db.php` dan sesuaikan kredensial database lokal:
   ```php
   $host = "localhost";
   $user = "your_db_user";
   $pass = "your_db_password";
   $dbname = "your_db_name";
   ```

### 3. Spin up the Local Server
Kamu bisa langsung menggunakan script launcher bawaan:
```bash
chmod +x jalankan.sh
./jalankan.sh
```
Atau jalankan manual menggunakan built-in PHP server:
```bash
php -S localhost:8000
```
Buka browser favoritmu dan arahkan ke `http://localhost:8000`.

---

## 👤 Author & Sole Developer

<div align="center">
  <br />
  <h3><b>Tjiani</b></h3>
  <p><b>Ghani — Teknik Komputer dan Jaringan (TKJ XII-A)</b></p>
  <p><i>Developer, Backend Enthusiast & Server Geek who prefers to be called <b>Tjiani</b>.</i></p>

  <a href="https://instagram.com/TJiani101" target="_blank">
    <img src="https://img.shields.io/badge/Instagram-@TJiani101-E4405F?style=for-the-badge&logo=instagram&logoColor=white" alt="Instagram @TJiani101" />
  </a>
  <a href="https://github.com/tjiani101" target="_blank">
    <img src="https://img.shields.io/badge/GitHub-tjiani101-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub tjiani101" />
  </a>
  <br /><br />
</div>

---

<p align="center">
  Made with ☕, late-night debugging sessions, and server fan white noise. <br />
  <b>Free to fork and adapt for your school's OSIS!</b>
</p>
