-- SQL-Inserts für die neue users-Tabelle (generiert am 2025-08-01T15:12:35.094Z)

INSERT INTO "users" ("id", "first_name", "last_name", "name", "email", "email_verified_at", "birth_date", "role_id", "vacation_days_per_year", "initials", "entry_date", "employee_number", "is_active", "password", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at", "remember_token", "current_team_id", "profile_photo_path", "created_at", "updated_at", "mentor_id", "is_apprentice", "is_ausbilder", "outlook_access_token", "outlook_refresh_token", "outlook_expires_at") VALUES
;

-- Setze die ID-Sequenz für PostgreSQL nach dem Import
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
