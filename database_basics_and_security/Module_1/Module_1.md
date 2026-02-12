# Database basics and security
## Part 1 

In this section we'll see how to set MySQL to run locally, how to connect via CLI and GUI, understanding users, hosts, and ports. Additionally we'll see why root is dangerous, and finally we'll learn how to create a non-admin workflow.

### Section 1
Some important context first. It is important to always have a threat-driven mindset. In this way, we will always assume credentials can leak, bugs can exist, and systems can be misused

#### Section 1.1. Connect via Command Line

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
CLI connections reduce accidental exposure (no saved passwords, no GUI cache, no background processes).

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
This query shows all defined authentication identities from an administrative perspective.<br>

**Why do users have a host?** <br>
In MySQL
```sql
    'user'@'host'
```
is the real identiy, not the username. This means that
```sql
    "root@localhost" != root@% /* they are not the same account*/
```

Even if the name is the same, the scope is different. This is very important for security. Security design must assume failure, not perfect code. <br>

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

Component | Required privileges (minimum)
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
- **c)** **app_rw**. Read and write only. Used by endpoint to create and/or update data. It should not be allowed to alter the schema. 
- **d)** **app_ro**. Read only, used by paged to display data only. It should not be allowed to write anything. 

### Section 8. Creating roles.
A role is a named collection of privileges that can be assigned to one or more users. Instead of granting privileges directly to every user, we:

- **a)** Define roles once
- **b)** Assign privileges to the roles
- **c)** Grant roles to users

This approach reduces repetition, lowers the chance of misconfiguration,and makes audits and future changes easier and safer. <br>
In short, roles separate “what is allowed” from “who is allowed”.

#### Section 8.1 Creating roles.
We start creating three main roles, each aligned with a specific responsibility: 
```sql
    CREATE ROLE 'role_app_ro';
    CREATE ROLE 'role_app_rw';
    CREATE ROLE 'role_migrator';
```
At this stage, the roles exist but have no privileges yet. 

#### Section 8.2 Granting privileges to roles
Let's assume our database (schema) is called **camping_db**. <br>
*Read-only role*
```sql
    GRANT SELECT
    ON camping_db.*
    TO 'role_app_ro';
```
In this way, this role can read any table in camping_db, but cannot insert, update, delete nor alter anything. <br>

*Read-write role* (data only)<br>
This role is intended for application endpoints that must create or modify data, but must not change the schema, thus the database structure.
```sql
    GRANT SELECT, INSERT, UPDATE, DELETE
    ON camping_db.*
    TO 'role_app_rw';
```
By this snippet, this role can now modify and read rows. However, it cannot create, alter, or delete tables. Furthermore, it cannot modify indexes or constraints.

*Migration role* (schema evolution)
This role is suited for schema/database structure alternations, such as creating/deleting tables, adding indexes, or evolving constraints. 
```sql
    GRANT CREATE, ALTER, DROP, INDEX, REFERENCES
    ON camping_db.*
    TO 'role_migrator';
```
This role should **never** be used by the live application.

#### Section 8.3. Notes on roles.
Roles do nothing by themselves. They become effective only after being granted to a user, and being enabled for thast user (generally as a default role)


### Section 9. Creating non-root users with host restrictions

By "host restriction" we mean defining where login is allowed from. <br>
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
GRANT 'role_app_ro' TO 'camp_app_ro'@'localhost'; 
GRANT 'role_app_rw' TO 'camp_app_rw'@'localhost';
GRANT 'role_migrator' TO 'camp_app_migrator'@'localhost';  
```
Then, set default role, so it is active automatically, by: 

```sql
    SET DEFAULT ROLE role_app_ro TO 'camp_app_ro'@'localhost';
    SET DEFAULT ROLE role_app_rw TO 'camp_app_rw'@'localhost'; 
    SET DEFAULT ROLE role_migrator TO 'camp_app_migrator'@'localhost';
```
If we don't set a default role, the user may log in and have zero effective privilegies until the role is enabled for the session. <br>
Without a default role, a user can authenticate successfully but have zero effective privileges.

### Section 11. Verify privileges and test the boundaries

- 1. Audit grants:
```sql
    SHOW GRANTS FOR 'camp_app_ro'@'localhost';
    SHOW GRANTS FOR 'camp_app_rw'@'localhost'; 
    SHOW GRANTS FOR 'camp_app_migrator'@'localhost';
```
- 2. Test the rules.

RO should fail on write: 
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
REVOKE 'role_app_rw' FROM 'camp_app_rw'@'localhost';
```

Revoke user entirely:
```sql
DROP USER 'camp_app_rw'@'localhost';
```
In case credentials leak, we can use the command we've just seen to restructure our DB access by: <br>
rotating --> revoking --> replacing <br>
Concept hook: Rotation is for suspicion. Revocation is for compromise.

### Section 13. Checking who you are and exiting MySQL
When working with multiple users (root, RO, RW, migrator), it is important to verify which MySQL account you are currently authenticated as: 
```sql
    SELECT USER(); /* USER() shows the account name and host you used to authenticate.*/
```
or: 
```sql
SELECT CURRENT_USER(); /* CURRENT_USER() shows the MySQL account actually used for privilege checks.*/
```
To leave the mYSQL client and return to our terminal we run: 
```sql
    exit
```
This habit is especially useful when switching between root, app RO, app RW, and migrator users during setup and testing.
### Section 14. Where credentials should (and should not) live

**Rule of thumb:**

> Never hardcode database credentials (especially root) in application code.

In a secure setup:

- Root credentials are used **only for initial setup and administration**
- The application connects using **least-privilege users** (RO / RW)
- Database passwords are stored **outside the repository**, for example:
  - environment variables
  - system secrets or configuration injected at runtime

This prevents accidental leaks via:

- source control
- backups
- logs
- shared codebases

For learning purposes, credentials may appear in local configuration files, but **real applications must never commit secrets to version control**.

## Part 2 - Database and schema creation (MySQL)
In MySQL, the terms database and schema are effectively interchangeable. In these notes, we’ll use “database” for simplicity. <br>
During this section we'll learn while building a small database for a camping/reservation app. The main touched topics will be: 

- **a)** what a schema is and how it builds our  DB
- **b)** tables
- **c)** columns with correct data types
- **d)** primary keys with auto-increment
- **e)** some first simple security design chioces

### Section 0. Context. Open MySQL and confirm where you are

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
It is recommended to run the code in this section as "**camp_migrator**".
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

**What do these commands mean?**

- **a)** UNSIGNED. A numeric column lie **INT** normally can store negative and positive numbers. By using UNSIGNED we force positive only numbers in our DB. 
- **b)** VARCHAR(n). It stands for "a string up to n characters long.
- **c)** UNIQUE. It's a constraint that enforces values in a column (or groups of columns) to be different. By having UNIQUE KEY uq_users_email (email) we avoid having rows with a mail already present in another row. **uq_users_email** is just the name we adopt for the unique constraint.
- **d)** ENGINE=InnoDB. My SQL can store tables using different storage engines. What's a store engine? A store engine is the internal system MySQL uses to store and manage the table on disk. InnoDB is the current standard. InnoDB provides a series of features that can be helpful, such as: foreign keys (relationships like **reservations.user_id -> users.id**), row-level locking (better concurrency when multiple users book at one for example), transactions (all-or-nothing changes) ensure that in a multi-step process all steps are completed in order to grant row creation. If even one step is jumped, then all are discarded. Example: both a reservation procedure AND  the payment must be completed in order to generate the row. 
- **e)** Row-level locking. It allows multiple users to operate concurrently, while locking only the rows involved in each transaction. 
- **f)** Better crash recovery than older engines. 

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
            CHECK (departure_date > arrival_date) /*CHECK constraints are enforced in MySQL 8.0.16 and later*/
        ) ENGINE=InnoDB;
```

### Section 3. Security note.
#### Why should IDs not be guessable?
Let's start by defining a problem and then finding the solution to it. If our API exposes: 

```sql
    /reservations/123
    /reservations/124
```

Attackers can try IDOR-style guessing (InsecureDirect Object Reference): "What if I request someone else's reservation?" <br>
Even if our authorization is correct, guessable IDs can leak internal volume (how many rows are there), it can make scraping easier, and it increases risk if an auth check on one endpoint is ever missed. 

Now that we have our problem, how do we solve it?<br>
A first common solution is using two IDs, one private (auto-increment), one public (opaque, to show to the clients). <br>
Good public-id format options are: 
- **a)** ULID. 26 chars, sortable, good for distributed systems
- **b)** UUID. 36 chars, commonly used
- **c)** our own custom random token that must have *at least 128 bits of randomness*

!! Remember !! Non-guessable ids help, but are never a substitute for authorization. 

##### Section 3.1. Security note. Avoiding over-exposed internal structure. 
As always, a golden rule of coding is building concreate healthy habits that last. Some to keep in our toolbox are: 

- **a)** Don't return raw SQL errors to users, log them on server-side instead
- **b)** Don't expose internal column names blindly in APIs (SELECT * --> avoid for APIs. A good protocol is to manually select the columns you actually need, SELECT public_id, email, created_at FROM users)
- **c)** Minimize what your endpoints return (principle of least data)

## Part 3. DB-backend mini app
### Section 1. Defining the scope
In this part, we move from SQL design to application architecture, focusing on how backend code safely accesses the database.<br>
Our goal is to build a small project in which we'll create a read-only page that leverages RO credentials, a series of lists pitches from the table *pitches*. We'll also produce a reservation flow that leverages RW credentials, plus we'll build the necessary architecture around the SQL code using HTML, JS, and PHP. 

### Section 2. Project directory
It is useful to keep a clean directory architecture like the following: 

    /public/
    pitches.php
    reservation_new.php
    reservation_create.php

    /app/
    db.php
    security.php
    config.php   (or use environment variables)

Using this layout we'll keep everything under /public web-accessible, while having /app as internal code only.

### Section 3 Goal/big picture
Let's set up a small DB connection layer that keeps the codebase clean and enforces “least privilege”: <br>
- **a)** One shared function builds a PDO (PHP Data Object) connection the same way every time. <br>
- **b)** Two wrapper functions pick which DB user to connect with:
- 1. read-only for SELECTs
- 2. read/write for INSERT/UPDATE/DELETE

In this way we won't repeat DSN/options logic, and our app code can just ask for “ro” or “rw” without worrying about credentials.

#### Section 3.1. Where the DB settings live
**app/config.php** is the configuration file that returns an array with: 
- **a)** connection details such as host, database name, charset, etc
- **b)** the credentials for two users: 
- 1. ro_user/ro_pass
- 2. rw_user/rw_pass

#### Section 3.2. The connection factory
**app/db.php** is the file that exposes functions that produce PDO connections. These are:
- 1. pdo_common($cfg, $user, $pass)
- 2. db_ro() and db_rw()

**pdo_common($cfg, $user, $pass**) knows how to build a PDO connection given where to connect (done by $cfg that contains host, name, and charset), and who is connecting ($user, $pass). <br>
**db_ro()** and **db_rw()** are small wrappers that have the following goals: 
- **a)** load config
- **b)** grab $config['db']
- **c)** choose the correct credentials
- **d)** call pdo_common(...)

#### Section 3.3. Mental picture 
Reading path: 
    Our code (SELECT) → db_ro() → load config → pdo_common() → new PDO(...) → PDO handle

Writing path: 
    OUR code (INSERT/UPDATE/DELETE) → db_rw() → load config → pdo_common() → new PDO(...) → PDO handle

Thus, db_ro()/db_rw() are **public** entrypoints and **pdo_common** is the internal (not private) helper function, not intended to be called directly by application code.

#### Section 3.4. The shared builder
The shared builder aforementioned is "**pdo_common(...)**" and it is used as a single source to create PDO connections: 
```php
    pdo_common(array $cfg, string $user, string $pass): PDO {}
```
It takes: 
- **a)** **$cfg** where we stored host, name, and charset
- **b)** **$user**, **$pass** for credentials depending on what I want, either rw or ro.

And it returns a **configured PDO connection handle**. The whole point of this is to have just one place where I can change options. 

#### Section 3.5. Step-by-step inside **pdo_common()** *****
- 1. Build the DSN (Data Source Name) to tell the PDO which driver to use (mysql) and which host, database, charset to connect with 

```php
$dsn = sprintf(
"mysql:host=%s;dbname=%s;charset=%s",
$cfg['host'],
$cfg['name'],
$cfg['charset']
);
```
We use sprintf() to plug config values into the DSN template. 

#### Section 3.6. Creating the PDO object 
To create the connection: 
```php
new PDO($dsn, $user, $pass, [/* options*/]);
```
#### Section 3.7. Applying consistent PDO options
To set options so the connection behaves predictably:
- 1. PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION (I want errors to throw exceptions instead od failing silently)
- 2. PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC (I want rows as associate arrays), so I can: 
```php
    $row['email']
```
rather than numeric indexes like **$row[0]**.
- 3. **PDO::ATTR_EMULATE_PREPARES => false** is used to express the preference of real prepared statements instead of emulated ones. 

#### Section 3.8. PDO as return hint
By: 
```php
    function db_ro(): PDO
```
We use **: PDO** as a return type hint, meaning "this function returns a PDO object".

#### Section 3.9. The full snippet

```php
    //app/config.php

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'camping_db',
        'charset' => 'utf8mb4',

        'ro_user' => 'camp_app_ro',
        'ro_pass' => '...',

        'rw_user' => 'camp_app_rw',
        'rw_pass' => '...',
    ]
    ];
```

```php
    //app/db.php (PDO)

    function pdo_common(array $cfg, string $user, string $pass): PDO {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $cfg['host'],
            $cfg['name'],
            $cfg['charset']
        );

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //throw exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //associative arrys^
            PDO::ATTR_EMULATE_PREPARES => false, //real prepared statements
        ]);
        return $pdo;
    }

    function db_ro(): PDO {
        $config = require __DIR__ . '/config.php';
        $db = $config['db']; 
        return pdo_common($db, $db['ro_user'], $db['ro_pass']);
    }

    function db_rw(): PDO {
        $config = require __DIR__ . '/config.php';
        $db = $config['db'];
        return pdo_common($db, $db['rw_user'], $db['rw_pass']);
    }
```
## Part 4. Protecting reads and displaying the DB data safely
The threats we'll consider building defenses against are: 
- 1. SQL injections. 
- 2. XSS that might happen if we display user-provided content without encoding. 

```php
<?php
    // /public/pitches.php
    require __DIR__ . '/../app/db.php';

    $pdo = db_ro();

    // Optional filter from query string (user-controlled input!)
    $hasElectricity = filter_input(INPUT_GET, 'has_electricity', FILTER_VALIDATE_INT);

    // Base query (explicit column list)
    $sql = "SELECT code, has_electricity, created_at
            FROM pitches";

    // If a filter is present, extend the query safely
    $params = [];

    if ($hasElectricity !== null) {
        $sql .= " WHERE has_electricity = :has_electricity";
        $params[':has_electricity'] = $hasElectricity;
    }

    $sql .= " ORDER BY code";

    // Prepare + execute (even for SELECT)
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $pitches = $stmt->fetchAll();

    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    ?>
    <!doctype html>
    <html>
    <head>
    <meta charset="utf-8">
    <title>Pitches</title>
    </head>
    <body>
    <h1>Pitches</h1>

    <table border="1" cellpadding="6">
        <thead>
        <tr>
            <th>Code</th>
            <th>Electricity</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pitches as $p): ?>
            <tr>
            <td><?= e($p['code']) ?></td>
            <td><?= $p['has_electricity'] ? 'Yes' : 'No' ?></td>
            <td><?= e($p['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </body>
    </html>
```
The use of htmlspecialchars is extremely important to avoid script injection in any field. encoding prevents it from being executed by broswer. 
Prepared statements are mandatory whenever user input is involved.

### Part 4.1. Protecting writes (forms, inserts, validation, and CSRF)
Validation must always follow a double layer structure: 
- 1. Client-side validation to help the UX via a faster feedback
- 2. Server-Side validation, where the real protection resides

#### Section 1. Server-side validation, CSRF
CSRF is when a user is logged into our site and another side tricks their browser into sending a request by piggy-backing the user's session. At the moment our small project doesn't have a login session yet. Still, it is usefull to see it. After all, repetita iuvant.

```php
    // app/security.php
    //I will not annotate anything about CSRF since I'll dedicate a file solely to it
    // (CSRF is important but we'll later dedicate a full file to it.)

    session_start();

    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrf_validate(?string $token): bool {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
```

#### Section 2. Reservation from (GET) page
```php
<?php
// /public/reservation_new.php
require __DIR__ . '/../app/security.php';
require __DIR__ . '/../app/db.php';

$pdo = db_ro();

// Even without user input, we still use prepare() for consistency
$sql = "SELECT id, code
        FROM pitches
        ORDER BY code";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$pitches = $stmt->fetchAll();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Reservation</title>
</head>
<body>
<h1>Create Reservation</h1>

<form method="post" action="reservation_create.php" id="resForm">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>Pitch:</label>
    <select name="pitch_id" required>
        <?php foreach ($pitches as $p): ?>
            <option value="<?= (int)$p['id'] ?>">
                <?= e($p['code']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br><br>

    <label>Arrival date:</label>
    <input type="date" name="arrival_date" required>
    <br><br>

    <label>Departure date:</label>
    <input type="date" name="departure_date" required>
    <br><br>

    <label>Notes (optional):</label><br>
    <textarea name="notes" maxlength="500"></textarea>
    <br><br>

    <button type="submit">Create</button>
</form>

<script>
// Client-side convenience only (not security)
document.getElementById('resForm').addEventListener('submit', (e) => {
    const a = document.querySelector('[name="arrival_date"]').value;
    const d = document.querySelector('[name="departure_date"]').value;
    if (a && d && d <= a) {
        e.preventDefault();
        alert("Departure must be after arrival.");
    }
});
</script>
</body>
</html>
```

#### Section 3. Reservation create handler (POST + validation + prepared insert)
Goals: 
- 1. Validate inputs
- 2. Use RW connection
- 3. Use a prepared statement
- 4. Execute it safely
- 5. Handle exceptions without leaking internals
- 6. Redirect or show safe output
```php
    // /public/reservation_create.php  
    require __DIR__ . '/../app/security.php';
    require __DIR__ . '/../app/db.php';

    function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }


    function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo e($msg);
    exit;
    }

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    fail("Invalid CSRF token.", 403);
    }

    //basic server-side validation
    $pitch_id = filter_input(INPUT_POST, 'pitch_id', FILTER_VALIDATE_INT); 
    $arrival = $_POST['arrival_date'] ?? '';
    $departure = $_POST['departure_date'] ?? '';
    $notes = $_POST['notes'] ?? null;

    if (!$pitch_id) fail("Invalid pitch.", 422);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $arrival)) fail("Invalid arrival date.", 422);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $departure)) fail("Invalid departure date.", 422);
    if ($departure <= $arrival) fail("Departure must be after arrival.", 422);

    if ($notes !== null) {
        $notes = trim($notes);
        if ($notes === '') $notes = null; 
        if (strlen($notes) > 500) fail("Notes too long.", 422);
    }

    //In a real app, you'd get user_id from the logged-in user session.
    // For now, we’ll assume user_id = 1 exists.
    $user_id = 1;

    //you should generste a public_id in the app (ULID/UUID). Placeholder here:
    $public_id = bin2hex(random_bytes(16)); //32 hex chars ~ 128 bits of randomness
    try {
        $pdo = db_rw(); 
        // Prepared statement: prevents SQL injection because values are never concatenated into SQL
        $sql = "INSERT INTO reservations 
        (public_id, user_id, pitch_id, arrival_date, departure_date, notes)
        VALUES 
        (:public_id, :user_id, :pitch_id, :arrival_date, :departure_date, :notes)";

        $stmt= $pdo->prepare($sql); //prepare() builds a compiled query template with placeholders

        //execute with parameter array (cleaner than binParam/bindValue for most cases)
        $stmt->execute([ //execute([...]) binds values safely and runs the query
            ':public_id'      => $public_id,
        ':user_id'        => $user_id,
        ':pitch_id'       => $pitch_id,
        ':arrival_date'   => $arrival,
        ':departure_date' => $departure,
        ':notes'          => $notes,
        ]);
        // Success response (simple). In a more sophisticated app we could redirect to a success page
        http_response_code(201);
        echo "Reservation created. Public ID: " . e($public_id);

    } catch (PDOException $ex) { // catch(PDOException) prevents raw DB errors from reaching the user
          // IMPORTANT: don't leak DB details to the user.
    // Log server-side (file/syslog) in real app; for now we show a generic message.
    fail("Database error while creating reservation.", 500);
    }    
```
#### Section 4. Sanity checks. Quick "attack simulation"

- **Test a)** RO connection cannot write
Let's temporsry this in pitches: 
```php
    $pdo->exec("INSERT INTO pitches(code, has_electricity) VALUES ('Z9',1)");
```
We expect a **permission denied** message.

- **Test b)**: RW connection cannot alter schema
In *reservation_create.php*, try: 
```php
    $pdo->exec("CREATE TABLE should_fail(id INT)");
```
We expect a **permission denied** message.

#### Section 5. Flow chart

```mermaid
flowchart TD

    Browser["Browser"]

    Public["/public/*.php
    Web-accessible layer

    Files:
    - pitches.php
    - reservation_*.php

    Responsibilities:
    - receive input
    - validate input
    - encode output
    - choose RO / RW
    "]

    DB["/app/db.php
    DB access layer

    Functions:
    - db_ro()
    - db_rw()
    - pdo_common()

    Responsibilities:
    - load config
    - build DSN
    - create PDO
    - enforce privilege
    "]

    Config["/app/config.php
    Configuration boundary

    Contains:
    - host
    - db name
    - charset
    - RO credentials
    - RW credentials
    "]

    MySQL["MySQL

    Users:
    - camp_app_ro
    - camp_app_rw

    Roles enforce:
    - read-only
    - read/write
    "]

    Browser -->|HTTP GET / POST| Public
    Public -->|require| DB
    DB -->|require| Config
    DB -->|PDO connection| MySQL
