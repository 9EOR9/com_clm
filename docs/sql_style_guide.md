# SQL Style Guide

This document defines naming conventions and formatting guidelines for SQL schema definitions, queries, and other database objects. Adhering to this style guide ensures consistency and clarity across our database design.

---

## General Guidelines

- Be **descriptive and consistent**.
- Use **English** for all names.
- Avoid **abbreviations**, unless they are well-known (e.g., `id`, `qty`).
- Never use **reserved SQL keywords** as identifiers.
- Don't use backticks for quoting column names

---

## Naming Conventions

### Tables
- Use **plural**, **snake_case** names.
- Example: `users`, `order_items`, `product_reviews`

### Columns
- Use **singular**, **snake_case** names.
- Include the context: `user_id`, `created_at`
- Boolean columns: use prefixes like `is_`, `has_`
  - Example: `is_active`, `has_access`

### Primary Keys
- Use `id` as the primary key.
- Composite keys: use descriptive column names (`user_id`, `role_id`)

### Foreign Keys
- Name format: `<referenced_table>_id`
- Constraint name: `fk_<from>_<to>`
  - Example: `fk_orders_user_id`

### Indexes
- Format: `idx_<table>_<column>`
  - Example: `idx_users_email`

### Views
- Format: `<description>_view`
  - Example: `active_users_view`

### Triggers
- Format: `trg_<table>_<action>`
  - Example: `trg_orders_before_insert`

### Stored Procedures and Functions
Unless there is a very good reason to do so, avoid using stored procedures and functions whenever possible:

- Stored procedures live inside the database, not in your codebase.
- Harder to track changes, diff versions, or review changes in pull requests.
- Mixing business logic inside the database can make the system harder to understand.
- logic in the application layer is easier to test and refactor.
- stored procedures/functions are hard to debug

### DML (Data Manipulation Language)

- Avoid `REPLACE INTO`, instead use `INSERT INTO .. ON DUPLICATE KEY`

---

## SQL Formatting

### Keywords
- Write SQL keywords in **UPPERCASE**.
- Identifiers (tables, columns) in **lowercase**.

```
SELECT u.id, u.email
FROM users u
WHERE u.is_active = 1;
```






