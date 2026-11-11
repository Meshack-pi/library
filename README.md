# Library Management System

A web application that replaces a manual library (paper registers and stamp cards) with software that tracks books, members and loans. It lets a library answer three questions at any moment:

- What books do we have?
- Who has which book?
- Who owes what?

Built with Laravel 13, Livewire 4, Tailwind CSS 4 and [spatie/laravel-permission](https://spatie.be/docs/laravel-permission).

## Contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Demo accounts](#demo-accounts)
- [Roles and permissions](#roles-and-permissions)
- [Library rules](#library-rules)
- [How lending works](#how-lending-works)
- [Screens](#screens)
- [Database](#database)
- [Reminders and scheduled tasks](#reminders-and-scheduled-tasks)
- [Project structure](#project-structure)
- [Testing](#testing)
- [Known limitations](#known-limitations)

## Features

### Member (student or staff)

- Register and log in with an email address or admission number.
- Search the catalogue by title, author or ISBN, and filter by category or by "available now".
- View a book's details: ISBN, category, shelf location, copies available and how many members are waiting.
- Reserve a book when every copy is on loan or held.
- See current loans with due dates and overdue warnings.
- Renew a loan, as long as it is not overdue, nobody has reserved the book, and the renewal limit has not been reached.
- Cancel a reservation.
- See unpaid fines and the fine building up on overdue books still held.
- Read notifications on the dashboard (due soon, overdue, reserved book ready).

### Librarian

- Desk dashboard with books on loan, returns due today, the overdue list, copies held for collection and total unpaid fines.
- Issue a book by typing the member's admission number or email and the book's ISBN or ID. The screen previews the member's loan count and the book's availability before confirming.
- Return a book by searching for the member or the book. Any fine is shown before the return and created automatically after it.
- Add, edit and remove books, and add categories.
- Register and update member accounts.
- View and cancel reservations, and jump straight to issuing a held copy.
- Record fine payments made at the desk.

### Admin

- Everything a librarian can do.
- Add librarian accounts, update them, and remove librarian access.
- Set the library rules (loan period, fine per day, borrowing limit, renewal limit, reservation hold period).
- View reports: most borrowed books, loans issued per month, overdue books, fines collected, fines unpaid and fines still building up.

### Automatic

- Calculates fines from the number of days late and the daily rate.
- Holds a returned copy for the member who has waited longest, and notifies them.
- Sends a reminder the day before a book is due.
- Sends an overdue reminder on the first day late and then once a week.
- Releases held copies that were not collected in time and passes them to the next member waiting.

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 (PHP 8.3 or newer) |
| UI | Livewire 4 class-based components with Blade views |
| Styling | Tailwind CSS 4, built with Vite |
| Roles and permissions | spatie/laravel-permission 8 |
| Database | SQLite by default; any database Laravel supports works |
| Notifications | Laravel notifications over the `database` and `mail` channels |
| Tests | PHPUnit with Livewire's testing helpers |

## Getting started

### Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm

### Install

```bash
git clone git@github.com:Meshack-pi/library.git
cd library

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

npm install
npm run build
```

`php artisan migrate --seed` creates the tables, the three roles with their permissions, 22 sample books and the demo accounts below.

### Run

```bash
composer run dev
```

Then open <http://localhost:8000>. This starts the web server and the Vite dev server together.

To run only the web server against the built assets:

```bash
php artisan serve
```

### Reset the data

```bash
php artisan migrate:fresh --seed
```

## Demo accounts

All demo accounts use the password `password`.

| Role | Log in with |
|---|---|
| Admin | `admin@library.test` |
| Librarian | `librarian@library.test` |
| Member | `member@library.test` or admission number `ADM001` |

Change or remove these accounts before using the system with real data.

## Roles and permissions

Each role lands on its own home page after logging in: members on **My library**, librarians on the **Desk**, admins on **Reports**.

| Permission | Member | Librarian | Admin |
|---|:-:|:-:|:-:|
| `catalogue.view` | ✓ | ✓ | ✓ |
| `reservations.create` | ✓ | ✓ | ✓ |
| `loans.renew-own` | ✓ | ✓ | ✓ |
| `fines.view-own` | ✓ | ✓ | ✓ |
| `books.manage` | | ✓ | ✓ |
| `loans.issue` | | ✓ | ✓ |
| `loans.return` | | ✓ | ✓ |
| `reservations.manage` | | ✓ | ✓ |
| `members.manage` | | ✓ | ✓ |
| `fines.record-payment` | | ✓ | ✓ |
| `librarians.manage` | | | ✓ |
| `settings.manage` | | | ✓ |
| `reports.view` | | | ✓ |

How access is enforced:

- Roles and their permissions are defined in one place, `app/Enums/Role.php`, and written to the database by `RolesAndPermissionsSeeder`.
- Every route is protected by `role:` or `permission:` middleware in `routes/web.php`.
- Every staff and admin screen checks its permission again with `Gate::authorize()` each time it renders and inside each action, because a Livewire action can be called without visiting the page's route.
- Member actions look records up through the logged-in user (for example `$member->activeLoans()->findOrFail($id)`), so one member cannot renew or cancel another member's records.
- Self-registration always creates a member. Librarians are only created by an admin, and librarians can only edit member accounts.

After changing the permissions in `app/Enums/Role.php`, apply them with:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## Library rules

An admin sets these on the **Rules** page. They are stored in the `settings` table and take effect on the next loan, return or renewal.

| Rule | Key | Default |
|---|---|---|
| Loan period | `loan_days` | 14 days |
| Fine per day late | `fine_per_day` | KES 10 |
| Maximum books per member | `max_books` | 3 |
| Maximum renewals per loan | `max_renewals` | 2 |
| Reservation hold period | `hold_days` | 3 days |

## How lending works

All lending rules live in `app/Services/LoanService.php`. When a rule blocks an action, the service throws a `LoanException` whose message is shown to the person at the screen.

### Availability

Copies are tracked as a count per book, not as individual copies:

```
available copies = total copies − copies on loan − copies held for reservations
```

The figure is calculated from the loans and reservations each time, never stored, so it cannot drift out of step.

### Issuing a book

A loan is refused when:

- the member already has the maximum number of books,
- the member already has a copy of the same book, or
- no copy is available. A copy held for a reservation can only be issued to the member it is held for.

Otherwise the loan is created with a due date of today plus the loan period, and the member's reservation for that book, if any, is marked as collected.

### Returning a book

The return date is recorded. If the book is late, a fine is created:

```
fine = whole days past the due date × fine per day
```

The calculation is in `daysOverdue()` and `accruedFine()` in `app/Models/Loan.php`. A book returned on its due date carries no fine.

If members are waiting for the book, the returned copy is held for the one who reserved first, and that member is notified.

### Renewing a loan

A renewal extends the due date by one loan period, counted from the current due date. It is refused when the loan is overdue, when another member is waiting for the book, or when the loan has reached the renewal limit.

### Reserving a book

A member can reserve a book only when no copy is available, and can hold one active reservation per book. A reservation moves through these states:

| Status | Meaning |
|---|---|
| `pending` | Waiting for a copy to come back |
| `ready` | A copy is held on the shelf for the member |
| `fulfilled` | The member collected the book |
| `cancelled` | Cancelled by the member or a librarian |
| `expired` | The held copy was not collected in time |

When a `ready` reservation is cancelled or expires, the copy passes to the next member waiting.

### Fines

A fine is created when a late book is returned. The amount owed on a book that is still out is calculated live and shown as "so far". Fines are paid at the desk, where the librarian records the payment; the system keeps who received it and when.

### Removing records

- Removing a book hides it from the catalogue but keeps its loan history (soft delete). It is blocked while copies are on loan, and it cancels the book's open reservations.
- Removing a librarian's access turns the account into an ordinary member, so the history of loans they issued is kept.

## Screens

| URL | Screen | Who |
|---|---|---|
| `/login`, `/register` | Log in, create a member account | Guests |
| `/dashboard` | Redirects to the home page for the user's role | Everyone |
| `/member` | My library: loans, reservations, fines, notifications | Member |
| `/catalogue` | Search and filter the catalogue, reserve | Everyone |
| `/catalogue/{book}` | Book details and availability | Everyone |
| `/librarian` | Desk dashboard | Librarian, Admin |
| `/librarian/circulation` | Issue and return books | Librarian, Admin |
| `/librarian/books` | Book management | Librarian, Admin |
| `/librarian/members` | Member management | Librarian, Admin |
| `/librarian/reservations` | Reservation queue | Librarian, Admin |
| `/librarian/fines` | Fines and payments | Librarian, Admin |
| `/admin/reports` | Reports | Admin |
| `/admin/librarians` | Librarian accounts | Admin |
| `/admin/settings` | Library rules | Admin |

## Database

| Table | Purpose | Key columns |
|---|---|---|
| `users` | Members, librarians and admins | `name`, `email`, `admission_number`, `password` |
| `categories` | Book categories | `name` |
| `books` | The catalogue | `title`, `author`, `isbn`, `category_id`, `shelf_location`, `total_copies`, `deleted_at` |
| `loans` | Every check-out | `user_id`, `book_id`, `issued_by`, `issued_at`, `due_at`, `returned_at`, `renewals_count` |
| `reservations` | The waiting queue | `user_id`, `book_id`, `status`, `ready_at`, `expires_at` |
| `fines` | Late-return charges | `loan_id`, `user_id`, `amount`, `paid_at`, `received_by` |
| `settings` | Library rules | `key`, `value` |
| `notifications` | In-app notifications | Laravel's standard table |
| `roles`, `permissions` and pivots | Access control | Managed by spatie/laravel-permission |

Relationships:

- A book belongs to one category and has many loans and reservations.
- A loan belongs to a member, a book and the librarian who issued it, and has at most one fine.
- A fine belongs to a loan and a member, and records the librarian who received payment.
- Amounts are whole Kenyan shillings.

## Reminders and scheduled tasks

One command does the daily housekeeping:

```bash
php artisan library:daily
```

It releases held copies that were not collected, sends due-tomorrow reminders, and sends overdue reminders on the first day late and every seventh day after that. It is scheduled for 07:00 each day in `routes/console.php`.

The schedule only fires while a scheduler is running. During development:

```bash
php artisan schedule:work
```

On a server, add the standard Laravel cron entry:

```
* * * * * cd /path/to/library && php artisan schedule:run >> /dev/null 2>&1
```

Notifications are stored for the member's dashboard and also sent by email. With the default `MAIL_MAILER=log`, emails are written to `storage/logs/laravel.log` instead of being delivered. Set the `MAIL_*` values in `.env` to send real email.

## Project structure

```
app/
  Console/Commands/RunDailyLibraryTasks.php   The library:daily command
  Enums/Role.php                              Roles, their permissions and home pages
  Enums/ReservationStatus.php                 Reservation states
  Exceptions/LoanException.php                Raised when a library rule blocks an action
  Livewire/
    Auth/                                     Login, Register
    Catalogue/                                Index (search), Show (details)
    Member/Dashboard.php                      My library
    Librarian/                                Dashboard, IssueReturn, Books, Members, Reservations, Fines
    Admin/                                    Reports, Librarians, Settings
    Concerns/Flashes.php                      Success and error messages shared by the screens
  Models/                                     User, Book, Category, Loan, Reservation, Fine, Setting
  Notifications/                              LoanDueSoon, LoanOverdue, ReservationReady
  Services/LoanService.php                    Issue, return, renew, reserve, cancel, fine payment
database/
  migrations/                                 Table definitions
  factories/                                  Test data builders
  seeders/                                    Roles and permissions, sample catalogue, demo accounts
resources/views/
  layouts/                                    app (signed in) and guest (login, register)
  components/                                 flash message, navigation link
  livewire/                                   One Blade view per Livewire component
routes/
  web.php                                     Pages and their access rules
  console.php                                 The daily schedule
tests/Feature/                                Feature tests
```

## Testing

```bash
php artisan test
```

The suite uses an in-memory SQLite database and covers:

| Test file | What it checks |
|---|---|
| `AuthenticationTest` | Login by email and admission number, wrong password, registration, logout |
| `RoleAccessTest` | Each role is sent to its own home page |
| `PagesTest` | Every page opens only for the roles allowed to use it |
| `LoanServiceTest` | Borrowing limit, availability, held copies, late fines, renewals, the reservation queue, hold expiry, fine payment |
| `ScreenActionsTest` | The actions on each screen: search, reserve, book and member management, issue, return, renew, rules, librarian accounts |
| `DailyLibraryTasksTest` | Due and overdue reminders and expiry of uncollected reservations |

Format the code before committing:

```bash
vendor/bin/pint
```

## Known limitations

- **No online payment.** Members see what they owe and pay at the desk. There is no M-Pesa or card integration.
- **No per-copy barcodes.** A book is issued by its ISBN or ID, and copies are a count. Tracking individual copies would need a `book_copies` table.
- **No password reset or email verification.** A librarian can set a new password for a member, and an admin for a librarian.
- **Member accounts cannot be deleted** from the interface, so loan and fine history is never lost.
- **Whole-day fines in one currency.** Fines are whole Kenyan shillings per whole day late.
