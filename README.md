# 🏍️ Rider Buddy: Your Buddy on the Road

[![Laravel](https://img.shields.io/badge/Backend-Laravel%2011-red)](https://laravel.com)
[![React Native](https://img.shields.io/badge/Mobile-React%20Native-blue)](https://reactnative.dev)
[![Expo](https://img.shields.io/badge/Framework-Expo-000020)](https://expo.dev)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**Rider Buddy** is a motorcycle assistance platform designed to help Filipino riders access roadside assistance and connect with verified mechanics.

Built around safety, reliability, and transparency, Rider Buddy provides a digital platform where riders can request mechanical assistance, verify mechanic arrival, and maintain service records for their motorcycles.

---

## 🛡️ Our Vision: "Kaluwasan ug Transparency sa Dalan"

Our goal is to make roadside assistance more accessible, reliable, and transparent for motorcycle riders.

We believe riders deserve access to trustworthy mechanics, clear service records, and a safer experience whenever they encounter motorcycle problems on the road.

---

## ✨ Implemented Features

### 🚨 1. Emergency SOS Consultations

- Riders can submit roadside assistance requests.
- Mechanics must meet account and verification requirements to receive eligible requests.
- Assigned mechanics can accept consultations.
- Duplicate unresolved SOS requests are prevented.
- Consultation status is managed throughout the service process.

### 📍 2. Mechanic Discovery and Eligibility

- Mechanic profiles contain shop information, specialization, experience, and service details.
- Mechanic availability and verification status are considered when selecting eligible mechanics.
- Location-based mechanic discovery supports nearby assistance.

### 📱 3. QR-Based Arrival Verification

- Riders generate a temporary QR code after a mechanic accepts a consultation.
- Assigned mechanics authenticate and scan the QR token.
- QR tokens are securely hashed in the database and expire after a limited period.
- Riders confirm or reject the mechanic's arrival.
- A consultation moves to `ongoing` only after successful rider confirmation.

### 📋 4. Motorcycle Maintenance Records

- Maintenance logs are linked to registered motorcycles.
- Service records include service type, description, odometer reading, service date, and cost.
- Mechanics must be verified and assigned to the consultation before creating its maintenance record.
- Maintenance logs can be associated with diagnostic reports.
- Soft deletion helps preserve data integrity and service history.

### 🔧 5. Consultation Management

- Riders can cancel pending consultations.
- Accepted and ongoing consultations require a cancellation reason.
- Cancellation records preserve relevant details, including who cancelled and when.
- Consultation completion requires a verified maintenance log.
- Authorization checks prevent unauthorized consultation updates.

### 👤 6. Authentication and Account Security

- Laravel Sanctum token-based API authentication.
- Role-based access for riders and mechanics.
- Mechanic verification and account eligibility checks.
- Protected API endpoints for consultation and maintenance operations.

---

## 🛠️ Technology Stack

| Component | Technology |
|---|---|
| Backend | Laravel 11 |
| Programming Language | PHP 8.2+ |
| Mobile Application | React Native |
| Mobile Framework | Expo |
| Database | PostgreSQL |
| API Authentication | Laravel Sanctum |
| API Architecture | RESTful API |
| Development Environment | XAMPP, Composer, Node.js |

---

## 🏗️ Project Architecture

Rider Buddy uses a separate backend and mobile application architecture.

- **Backend Repository:** [rider-buddy](https://github.com/nichollecera11/rider-buddy)
- **Mobile Application Repository:** [rider-buddy-app](https://github.com/nichollecera11/rider-buddy-app)

The Laravel backend handles authentication, business logic, database operations, consultation management, and maintenance records.

The Expo/React Native application provides the mobile interface through API integration.

---

## 🔒 Development Principles

- **Safety First:** Protect riders through controlled mechanic assignment and arrival verification.
- **Trust and Transparency:** Maintain accountable service records and consultation history.
- **Data Integrity:** Use database constraints, authorization policies, transactions, and validation.
- **Secure Access:** Apply authentication and role-based authorization to protected operations.

---

## 📌 Project Status

Rider Buddy is currently under active development. Implemented backend features are supported by automated tests, with mobile application integration and workflow validation ongoing.

---

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
