# Module 2
## Part 1
### Part 1, Section 1. From business logit to relational model
In this section we'll learn the following aspects: 
- **a)** set of tables,
- **b)** columns
- **c)** relationships
- **d)** redoundancy
- **e)** security mindset

#### Sub-Section 1.0. What's a relational model 
A relational database stores data into tables. Tables are like Excel sheets, we have columns telling us what kind of info we are dealing with and rows, the observations/items. 

By "relational" we mean that **tables are allowed to reference each other**, and the database can enforce those relationships (e.g. foreign keys).

#### Sub-Section 1.1. Start from the requirements (business logic)
We, as a business, usually need a specific flow to follow in order for us to get the necessay data. A practical example is: <br>
"*People can book a resource for a time range*".

#### Su-Section 1.2. Identifying entities and what they store
Entities are waht become tables. E.g. users, resources, bookings, etc. By identifying what each tables must contain we understand which olumns must be created. 
The columns usually present in any databse, independently from its popruse, answer to the following issues: 
    - Identity
    - Integrity
    - Traceability
    - Safety
    - Security
    - Auditing
    - Mantainability/concurrency safety

- 1. Identity: 
Every row must be uniquely identifiable in a stable way. Otherwise, we cannot safely update rows, safely delte them, reference them from other tables, and we'd have a higher risk of ambiguity. 

e.g.
```sql
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
```
Later, we'll actually see how to make ids non-guessable and why they need to be so. The example above here is just for conceptual clearity. 

- 2. Integrity: 
It ensures a databse can't enter an invalid state. Columns and involvedcontraints are: 
    - a) PRIMARY KEY
    - b) FOREIGN KEY
    - c) UNIQUE
    - d) CHECK
    - e) STRICT MODE (behavior enforcement policy, we'll see it later)
Integrity is, in the end, enforced by constraints. 

- 3. Traceability
By using having a **created_at** column, we use a time stmap to know WHEN the row was created, it can thus help use during debugging, fraud investigation, auditing, data life-cycle management, and backfill detection. 

```sql
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```
We also need to know if any row was ever changed, when it happed, if a bug overwrote data or a script misbehaved. 

```sql
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
ON UPDATE CURRENT_TIMESTAMP 
```
- 4. Safety (!!important)
Hard deletes are dangerous. If weever delete a user, we could have a booking break. If we ever delete a resource, the historical report become inconsistent.
```sql
DELETE FROM users WHERE id = 5;
```
The row disappears since it is phisically removed from the table. After that: 
```sql
SELECT * FROM users WHERE id = 5;
```
Returns nothing. The database has no memory of it. 

Instead, we mark them as deleted, hence applying what's known as **soft delete**:
```sql
ALTER TABLE users ADD deleted?at DATETIME NULL;
``` 
Instead of: 
```sql
DELETE FROM users WHERE id = 5;
```
We: 
```sql
UPDATE users
SET deleted_at = NOW()
WHERE id = 5;
```
The row still exists but it is logically deleted. 

This is safer but drags an architectural cost. Each query must remember that: 
```sql
WHERE deleted_at IS NULL
```
If we forget ti thandeleted rows might reappear, our app becomes inconsistent, bugs become subtle. 

- 5. Security
We've already seen how to apply an id to eachrow to avoid amiguity. However, it is common (and preferred) practice to have two of them. One private and one public. Many systems have a non guessable public id, either custom with at least 128 bits of randomness or ULID/UUID, and a public one. 
e.g. for public key: 
```sql
public_id CHAR(26) NOT NULL UNIQUE
```
- 6. Auditing 
Auditing procedures often include references telling which user made the change. 
```sql
created_by BIGINT UNSIGNED NULL
updated_by BIGINT UNSIGNED NULL
```
- 7. Mantainability/Concurrency safety
Large systems usually add: 
```sql
version INT NOT NULL DEFAULT 1
```
Used got optimistic locking that prevents two users from overwriting each other's changes silently. We'll see it in a deeper manner later. 
