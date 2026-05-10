-- =============================================
-- HealthKiosk Database Schema
-- Database: medical_kiosk
-- =============================================

CREATE DATABASE IF NOT EXISTS medical_kiosk;
USE medical_kiosk;

-- -----------------------------------------------
-- TABLE: patients
-- Stores patient info from scanid.php
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    patient_id    INT AUTO_INCREMENT PRIMARY KEY,   -- renamed from 'id'
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    age           INT,
    gender        ENUM('Male','Female') NOT NULL,
    phone         VARCHAR(20),
    barangay      VARCHAR(100),
    municipality  VARCHAR(100) DEFAULT 'Pozorrubio',
    province      VARCHAR(100) DEFAULT 'Pangasinan',
    face_image    VARCHAR(255),                     -- stores file path (e.g. uploads/faces/1.jpg)
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- TABLE: health_records
-- Stores all vitals linked to a patient session
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS health_records (
    record_id       INT AUTO_INCREMENT PRIMARY KEY,  -- renamed from 'id'
    patient_id      INT NOT NULL,
    record_code     VARCHAR(20),                     -- e.g. REC-123456
    visit_date      DATE,                            -- date of the visit
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Body Measurements
    weight_kg       DECIMAL(5,2),                    -- from weight.php
    height_cm       DECIMAL(5,2),                    -- from height.php
    bmi             DECIMAL(5,2),                    -- auto-calculated

    -- Vital Signs
    temperature_c   DECIMAL(5,2),                    -- from temperature.php
    spo2_percent    DECIMAL(5,2),                    -- from oximeter.php
    systolic_bp     INT,                             -- from bloodpressure.php
    diastolic_bp    INT,                             -- from bloodpressure.php
    pulse_bpm       INT,                             -- from bloodpressure.php

    -- Status flags (auto-evaluated by save_vitals.php)
    temp_status     VARCHAR(20),                     -- Normal / Fever / Low
    spo2_status     VARCHAR(20),                     -- Normal / Low / Critical
    bp_status       VARCHAR(20),                     -- Normal / Elevated / High
    pulse_status    VARCHAR(20),                     -- Normal / Low / High
    bmi_status      VARCHAR(20),                     -- Normal / Underweight / Overweight / Obese

    recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- INDEX for faster lookups
-- -----------------------------------------------
CREATE INDEX idx_patient_id ON health_records(patient_id);
CREATE INDEX idx_recorded_at ON health_records(recorded_at);
