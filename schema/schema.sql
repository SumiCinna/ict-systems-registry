-- ============================================================
-- ICT Systems Registry - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS ict_systems_registry
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ict_systems_registry;

-- ------------------------------------------------------------
-- users
-- Basic account info collected at registration.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_name            VARCHAR(191)        NOT NULL,
    last_name              VARCHAR(100)        NOT NULL,
    first_name             VARCHAR(100)        NOT NULL,
    middle_initial         VARCHAR(5)          NULL,
    position_designation   VARCHAR(150)        NOT NULL,
    telephone_number       VARCHAR(20)         NOT NULL,
    email                  VARCHAR(191)        NOT NULL,
    password_hash          VARCHAR(255)        NOT NULL,
    created_at             TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- The tables below mirror the "List of Application Systems" and
-- "List of ICT Projects" sheets from the uploaded template. They
-- are not wired up to any screen yet, but are included now so the
-- schema is ready when those forms are built.
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS application_systems (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     INT UNSIGNED NOT NULL,
    agency_name                 VARCHAR(191) NOT NULL,
    application_name_version    VARCHAR(191) NOT NULL,
    date_of_implementation      DATE NULL,
    development_strategy        ENUM('In-house','Outsourced','Combination') NOT NULL,
    owns_ip                     ENUM('Yes','No') NOT NULL,
    mode_of_implementation      ENUM('Stand Alone','LAN','WAN','Web-based') NOT NULL,
    acquisition_cost            DECIMAL(15,2) NULL,
    annual_maintenance_cost     DECIMAL(15,2) NULL,
    annual_transaction_amount   DECIMAL(15,2) NULL,
    no_of_users                 INT UNSIGNED NULL,
    type_of_information         ENUM('External/Public','Internal/Agency Data') NOT NULL,
    scope_of_operation           ENUM('International','Nation-wide','Province','Municipal/City') NOT NULL,
    status                      ENUM('Fully implemented','Not fully rolled out yet, but with pilot implementation','Ongoing development and testing','Not utilized') NOT NULL,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appsys_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ict_projects (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                  INT UNSIGNED NOT NULL,
    project_name             VARCHAR(191) NOT NULL,
    description              VARCHAR(255) NOT NULL,
    start_date               DATE NULL,
    end_date                 DATE NULL,
    project_contract_cost    DECIMAL(15,2) NULL,
    third_party_provider     VARCHAR(191) NULL,
    funding_source           VARCHAR(191) NULL,
    status                   ENUM('Ongoing','Completed') NOT NULL,
    created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ictproj_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;