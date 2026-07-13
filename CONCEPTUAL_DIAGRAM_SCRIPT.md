# Vehicle Compliance System Conceptual Diagram Script

## 1. Word-For-Word Presentation Script

“This conceptual diagram shows the Vehicle Compliance System as a layered web application inside a system boundary.

On the left are the three user groups: the vehicle owner, the traffic enforcement officer, and the system administrator. Each user interacts with the system through the front end, which is the web interface.

The front end contains the pages and screens for login, registration, password reset, dashboards, reports, and role-specific access. It collects input from the user and displays results back to the user.

The front end communicates with the back end. The back end is where the business logic lives. It handles authentication, email-based login verification, role-based access control, vehicle and compliance management, inspection updates, notification management, report generation, and user management.

Below that is the data access layer. This layer is responsible for database communication using PDO. It performs CRUD operations and validation, and it sits between the business logic and the database.

The algorithms section shows the key processing rules used by the system. For example, passwords are hashed and verified, login and reset tokens are SHA-256 hashed, login verification uses a 6-digit code, daily compliance alerts run at 10:00 AM with retryable email delivery, notifications are de-duplicated by event key, sessions are routed by role, and inspection updates follow a controlled workflow.

On the right is the MySQL database. This stores users, vehicles, compliance records, service records, notifications, login verification tokens, and password reset tokens.

At the bottom are the supporting libraries and frameworks. These include PHP, PDO, PHPMailer, Bootstrap, and Bootstrap Icons. They provide the tools used to build the application, connect to the database, and send emails.

Overall, the diagram shows that the users interact with the front end, the front end talks to the back end, the back end uses the data access layer to communicate with the database, and the libraries support everything underneath.”

## 2. How To Explain The Bi-Directional Arrows

Use these lines:

- “The arrows mean two-way communication.”
- “The user sends input to the front end, and the front end sends output back.”
- “The front end sends requests to the back end, and the back end sends responses back.”
- “The back end requests data from the data access layer, and the data access layer returns results.”
- “The data access layer sends SQL queries to the database, and the database returns records.”

## 3. What Each Relationship Means

- `Vehicle Owner ↔ Front End`: owners submit forms and receive pages or results.
- `Officer ↔ Front End`: officers update inspections and view dashboards.
- `Admin ↔ Front End`: admins manage users and reports.
- `Front End ↔ Back End`: browser requests and server responses.
- `Back End ↔ Data Access Layer`: application logic asks for data or saves data.
- `Data Access Layer ↔ MySQL Database`: SQL queries and returned rows.
- `Back End ↔ Algorithms`: security, token handling, role routing, scheduling, and deduplication.
- `Back End ↔ Libraries/Frameworks`: PHP, PDO, PHPMailer, Bootstrap, and icons support the app.

## 4. Why The Layered Design Was Used

Say this if asked:

- “It separates concerns.”
- “Each layer has one responsibility.”
- “That makes the system easier to maintain and explain.”
- “It also prevents the interface from talking directly to the database.”

## 5. Short Version For Fast Answers

“This diagram shows a layered system. Users interact with the front end. The front end communicates with the back end. The back end contains the business logic and algorithms. The data access layer connects the system to the MySQL database. The bi-directional arrows mean requests go in one direction and responses come back in the other direction.”

