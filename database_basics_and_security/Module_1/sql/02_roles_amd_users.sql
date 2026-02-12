CREATE ROLE 'role_app_ro';
CREATE ROLE 'role_app_rw';
CREATE ROLE 'role_migrator';

GRANT SELECT ON camping_db.* TO 'role_app_ro';
GRANT SELECT, INSERT, UPDATE, DELETE ON camping_db.* TO 'role_app_rw';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON camping_db.* TO 'role_migrator';

CREATE USER 'camp_app_ro'@'localhost' IDENTIFIED BY '...';
CREATE USER 'camp_app_rw'@'localhost' IDENTIFIED BY '...';
CREATE USER 'camp_app_migrator'@'localhost' IDENTIFIED BY '...';

GRANT 'role_app_ro' TO 'camp_app_ro'@'localhost';
GRANT 'role_app_rw' TO 'camp_app_rw'@'localhost';
GRANT 'role_migrator' TO 'camp_app_migrator'@'localhost';

SET DEFAULT ROLE 'role_app_ro' FOR 'camp_app_ro'@'localhost';
SET DEFAULT ROLE 'role_app_rw' FOR 'camp_app_rw'@'localhost';
SET DEFAULT ROLE 'role_migrator' FOR 'camp_app_migrator'@'localhost';
