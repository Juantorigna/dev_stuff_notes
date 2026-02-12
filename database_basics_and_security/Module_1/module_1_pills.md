# Database & Backend Security Concepts Checklist

## Threat Mindset
- Assume credentials can leak  
- Assume bugs exist  
- Assume misuse is possible  
- Design for failure, not perfection  

---

## Authentication & Identity
- MySQL identity is `'user'@'host'`, not just username  
- Restrict hosts (`localhost` > `192.168.x.%` > `%`)  
- Never use `%` unless absolutely required  
- Separate identities for different responsibilities  

---

## Principle of Least Privilege
- Every component gets only the permissions it strictly needs  
- Separate responsibilities:
  - `root` → setup only  
  - `migrator` → schema changes  
  - `app_rw` → read/write data  
  - `app_ro` → read-only  
- Reduce blast radius of compromise  

---

## Roles & Privilege Management
- Use roles instead of direct grants  
- Separate what is allowed (role) from who is allowed (user)  
- Always set a default role  
- Regularly verify privileges with `SHOW GRANTS`  

---

## Credential Hygiene
- Never hardcode credentials  
- Never commit secrets to version control  
- Store credentials outside repository (environment variables or runtime config)  
- Rotate passwords on suspicion  
- Revoke access on confirmed compromise  

---

## Database Design Security
- Use `UNSIGNED` for positive-only IDs  
- Use `UNIQUE` constraints for integrity  
- Use `CHECK` constraints for logical rules  
- Use foreign keys to enforce relationships  
- Prefer `InnoDB` (transactions, row locking, crash recovery)  

---

## Non-Guessable Public Identifiers
- Avoid exposing auto-increment IDs publicly  
- Use opaque public IDs (ULID / UUID / 128-bit random tokens)  
- Prevent IDOR (Insecure Direct Object Reference)  
- Non-guessable IDs are not a substitute for authorization  

---

## Data Exposure Minimization
- Avoid `SELECT *` in APIs  
- Explicitly select only needed columns  
- Do not expose internal column names blindly  
- Do not leak raw SQL errors  
- Follow principle of least data  

---

## SQL Injection Protection
- Always use prepared statements  
- Disable emulated prepares  
- Never concatenate user input into SQL  
- Validate inputs server-side  

---

## XSS Protection
- Always encode output (`htmlspecialchars`)  
- Never trust stored data  
- Treat database content as untrusted  

---

## Input Validation
- Client-side validation improves UX only  
- Server-side validation provides real security  
- Use strict filters (e.g., `FILTER_VALIDATE_INT`)  
- Use regex or strict parsing for dates  
- Enforce logical constraints (e.g., departure > arrival)  

---

## CSRF Protection
- Use CSRF tokens for state-changing requests  
- Store token in session  
- Validate using constant-time comparison (`hash_equals`)  

---

## Error Handling
- Never expose raw database exceptions  
- Log errors internally  
- Return generic error messages to users  

---

## Safe Architecture Practices
- Separate `/public` from `/app`  
- Centralize database connection logic  
- Use typed return hints (`: PDO`)  
- Enforce RO vs RW connection paths  
- Verify current identity with `SELECT USER()` or `SELECT CURRENT_USER()`  
