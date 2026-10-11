# UPTM Smart Library System (UPTM-SLS) — V2

A PHP + MySQL / MariaDB Final Year Project prototype designed for VS Code, XAMPP, and phpMyAdmin.

## New in V2

- Student registration
- Fixed partial / hint book search
- Borrow books directly from the book details page
- Choose a borrowing period: 7, 14, or 21 days
- Automatic due-date calculation
- "Days left" / overdue information in My Borrowings
- Return books from the borrowing page
- 1–5 star rating during return
- Optional written feedback during return
- Blue and red visual theme
- Transaction-safe borrow/return updates

## Main functions

- Register
- Login / logout
- Search books by partial title, author, category, or ISBN
- View book details
- Check book availability
- Borrow available books
- View borrowed books and due dates
- Return books
- Leave a star rating and feedback

## IMPORTANT: database update

### If this is a fresh installation

Import:

`database/uptm_sls.sql`

### If you already imported the older project database

Do **not** delete your database. Select the existing `uptm_sls` database in phpMyAdmin and import this file once:

`database/upgrade_v2.sql`

It adds the new `rating` and `feedback` fields required by the return feature.

## Installation

1. Start **Apache** and **MySQL** in XAMPP.
2. Copy the `uptm_sls` folder to:

   `C:\xampp\htdocs\`

3. Open:

   `http://localhost/phpmyadmin/`

4. Import the correct SQL file using the instructions above.
5. Open:

   `http://localhost/uptm_sls/`

## Demo login

- Student ID: `STU001`
- Email: `student1@uptm.edu.my`
- Password: `Student@123`

You can also create a new account using the **Register** page.

## How borrowing works

1. Search for a book.
2. Open its details page.
3. If the book is available, click **Borrow this book**.
4. Choose 7, 14, or 21 days.
5. Confirm the borrowing.
6. The book status becomes `Borrowed`.
7. The due date appears in **My Borrowings**.

## How returning works

1. Open **My Borrowings**.
2. Click **Return** for an active book.
3. Choose a 1–5 star rating.
4. Optionally write feedback.
5. Confirm the return.
6. The book status becomes `Available` again.

## Search fix

The search now uses separate PDO placeholders and token-based partial matching. For example:

- `data` can find `Database System Concepts`
- `computer net` can find `Computer Networks`
- `artificial` can find `Artificial Intelligence: A Modern Approach`

This avoids the prepared-statement placeholder error from the older search query.

## Database structure

Main entities:

- `users`
- `books`
- `borrowings`

`borrowings` now also stores:

- `rating`
- `feedback`

That means the final ERD should eventually be updated to include these two attributes.

## V3 interface update

This version improves the borrowing and return experience:

- Borrowing periods are displayed as aligned selectable cards instead of plain radio buttons.
- Borrow date and return date are displayed together in a clear date summary.
- The return page uses an aligned interactive 1–5 star rating component.
- The blue/red theme is more visible across the header, login/register hero, cards, buttons and status areas.
- CSS and JavaScript files use automatic cache-busting in the header/footer, so browser caching should no longer show the old layout after an update.
