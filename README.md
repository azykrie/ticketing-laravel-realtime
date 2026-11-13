# Ticketing Laravel Realtime

A real-time support ticketing system built with Laravel, Livewire, Flux UI, Reverb, and Spatie Permission.

## Features

- Authentication (login, register, logout) with login rate limiting
- Role-based access control: **admin**, **agent**, **user** (Spatie Laravel Permission)
- Ticket management: create, list, filter, search, sort, edit, close, reopen, delete
- Ticket detail with threaded replies
- File attachments (images and documents) on tickets and replies, served through authorized routes
- Real-time updates with Laravel Reverb (new tickets, replies, status changes)
- Real-time notifications (bell icon) via database and broadcast channels
- Dashboard with ticket statistics
- Admin pages: user management and category management
- In-app "How to Use" guide that adapts to the user's role
- Custom error pages (403, 404, 419, 429, 500)

## Tech Stack

- PHP 8.2+ and Laravel
- Livewire (single-file components) and Flux UI Pro
- Laravel Reverb (WebSocket) and Laravel Echo
- Spatie Laravel Permission
- MySQL
- Tailwind CSS and Vite

## Roles

| Role | Permissions |
|---|---|
| user | Create tickets, view and reply to own tickets, edit own title/description, close own ticket |
| agent | View all tickets, reply, change status/priority/category/assignee, close and reopen |
| admin | Everything above, plus delete tickets, manage users and categories |

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL
- A valid **Flux UI Pro** license (this repository does not include Flux Pro)

## Installation

```bash
git clone https://github.com/azykrie/ticketing-laravel-realtime.git
cd ticketing-laravel-realtime

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Activate Flux Pro with your own license:

```bash
php artisan flux:activate
```

Configure the database and Reverb in `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=ticketing
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

REVERB_APP_ID=100001
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Run migrations and seed the default roles and categories:

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=CategorySeeder
```

Make your first account an admin:

```bash
php artisan tinker
>>> App\Models\User::where('email', 'you@example.com')->first()->syncRoles(['admin']);
```

## Running the App

```bash
composer run dev
```

This starts the web server, queue worker, Reverb WebSocket server, and Vite. The queue worker and Reverb must be running for real-time features and notifications to work.

## Project Structure

```
app/
  Events/            TicketReplied, TicketChanged (broadcast events)
  Models/            Ticket, TicketReply, Category, Attachment, User
  Notifications/     NewTicket, TicketReply, TicketUpdated
  Policies/          TicketPolicy
  Support/           StoreAttachments helper
resources/views/
  layouts/           app (sidebar) and auth layouts
  pages/             Livewire pages: auth, dashboard, tickets, users, categories, help
  components/        attachment-list, error-page
routes/
  web.php            Application routes
  channels.php       Broadcast channel authorization
```

## Security Notes

- Attachments are stored on a private disk and downloaded through an authorized route.
- Access rules are enforced in routes, policies, and Livewire component methods, not only in the UI.
- Login attempts are rate limited.
- Change default credentials and set `APP_DEBUG=false` before deploying.

## Production Deployment (outline)

- Nginx + PHP-FPM + MySQL
- Supervisor for `php artisan reverb:start` and `php artisan queue:work`
- SSL, with `REVERB_SCHEME=https` for `wss://`
- `php artisan optimize` and `npm run build`

## License

Add your license here. Note that Flux UI Pro is a commercial package and is not covered by this license.