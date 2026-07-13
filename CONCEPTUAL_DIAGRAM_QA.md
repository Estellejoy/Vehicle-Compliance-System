# Vehicle Compliance System Conceptual Diagram Q&A

## 1. What does the diagram show?

**Answer:**
It shows the system structure, the user groups, the application layers, the database, and the technologies that support the system.

## 2. Why is the diagram divided into layers?

**Answer:**
To separate responsibilities. The front end handles the interface, the back end handles business logic, the data access layer handles database communication, and the database stores data.

## 3. What do the bi-directional arrows mean?

**Answer:**
They mean two-way interaction. One side sends a request, and the other side returns a response.

## 4. Why do users connect to the front end first?

**Answer:**
Because the front end is the web interface the user sees and uses to interact with the system.

## 5. Why does the front end not talk directly to the database?

**Answer:**
Because the back end and data access layer should control validation, business rules, and safe database access.

## 6. What is the role of the back end?

**Answer:**
It handles the business logic such as authentication, access control, notifications, reports, and account management.

## 7. What is the role of the data access layer?

**Answer:**
It performs CRUD operations and database communication using PDO.

## 8. What is the role of the algorithms section?

**Answer:**
It shows the important rules and processing logic used by the system, such as password hashing, token hashing, daily compliance alerts, delivery retries, and deduplication, and role-based routing.

## 9. Why is PHPMailer shown in the diagram?

**Answer:**
Because the system uses it to send verification emails, login codes, password reset emails, reminders, and alerts.

## 10. Why is PDO shown in the diagram?

**Answer:**
Because PDO is the database access tool used to connect PHP to MySQL safely.

## 11. What does “role-based session routing” mean?

**Answer:**
After login, the system sends the user to the correct dashboard based on their role, such as owner, officer, or admin.

## 12. What does the “inspection update flow” mean?

**Answer:**
It means when an officer marks a vehicle as inspected, the system updates the record and notifies the owner.

## 13. What does “de-duplication of notifications by event key” mean?

**Answer:**
It means the same reminder is not inserted or sent multiple times for the same vehicle, event type, and expiry date.

## 14. What should you say if they ask why the system boundary exists?

**Answer:**
It separates the system from external actors and shows what is inside the application and what is outside it.

## 15. What is the best short explanation of the whole diagram?

**Answer:**
It is a layered web system where users interact with the front end, the back end processes requests, the data access layer talks to the database, and the libraries support security, email, and presentation.

