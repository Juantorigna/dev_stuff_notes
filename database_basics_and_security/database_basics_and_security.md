# Database basics and security

## Part one 

In this session we'll see how to set MySQL to run locally, how to connect via CLI and GUI, understanding users, hosts, and ports. Additionally we'll see why root is dangerous, and finally we'll learn how to create a non-admin workflow.

### Section 1
Some important context first. It is important to always have a threat-driven mindset. In this way, we'll work keeping into account that the db might eventually be exposed, the credentials might be leaked, bugs could happen. 

### Section 1.1. Connect via Command Line

Open a terminal in your db dir, and run: 

    mysql -u root -p

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

Sometimes GUIs are preffered over CLI since they are useful for visualizing schemas, reduce human error while browsing data (due to the possibility of visualizing it as we said before), help during debugging. 

### Section 3. Understanding users, hosts, and ports (IMPORTANT)

By running the following inside MySQL: 

    SELECT user, host FROM mysql.user

We'll see outputs like: 

    - root@localhost
    - mysql.session@localhost

**Why do users have a host?**

In MySQL

    'user'@'host'

is the real identiy, not the username. This means that
    "root@localhost" != root@%.

Even if the name is the same, the scope is different. This is very important for security. 

**How does MySQL use host + port?**

The port (in our case 3306) identifies the server. Meanwhile, the host identifies **where** the client is allowed from. 

Examples: 
- **a)** localhost means it can run ONLY on local machine
- **b)** % means it can run anywhere (this represent a security risk)
- **c)** 192.168.1.% means it can run on LAN only

### Secton 5. Why are root/admin accounts dangerous?

**root** can perform all actions on the database, such as: 

- Drop any db
- Read any table
- Create users
- Grant privileges
- Disable security features

If an attacker gets access to root by infiltraticng one of your scripts. they get full access to our db. To our concern, in the case of a script having root access to our db, is not only the intentions of an attacker, but also any possible bug. In the case of a bug dealing in a undesireded manner with our db, we'd face irreversable damage to the db data. 

### Section 6. Principle of Least Privilege

Here's the golden rule to db security: 

**A component/script should have only the permission it stricly needs.**

Example 

Component | Needs
|:---------|:-------:|
|Web app | SELECT, INSERT |
|Admin tool | ALTER |
|Migration script | CREATE |
|Root | Setup only |







