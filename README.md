# Simple News Web App

A full-stack news website built with **PHP and MySQL**, allowing users to create accounts, publish stories, comment on posts, and manage their own content.

## Features

* User registration and secure authentication
* Password hashing and secure account management
* Create, edit, and delete stories
* Comment on published stories
* Edit and delete personal comments
* Attach external links to stories
* MySQL database for users, stories, comments, and links
* CSRF protection
* Server-side input validation
* SQL injection protection using prepared statements
* Input filtering and output escaping
* W3C-compliant HTML/CSS

## Technologies

* **PHP**
* **MySQL**
* **HTML/CSS**
* **Apache**
* **Linux**

## Security

Security was a major focus of the project. The application uses prepared SQL statements to prevent SQL injection, securely hashes user passwords, validates requests on the server, uses CSRF tokens for form submissions, and follows **Filter Input, Escape Output (FIEO)** practices to help prevent XSS and other common web vulnerabilities.

## Project

This project was developed as part of a web development course to practice building database-driven web applications, implementing authentication and authorization, and applying secure web development principles.
