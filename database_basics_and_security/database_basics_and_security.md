# Database basics and security

## Part one 

In this section we'll see how to set MySQL to run locally, how to connect via CLI and GUI, understanding users, hosts, and ports. Additionally we'll see why root is dangerous, and finally we'll learn how to create a non-admin workflow.

### Section 1
Some important context first. It is important to always have a threat-driven mindset. In this way, we'll work keeping into account that the db might eventually be exposed, the credentials might be leaked, bugs could happen. 

### Section 1.1. Connect via Command Line

Open a terminal in your DB dir, and run: 
```sql
    mysql -u root -p
```

-u root --> username
-p --> password

By writing your account's password you'll be linked to MySQL servers. If successful, you'll see: 

    Welcome to the MySQL monitor.
    mysql>

### Section 2. Connet via GUI (Workbench)

Start by opening MySQL Workbench, then create a new connection by: 

1. Host: localhost
2. Port: 3306
3. Username: root
4. Password: store or promt 
5. Test connection --> connect 

Sometimes GUIs are preffered over CLI since they are useful for visualizing schemas, reduce human error while browsing data (due to the possibility of visualizing it as we said before), help during debugging. However, GUI can increase risk if the machine is shared, and if our profile is compromised. 

### Section 3. Understanding users, hosts, and ports (IMPORTANT)

By running the following inside MySQL: 
```sql
    SELECT user,
    host FROM mysql.user
```
We'll see outputs like: 
```sql
    root@localhost
    mysql.session@localhost
```
These inputs help us seeing existing account through admin view.<br>

**Why do users have a host?** <br>
In MySQL
```sql
    'user'@'host'
```

is the real identiy, not the username. This means that
```sql
    "root@localhost" != root@% /* they are not the same account*/
```

Even if the name is the same, the scope is different. This is very important for security. <br>

**How does MySQL use host + port?**<br>
The port (in our case 3306) identifies the server. Meanwhile, the host identifies **where** the client is allowed from. 

Examples: 
- **a)** localhost means it can run ONLY on local machine
- **b)** % means it can run anywhere (this represent a security risk)
- **c)** 192.168.1.% means it can run on LAN only

As soon as we start using non-admin introspection, we won't be able to query **mysql.user** anymore. This is actually good. A normal app user should never be allowed to inspect all server account. 

### Secton 5. Why are root/admin accounts dangerous?

**root** can perform all actions on the database, such as: 

- Drop any DB
- Read any table
- Create users
- Grant privileges
- Disable security features

If an attacker gets access to root by infiltraticng one of your scripts. they get full access to our DB. To our concern, in the case of a script having root access to our DB, it is not only the intentions of an attacker, but also any possible bug tht could end up being disrupted. In the case of a bug dealing in an undesireded manner with our DB, we'd face irreversable damage to the DB data. <br>
An attacker getting root could **DROP** databases and/or tables, read everything, create other users, grant privileges, weaken security configuration. In this scenario any SQL injection becomes catastrophic and any leaked **.env** file means full DB takeover.

### Section 6. Principle of Least Privilege

Here's the golden rule to DB security: 

**A component/script should have only the permission it stricly needs.**

Example 

Component | Needs
|:---------|:-------:|
|Web app | SELECT, INSERT |
|Admin tool | ALTER |
|Migration script | CREATE |
|Root | Setup only |

Least privilege is the main way to reduce blast radius. 

### Section 7. The ideal workflow
How would an ideal least privilege workflow b structured, then?

- **a)** **root**. Used only to set up and crete a DB.
- **b)** **migrator/schema user**. Used for schema evolution, hence creating and altering tables, indexes, constraints, etc. 
- **c)** **app_rw**. Read and write only. Used by endpoint to create and/or update data. It should not be allowed to alter data. 
- **d)** **app_ro**. Read only, used by paged to display data only. It should not be allowed to write anything. 

### Section 8. Creating roles.
A role is a group of privileges we can assign to users. Instead of granting the, directly to each user, we define roles once, and grant them globally. <br>
Roles allow for less repetition, less chance of mistake, and are easy to audit and update. <br>

To create them we: 

```sql
    CREATE ROLE 'role_app_ro';
    CREATE ROLE 'role_app_rw'; 
    CREATE ROLE 'role_migrator'; 
```

To grant them privileges we proceed in the following way. Assume our schema is called "*camping_db*": 

Read-only: 
```sql
    GRANT SELECT ON camping_db.* TO 'role_app_ro';
```

Reading and writing only: 
```sql
    GRANT, SELECT, INSERT, UPDATE, DELETE ON camping_db.* TO 'role_app_rw';
```

Migration (schema evolution): 
```sql
    GRANT, CREATE, ALTER, DROP, INDEX, REFERENCES ON camping_db.* TO 'role_migrator'; 
```

### Section 9. Creating non-root users with host restrictions

By "host restriction" we mean defining where login is allowed from. 

For local-dev, we: 
```sql
    CREATE USER 'camp_app_ro'@'localhost' IDENTIFIED BY 'use-a-strong-password'; 
    CREATE USER 'camp_app_rw'@'localhost' IDENTIFIED BY 'use-a-strong-password'; 
    CREATE USER 'camp_app_migrator'@'localhost' IDENTIFIED BY 'use-a-strong-password'; 
```

With "*use-a-strong-password*" we mean creating a brand-new password for the user, not your MySQL account password. 

### Section 10. Assigning roles to users and setting default role
To grant roles to users run: 

```sql
GRANT 'role_app_ro' TO 'campapp_ro'@'localhost'; 
GRANT 'role_app_rw' TO 'campapp_rw@localhost';
GRANT 'role_migrator' TO 'camp_migrator'@'localhost';  
```
Then, set default role, so it is active automatically, by: 

```sql
    SET DEFAULT ROLE 'role_app_ro' FOR 'camp_app_ro'@'localhost';
    SET DEFAULT ROLE 'role_app_rw' FOR 'camp_app_rw'@'localhost'; 
    SET DEFAULT ROLE 'role_app_migrator' FOR 'camp_migrator'@'localhost';
```
If we don't set a default role, the user may log in and have zero effective privilegies until the role is enabled for the session. 

### Section 11. Verify privileges and test the boundaties

- 1. Audit grants: 

```sql
    SHOW GRANTS FOR 'camp_app_ro'@'localhost';
    SHOW GRANTS FOR 'camp_app_rw'@'localhost'; 
    SHOW GRANTS FOR 'camp_migrator'@'localhost';
```

- 2. Test the rules. RO should fail on write: 

```sql
    INSERT INTO pitches(code, has_electricity) VALUES ('A1', true);
    -- Expected: permission denied
```
RW should fail on schema changes: 
```sql
    CREATE TABLE test_table(id INT);
    -- Expected: permission denied
```
### Section 12. Password rotation + revoking access
Rotate password: 

```sql
    ALTER USER 'camp_app_rw'@'localhost' IDENTIFIED BY 'new-strong-password';
```

Revoke a role: 

```sql
REVOKE 'role_app_rw' FROM 'camp:app_rw'@0'localhost';
```

Revoke user entirely:

```sql
DROP USER 'camp_app_rw'@'localhost';
```
In case credentials leak, we can use the command we've just seen to restructure our DB access by: <br>
rotating --> revoking --> replacing

## Part 2 - Database and schema creation (MySQL)

During this section we'll learn while building a small database for a camping/reservation app. The main touched topics will be: 

- **a)** what a schema is and how it builds our  DB
- **b)** tables
- **c)** columns with correct data types
- **d)** primary keys with auto-increment
- **e)** some first simple security design chioces

### Section 0. Open MySQL and confirm where you are

Open your terminal and access to MySQL using the commands learned from Part 1: 
```sql
    mysql -u root -p
```

You'll be asked for your password, and if it is correct you'll have access to your MySQL account. 

Once connected, run the following: 
```sql
    SELECT VERSION(); /*it tells you the MySQL you are running*/
    SHOW DATABASES; /*it tells you the databases you already have created */
```
If you already have a DB, you can run the following to start using it: 
```sql
    USE databasename /* where "databasename is you DB name*/
```
### Section 1. Create a database (schema)

If the DB doesn't exist yet, and you are building it form scratch, then run: 
```sql
    CREATE DATABASE the_name_you_want
        DEFAULT CHARACTER SET utf8mb4 /*utf8mb4 is the modern "full UTF-8" for MySQL*/
        DEFAULT COLLATE utf8mb4_0900_ai_ci; /*this breaks down in multiple parts. **utf8mb4** is described above; **0900** stands for UNicode rules from teh UCA; **ai** accent-insensitive; **ci** stands for case insensitive*/
```

The expected query is "**Query OK, 1 row affected**".
    
An important command to check which DB you're using is: 
```sql
    SELECT DATABASE(); 
```
It is useful cause if we ever forget to run
```sql
    USE databasename; 
```
We might end up creating tables in the wrong database. 

### Section 2. Create tables. 
Tables are the data structure of a database. 
#### Create **users** table 
Run this at mysql>
```sql
   CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, /*big stands for "bigger range. We'll use it as Primary Key (PK)*/
    public_id CHAR(26) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_public_id (public_id),
    UNIQUE KEY uq_users_email (email)
    ) ENGINE=InnoDB;
```

The expected query telling you everything has worked fine is "**Query OK...**"

**What do these commands mean?

- **a)** UNSIGNED. A numeric column lie **INT** normally can store negative and positive numbers. By using UNSIGNED we force positive only numbers in our DB. 
- **b)** VARCHAR(20). It stands for "a string up to 20 characters long.
- **c)** UNIQUE. It's a constraint that enforces values in a column (or grups of columns) to be different. By having UNIQUE KEY uq_users_email (email) we avoid having rows with a mail already present in another row. **uq_users_email** is just the name we adopt for the unique constraint.
- **d)** ENGINE=InnoDB. My SQL can store tables using different storage engines. What's a store engine? A store engine is the internal system MySQL uses to store and manage the table on disk. InnoDB is the current standard. InnoDB provides a series of features that can be helpful, such as: foreign keys (relationships like **reservation.user_id -> user.id**), row-level locking (better concurrency when multiple users book at one for example), transactions (all-or-nothing changes) ensure that in a multi-step process all steps are completed in order to grant row creation. If even one step is jumped, then all are discarted. Example: both a reservation procedure AND  the payment must be completed in order to generate the row. 
- **e)** Row-level locking. Better concurrency when multiple users book at once. Only one event at a time can be processed. 
- **f)** Better crach recovery than older engines. 

After running the code above we can check it writing: 
```sql
    DESCRIBE users; 
```
If everything works out fine, we should see columns including headers like id, public:id, email, etc. 

Since in our example we are building  DB for a camping, we'll create a table dedicated to each pitch: 
```sql
    CREATE TABLE pitches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL,
    has_electricity BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pitches_code (code)
    ) ENGINE=InnoDB;
```

We'll now add a table for reservations: 

```sql
    CREATE TABLE reservations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
        public_id CHAR(26) NOT NULL, 
        user_id BIGINT UNSIGNED NOT NULL, 
        pitch_id INT UNSIGNED NOT NULL, 

        arrival_date DATE NOT NULL,
        departure_date DATE NOT NULL, 

        notes TEXT NULL, 
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 

        PRIMARY KEY (id), 
        UNIQUE KEY uq_res_public_id (public_id), 
        KEY idx_res_user_date (user_id, arrival_date), 
        KEY idx_res_pitch_date (pitch_id, arrival_date), 

        CONSTRAINT fk_res_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE RESTRICT ON UPDATE CASCADE, 

        CONSTRAINT chk_dates
            CHECK (departure_date > arrival_date)
        ) ENGINE=InnoDB;
```

### Section 3. Security note.
#### Why should IDs not be guessable?
Let's start by defining a problem and then find the solution to it. If our API exposes: 

```sql
/reservations/123
/reservations/124
```

Attackers can try IDOR-style guessing (InsecureDirect Object Reference): "what if I request someone else's reservation?" <br>
Even if our authorization is correct, guessable IDs can leak internal volume (how many rows are there), it cna make scraping easier, and it increases risk if an auth check on one endipoint is ever missed. 

Now tht we have our probem, how do we solve it?<br>
A first common solutuion is using two IDs, one private (auto-increment), one public (opaque, to show to the clients). <br>
Good public-id format options are: 
- **a)** ULID. 26 chars, sortable, good for distributed systems
- **b)** UUID. 36 chars, commonly used
- **c)** our own custom random token that must have *at least 128 bits of randomness*

!! Remember !! Non-guessable ids help, but never subtitute authorization. 

##### Section 3.1. Security note. Avoiding over-exposed internal structure. 
As always, a golden rule od coding is building concreate healthy habits that last. Some to keep in our toolbox are: 

- **a)** Don't return raw SQL errors to users, log them on server-side instead
- **b)** Don't expose intrnal column names blindly in APIs (SELECT * --> avoid for APIs. A good protocol is to manually select the columns you actually need, SELECT public_id, email, created_at FROM users)
- **c)** Minimize what your endpoints return (principle of least data)




