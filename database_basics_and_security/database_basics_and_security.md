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


