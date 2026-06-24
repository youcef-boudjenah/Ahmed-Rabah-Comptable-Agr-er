ALTER TABLE clients
    ADD COLUMN contact_email VARCHAR(255) NULL AFTER adresse,
    ADD COLUMN contact_phone VARCHAR(30) NULL AFTER contact_email,
    ADD COLUMN contact_name VARCHAR(255) NULL AFTER contact_phone;
