# Civilization Simulator

A web-based strategy simulation game where users can create and manage their own kingdoms on a shared world map. Players allocate resources, expand territory, and compete with other kingdoms in a dynamic, event-driven environment.

---

## Overview

This project is a PHP-based full-stack application that combines user authentication, real-time simulation logic, and an interactive frontend interface. Each user controls a kingdom that evolves over time based on decisions, resource allocation, and interactions with neighboring kingdoms.

---

## Features

### Authentication System
- User registration and login with secure password hashing
- Session-based authentication
- Logout functionality
- Input validation for usernames and passwords

Implemented in:
- auth.php
- login.php 
- register.php 

---

### Database Layer
- MySQL database connection using PDO
- Error handling and secure prepared statements
- Centralized connection logic

Implemented in:
- db.php

---

### Main Application (Frontend + Session Control)
- Protected routes (redirects unauthenticated users)
- Navigation between simulation panels
- Interactive UI for managing kingdoms
- Canvas-based world map

Implemented in:
- index.php 

---

### World Simulation Engine
- Hex-based world map system
- Kingdom creation with stat allocation (must total 200 points)
- Territory expansion and conflict mechanics
- Random and deterministic world events
- Kingdom elimination and world reset logic
- Periodic simulation ticks

Implemented in:
- world.php 

---

### Game Mechanics

#### Kingdom Creation
- Choose:
  - Name
  - Region (temperate, desert, arctic, tropical, island, river delta)
  - Government type
- Allocate points across:
  - Resources
  - Technology
  - Territory
  - Military
  - Prosperity

#### Development Actions
- Spend resources to improve:
  - Technology
  - Military
  - Prosperity

#### Simulation Events
- War and territorial expansion
- Resource discoveries
- Plagues and rebellions
- Trade and alliances
- Hegemony condition (75% world control resets game)

---

### Frontend

#### Styling
- Custom CSS with a medieval-themed aesthetic
- Responsive layout with grid and flexbox
- UI components for stats, panels, and navigation

Implemented in:
- style.css

#### JavaScript
- Handles UI interactions and API calls
- Updates world state dynamically
- Renders map and user interactions

Files:
- script.js
- world_map.js

---


## Project Structure

```bash
project-root/
├── auth.php        # Authentication API (login, register, logout)
├── db.php          # Database connection
├── index.php       # Main application interface
├── login.php       # Login page
├── register.php    # Registration page
├── world.php       # Simulation engine and API
│
├── style.css       # Styling
├── script.js       # Frontend logic
├── world_map.js    # Map rendering logic
│
└── README.md       # Project documentation
```

## Setup Instructions

### 1. Clone Repository
git clone <repo-url>
cd <project-folder>


### 2. Configure Database
Edit `db.php` with your database credentials:
define('DB_HOST', 'your_host');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');


### 3. Database Schema (Required Tables)
You need the following tables:
- users
- kingdoms
- world_hexes
- world_state
- world_events

(Create them manually or via SQL script if available.)

### 4. Run the Application
- Place project in your local server directory (e.g., XAMPP / MAMP / Apache)
- Start server
- Open in browser:
- http://localhost/<project-folder>/login.php


---

## API Endpoints

### Authentication (auth.php)
- POST `action=login`
- POST `action=register`
- POST `action=logout`

### World (world.php)
- `action=state` → Get world state
- `action=found` → Create kingdom
- `action=develop` → Upgrade stats
- `action=tick` → Advance simulation
- `action=targets` → Get rival kingdoms

---

## Security Notes

- Passwords are hashed using bcrypt
- SQL injection prevented via prepared statements
- Sessions used for authentication
- Input validation enforced on both frontend and backend

---

## Future Improvements

- AI-driven simulation enhancements
- Mobile optimization

---

## License

This project is for educational and experimental purposes.

---

## Author

Developed as a full-stack simulation system combining backend logic, frontend interaction, and game mechanics design.