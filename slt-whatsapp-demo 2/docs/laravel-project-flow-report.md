# SLT WhatsApp Agent Laravel Project Flow Report

Audience: PHP developer who is new to Laravel

Scope: this report focuses on the custom project code. Default Laravel/Breeze files are only mentioned when they directly affect the custom flow.

## 1. Laravel first: what Laravel is doing in this project

Laravel is still plain PHP, but it organizes the app into a predictable request lifecycle:

1. A request enters through `public/index.php`.
2. Laravel boots the app using `bootstrap/app.php`.
3. Laravel matches the URL to a route in `routes/web.php`.
4. Middleware runs first. In this project, most custom routes require `auth` and `verified`.
5. The route calls a controller method.
6. The controller talks to models and services.
7. The controller returns either:
   - a Blade view for HTML pages, or
   - JSON for the JavaScript chat UI.
8. Blade renders the server-side HTML. JavaScript inside Blade then keeps polling JSON endpoints.

For command-line tasks, the flow is similar:

1. A CLI command enters through `artisan`.
2. Laravel boots the app using `bootstrap/app.php`.
3. The command is defined in `app/Console/Commands/...`.
4. The command uses models and services.
5. Output goes back to the terminal.

That means Laravel is not replacing PHP logic. It is structuring where each piece of logic belongs.

## 2. MVC in this project

Laravel uses MVC, but this app is really closer to "Route -> Controller -> Service/Model -> View or JSON".

### Model

Models represent database tables and relationships.

Custom models in this project:

- `app/Models/Contact.php`
- `app/Models/Message.php`
- `app/Models/ApiLog.php`
- `app/Models/User.php`

### View

Views are Blade templates in `resources/views`.

Main custom views:

- `resources/views/chats/index.blade.php`
- `resources/views/chats/show.blade.php`
- `resources/views/chats/partials/list-items.blade.php`
- `resources/views/logs/index.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`

### Controller

Controllers receive the request and coordinate the work.

Custom controllers:

- `app/Http/Controllers/ChatController.php`
- `app/Http/Controllers/ChatApiController.php`
- `app/Http/Controllers/ChatLockController.php`
- `app/Http/Controllers/ContactController.php`
- `app/Http/Controllers/LogController.php`

### Important project-specific note

This project does **not** currently behave like a classic "store every inbound message in DB" chat app.

Current behavior:

- inbound messages are fetched live from the SLT API
- outbound replies are stored locally in the `messages` table
- recent contacts are stored locally in the `contacts` table
- API requests and responses are stored locally in the `api_logs` table

There is an older command, `WhatsappSync`, that can store inbound messages into the database, but the scheduler currently does not use it and the web UI reads inbound messages from the API, not from the local `messages` table.

## 3. Custom file architecture of this project

```text
app/
  Console/Commands/
    WhatsappSync.php
    WhatsappSyncContacts.php
  Http/Controllers/
    ChatController.php
    ChatApiController.php
    ChatLockController.php
    ContactController.php
    LogController.php
  Models/
    Contact.php
    Message.php
    ApiLog.php
    User.php
  Providers/
    AppServiceProvider.php
  Services/
    SltWhatsappClient.php

bootstrap/
  app.php
  providers.php

config/
  app.php
  chat.php
  database.php
  logging.php
  queue.php
  services.php
  session.php

database/
  migrations/
  seeders/

public/
  index.php
  build/
  images/

resources/views/
  chats/
  layouts/
  logs/

routes/
  web.php
  console.php
  auth.php

.env.example
```

## 4. End-to-end flow in this app

## 4.1 Browser flow: open chat list

1. User visits `/chats`.
2. Route in `routes/web.php` maps `/chats` to `ChatController@index`.
3. `ChatController@index` loads contacts from the `contacts` table.
4. Controller returns `resources/views/chats/index.blade.php`.
5. Blade renders the page.
6. JavaScript in the Blade file keeps refreshing the contact list and periodically triggers recent-contact sync.

## 4.2 Browser flow: open one conversation

1. User clicks one contact.
2. Route `/chats/{contact}` maps to `ChatController@show`.
3. Laravel automatically resolves `{contact}` to a `Contact` model using route model binding.
4. Controller returns `resources/views/chats/show.blade.php`.
5. The page loads and Alpine.js starts `chatApp(contactId)`.
6. JavaScript then calls JSON endpoints:
   - `GET /chats/{contact}/lock`
   - `GET /chats/{contact}/messages`
   - `POST /chats/{contact}/read`
   - later, `POST /chats/{contact}/send`

## 4.3 Browser flow: load messages

1. `resources/views/chats/show.blade.php` calls `GET /chats/{contact}/messages`.
2. Route maps to `ChatApiController@messages`.
3. Controller asks `SltWhatsappClient` for messages from SLT API.
4. Controller normalizes the API payload into a UI-friendly structure.
5. Controller also loads local outgoing messages from the `messages` table.
6. Controller merges API messages and local outgoing messages.
7. Controller returns JSON.
8. Alpine.js renders chat bubbles.

## 4.4 Browser flow: send a reply

1. User types a message and clicks Send.
2. JavaScript posts to `POST /chats/{contact}/send`.
3. Route maps to `ChatApiController@send`.
4. Controller acquires or refreshes the chat lock inside a DB transaction.
5. Controller validates the incoming text.
6. Controller asks `SltWhatsappClient` for recent inbound messages to find the latest inbound UUID.
7. Controller sends the reply to SLT API using that UUID.
8. If SLT accepts it, controller stores an outbound row in `messages`.
9. Controller returns JSON `{ ok: true }`.
10. Frontend reloads messages and chat list.

## 4.5 Scheduler flow: sync recent contacts

1. `php artisan schedule:work` runs the Laravel scheduler.
2. `routes/console.php` schedules `whatsapp:sync-contacts` every minute.
3. `app/Console/Commands/WhatsappSyncContacts.php` runs.
4. Command asks `SltWhatsappClient` for recent active mobile numbers.
5. Command upserts contacts into the `contacts` table.
6. Chat sidebar now shows new or recently active contacts.

## 4.6 Admin logs flow

1. User visits `/logs`.
2. Route maps to `LogController@index`.
3. Controller checks `auth()->user()->is_admin`.
4. If tab is `api`, controller reads from the `api_logs` table.
5. If tab is `errors`, controller reads Laravel log files from `storage/logs`.
6. Controller returns `resources/views/logs/index.blade.php`.

## 5. Routes: what happens here and how to create them

Main custom route file: `routes/web.php`

What this file does here:

- defines page routes for chats and logs
- defines JSON endpoints for polling, send, read, sync, and lock handling
- groups most custom routes under `auth` and `verified`
- relies on route model binding for `{contact}`

### Custom routes in this project

Page routes:

- `GET /chats` -> `ChatController@index`
- `GET /chats/list` -> `ChatController@list`
- `GET /chats/{contact}` -> `ChatController@show`
- `GET /logs` -> `LogController@index`

Contact routes:

- `POST /contacts` -> `ContactController@store`
- `PUT /contacts/{contact}` -> `ContactController@update`
- `POST /contacts/sync-recent` -> `ContactController@syncRecent`

Chat API routes:

- `GET /chats/{contact}/messages` -> `ChatApiController@messages`
- `POST /chats/{contact}/send` -> `ChatApiController@send`
- `POST /chats/{contact}/sync` -> `ChatApiController@sync`
- `POST /chats/{contact}/read` -> `ChatApiController@markRead`
- `POST /chats/sync-all` -> `ChatApiController@syncAll`

Lock routes:

- `GET /chats/{contact}/lock` -> `ChatLockController@status`
- `POST /chats/{contact}/lock/acquire` -> `ChatLockController@acquire`
- `POST /chats/{contact}/lock/release` -> `ChatLockController@release`

### How to create a new route in Laravel

Example:

```php
use App\Http\Controllers\ReportController;

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
```

If you need a controller:

```bash
php artisan make:controller ReportController
```

### Important Laravel concept used here: route model binding

In:

```php
Route::get('/chats/{contact}', [ChatController::class, 'show']);
```

Laravel sees `{contact}` and automatically injects `App\Models\Contact` into:

```php
public function show(Contact $contact)
```

That saves you from writing manual lookup code like:

```php
$contact = Contact::findOrFail($id);
```

## 6. Controllers: what happens in this project and how to create them

Controllers in Laravel are the traffic managers. They should not contain low-level infrastructure details if that logic can live in models or services.

Create one with:

```bash
php artisan make:controller ChatController
```

### 6.1 `ChatController`

Purpose:

- returns HTML pages for chat list and chat detail
- renders the contact sidebar partial for AJAX refreshes

Methods:

#### `index()`

Flow:

1. Read sidebar limit from `config('chat.list_limit')`.
2. Load contacts ordered by latest `updated_at`.
3. Return `chats.index`.

#### `show(Contact $contact)`

Flow:

1. Resolve the selected contact using route model binding.
2. Load the sidebar contact list.
3. Return `chats.show` with both the selected contact and the list.

#### `list(Request $request)`

Flow:

1. Read an optional `limit` query parameter.
2. Load contacts for sidebar refresh.
3. Read UI flags such as `show_lock`, `show_active`, and `show_preview`.
4. Return the Blade partial `chats.partials.list-items`.

Why this controller exists:

- page rendering and partial rendering are still server-side
- the page itself contains JavaScript, but the HTML shell comes from Blade

### 6.2 `ChatApiController`

Purpose:

- powers the chat UI JSON endpoints
- fetches live messages from SLT
- sends replies
- marks conversations as read

Methods:

#### `messages(Contact $contact, SltWhatsappClient $client)`

Flow:

1. Ask SLT API for recent messages using the contact mobile number.
2. Normalize various possible API response shapes.
3. Split combined API rows into two bubbles when needed.
4. Load locally stored outgoing rows from `messages`.
5. Merge live API data and local DB replies.
6. Sort the merged list.
7. Return JSON.

This is the most important project-specific runtime path.

#### `send(Contact $contact, Request $request, SltWhatsappClient $client)`

Flow:

1. Open a transaction and lock the selected contact row using `lockForUpdate()`.
2. If the lock is stale, clear it.
3. If another admin owns the lock, stop with HTTP 423.
4. If lock is free or owned by the current admin, refresh it.
5. Validate the new message text.
6. Fetch recent inbound messages and find the latest inbound UUID.
7. Call SLT `reply.php`.
8. Detect payload-level errors from the SLT response.
9. Save the outgoing message locally using `updateOrCreate`.
10. Return success JSON.

#### `markRead(Contact $contact)`

Flow:

1. Update `contacts.last_read_at`.
2. Return JSON.

#### `sync(Contact $contact)` and `syncAll(Request $request)`

These are compatibility endpoints for the frontend. They no longer perform DB message sync. They return success so the current UI can still call them.

### 6.3 `ChatLockController`

Purpose:

- handles multi-admin chat locking

Methods:

#### `status(Contact $contact)`

Flow:

1. Load the contact inside a transaction.
2. Clear stale lock if needed.
3. Return a lock payload to the frontend.

#### `acquire(Contact $contact)`

Flow:

1. Lock the contact row with `lockForUpdate()`.
2. Clear stale lock if needed.
3. If another admin owns it, return HTTP 423.
4. Otherwise assign current user as lock owner.
5. Return JSON lock state.

#### `release(Contact $contact)`

Flow:

1. Lock the row in a transaction.
2. Clear stale lock if needed.
3. If nobody owns it, return already released.
4. If another admin owns it, reject with HTTP 423.
5. If current admin owns it, clear `locked_by_user_id` and `locked_at`.
6. Return JSON.

Why lock logic is in its own controller:

- it keeps send logic smaller
- the frontend can poll lock state independently from messages

### 6.4 `ContactController`

Purpose:

- creates or updates contacts
- triggers recent-contact sync

Methods:

#### `store(Request $request)`

Flow:

1. Validate `name` and `mobile`.
2. Strip non-digits.
3. Normalize `07...` to `94...`.
4. Use `Contact::updateOrCreate`.
5. Redirect back to chats with a flash message.

#### `update(Request $request, Contact $contact)`

Flow:

1. Validate `name`.
2. Update contact name.
3. Redirect back to the selected chat.

#### `syncRecent(Request $request)`

Flow:

1. Read sync limit from request or config.
2. Enforce a max limit.
3. Call the artisan command `whatsapp:sync-contacts`.
4. Inspect command output to detect "skipped" state.
5. Redirect back with a success or error flash message.

### 6.5 `LogController`

Purpose:

- shows API log entries from DB
- shows application error log entries from `storage/logs`

Flow:

1. Only admins are allowed.
2. Decide which tab is active: `api` or `errors`.
3. If `api`, paginate `ApiLog` rows and decorate them for display.
4. If `errors`, read log files, parse entries, group repeated problems, and mark them as active or fixed.
5. Return `logs.index`.

This controller is heavier than the others because it contains display-side analysis logic, not just routing logic.

## 7. Models: what happens in this project and how to create them

Create one with:

```bash
php artisan make:model Contact
```

If you want model + migration together:

```bash
php artisan make:model Contact -m
```

### 7.1 `Contact`

Represents: `contacts` table

Key fields used by this project:

- `name`
- `mobile`
- `last_synced_at`
- `last_read_at`
- `locked_by_user_id`
- `locked_at`

Relationships:

- `messages()` -> has many `Message`
- `lastMessage()` -> latest message by `sent_at`
- `lockedBy()` -> belongs to `User`

Important custom method:

- `isLockExpired()`

What it does:

1. Reads lock TTL from `config('chat.lock_ttl_seconds')`.
2. Compares `locked_at` with `now()`.
3. Returns true if lock is stale.

Where it is used:

- `ChatApiController`
- `ChatLockController`

### 7.2 `Message`

Represents: `messages` table

Key fields:

- `contact_id`
- `uuid`
- `direction`
- `body`
- `sent_at`
- `raw`

Relationship:

- `contact()` -> belongs to `Contact`

Important project behavior:

- currently used mainly for local outgoing messages
- not the primary source of inbound chat history in the current UI

### 7.3 `ApiLog`

Represents: `api_logs` table

Used for:

- storing SLT API call metadata
- request payload snapshots
- response payload snapshots
- duration and status monitoring

This model is written by `SltWhatsappClient` and read by `LogController`.

### 7.4 `User`

Mostly default Laravel auth model, but one custom field matters:

- `is_admin`

Used for:

- log access control
- lock ownership relationships through `Contact::lockedBy()`

## 8. Services: what happens in this project and how to create them

Laravel has no default `make:service` command. Services are usually plain PHP classes placed under `app/Services`.

Create one manually:

```bash
mkdir -p app/Services
```

Then create a file such as:

```php
namespace App\Services;

class ReportService
{
    public function run(): array
    {
        return [];
    }
}
```

### `SltWhatsappClient`

Purpose:

- centralizes all communication with the SLT WhatsApp API
- keeps controller code cleaner

Configuration source:

- `config/services.php`
- environment variables from `.env`

Constructor flow:

1. Read `config('services.slt_whatsapp')`.
2. Store base URL, username, password, phone number ID, and optional bearer token.

Main methods:

#### `getToken()`

Flow:

1. If `.env` already contains a bearer token, use it.
2. Otherwise use Laravel cache to remember a generated token for 50 minutes.
3. If missing, call `login()`.

#### `login()`

Flow:

1. POST to `/login.php`.
2. Measure duration.
3. Parse token from response.
4. Write one row to `api_logs`.
5. Throw an exception if login failed.

#### `getMessages(string $mobile, int $limit = 20)`

Flow:

1. Call authenticated POST `/getMessages.php`.
2. Return parsed JSON.

#### `getRecentActiveMobiles(int $take = 5)`

Flow:

1. Call authenticated GET `/getRecentActiveMobiles.php`.
2. Support multiple possible response shapes.
3. Extract mobile numbers.
4. Normalize `07...` to `94...`.
5. Deduplicate and slice to the required amount.

#### `reply(string $uuid, string $mobile, string $replyMessage)`

Flow:

1. Call authenticated POST `/reply.php`.
2. Include:
   - inbound message UUID
   - phone number ID
   - mobile
   - reply message

#### `authedPost()` and `authedGet()`

Flow:

1. Get bearer token.
2. Make HTTP request.
3. If HTTP 401 happens, clear cached token and retry once.
4. Measure duration.
5. Save request/response to `api_logs`.
6. Throw exception on non-success HTTP status.

Why the service layer matters here:

- controllers should not know SLT endpoint details
- API logging is centralized
- token refresh logic stays in one place

## 9. Config files: what happens in this project and how to create them

Custom config files are plain PHP arrays under `config/`.

Create one manually, for example:

```php
// config/chat.php
return [
    'lock_ttl_seconds' => (int) env('CHAT_LOCK_TTL_SECONDS', 1800),
];
```

Then read it anywhere with:

```php
config('chat.lock_ttl_seconds')
```

### 9.1 `config/chat.php`

This is the main custom config file.

It controls:

- `lock_ttl_seconds`
- `list_limit`
- `sync_recent_limit`
- `sync_recent_max_limit`
- `error_active_window_minutes`

Where it is used:

- `ChatController`
- `ContactController`
- `ChatLockController`
- `Contact` model
- `LogController`
- Blade views
- `routes/console.php`

### 9.2 `config/services.php`

This project adds:

- `services.slt_whatsapp.base`
- `services.slt_whatsapp.username`
- `services.slt_whatsapp.password`
- `services.slt_whatsapp.phone_number_id`
- `services.slt_whatsapp.bearer_token`

Where it is used:

- `SltWhatsappClient`

### 9.3 `config/app.php`

Project-relevant custom behavior:

- timezone is read from `APP_TIMEZONE`
- default fallback used here is `Asia/Colombo`

This matters because:

- lock timestamps
- message timestamps
- logs page display times

### 9.4 `config/database.php`

Important because this app supports:

- SQLite
- MySQL
- PostgreSQL

All models use this config indirectly. When `.env` changes `DB_CONNECTION`, Eloquent automatically uses that database connection.

### 9.5 `config/session.php`

Relevant here because `.env` currently points sessions to the database:

- `SESSION_DRIVER=database`

That is why the default `sessions` table migration matters for this project.

### 9.6 `config/cache.php`

Relevant because:

- cached SLT token uses Laravel cache
- `.env` currently uses `CACHE_STORE=database`

That is why the default `cache` and `cache_locks` tables matter.

### 9.7 `config/queue.php`

Relevant because `.env` currently uses:

- `QUEUE_CONNECTION=database`

The current custom chat flow does not use queues directly, but Laravel is configured for them.

### 9.8 `config/logging.php`

Relevant because:

- Laravel application errors go to `storage/logs/laravel.log`
- `LogController` reads those files for the Error Logs tab

## 10. `.env`: what happens in this project and how its values flow

Create `.env` from template:

```bash
cp .env.example .env
php artisan key:generate
```

Flow of `.env` in Laravel:

1. `.env` stores deployment-specific values.
2. Config files read them through `env(...)`.
3. App code reads config through `config(...)`.
4. Controllers, services, models, and views use those config values.

Example in this project:

1. `.env` contains `CHAT_LOCK_TTL_SECONDS=1800`
2. `config/chat.php` reads it
3. `Contact::isLockExpired()` uses `config('chat.lock_ttl_seconds')`
4. `ChatLockController` and `ChatApiController` use that behavior

### Important `.env` keys for this app

App:

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`
- `APP_TIMEZONE`

Database:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Chat behavior:

- `CHAT_LOCK_TTL_SECONDS`
- `CHAT_LIST_LIMIT`
- `CHAT_SYNC_RECENT_LIMIT`
- `CHAT_SYNC_RECENT_MAX_LIMIT`
- `CHAT_ERROR_ACTIVE_WINDOW_MINUTES`

SLT API:

- `SLT_API_BASE`
- `SLT_API_USERNAME`
- `SLT_API_PASSWORD`
- `SLT_PHONE_NUMBER_ID`
- optional `SLT_API_BEARER_TOKEN`

Infrastructure:

- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`
- `LOG_CHANNEL`

## 11. Migrations: what happens in this project and how to create them

Migrations define database structure.

Create one with:

```bash
php artisan make:migration create_contacts_table --create=contacts
```

Laravel then generates a timestamped file in `database/migrations`.

### Project custom migrations

#### `create_contacts_table`

Creates:

- `id`
- `name`
- `mobile` unique
- `last_synced_at`
- timestamps

Why it exists:

- stores the chat sidebar's known contacts

#### `create_api_logs_table`

Creates:

- `user_id`
- `service`
- `endpoint`
- `method`
- `status`
- `duration_ms`
- `request`
- `response`
- timestamps

Why it exists:

- audit and troubleshooting for SLT API calls

#### `create_messages_table`

Creates:

- `contact_id`
- `uuid`
- `direction`
- `body`
- `sent_at`
- `raw`
- unique pair: `contact_id + uuid`

Why it exists:

- store outgoing replies locally
- optionally support inbound sync if `WhatsappSync` is used

#### `add_is_admin_to_users_table`

Adds:

- `is_admin`

Why it exists:

- simple admin authorization

#### `add_chat_lock_to_contacts_table`

Adds:

- `locked_by_user_id`
- `locked_at`

Why it exists:

- multi-admin lock ownership per contact

#### `add_last_read_at_to_contacts_table`

Adds:

- `last_read_at`

Why it exists:

- remember when a contact conversation was last read

### Default migrations that still matter

These are default, but they are operationally important because current `.env` uses them:

- `create_users_table`
- `create_cache_table`
- `create_jobs_table`

## 12. Seeders: what happens in this project and how to create them

Create one with:

```bash
php artisan make:seeder AdminSeeder
```

### `DatabaseSeeder`

This is the master entry point. Laravel calls it during:

```bash
php artisan migrate --seed
```

In this project it calls:

- `AdminSeeder`

### `AdminSeeder`

Purpose:

- creates the default admin users

Flow:

1. Define four admin names and emails.
2. Loop through them.
3. Use `User::updateOrCreate(...)`.
4. Set password, `is_admin`, verified email, and remember token.

Why `updateOrCreate` is good here:

- re-running seeders does not create duplicates

## 13. CRUD flow in this project

This app is not a full CRUD app for every table. Some tables are write-only logs, some are read-heavy, and some are partly managed by API sync.

### 13.1 Contacts CRUD

Create:

- manual create through `ContactController@store`
- auto create through `WhatsappSyncContacts`

Read:

- `ChatController@index`
- `ChatController@show`
- `ChatController@list`

Update:

- `ContactController@update`
- contact `touch()` during sync
- lock fields updated by `ChatLockController` and `ChatApiController`
- `last_read_at` updated by `ChatApiController@markRead`

Delete:

- no custom delete route currently

### 13.2 Messages CRUD

Create:

- `ChatApiController@send` stores outgoing rows
- optional legacy `WhatsappSync` stores inbound/outbound rows if used manually

Read:

- `ChatApiController@messages` reads local outgoing rows

Update:

- `updateOrCreate` in `storeLocalOutgoingMessage`

Delete:

- no direct delete route
- deleted automatically if the parent contact is deleted because of cascade

### 13.3 API logs CRUD

Create:

- every SLT request in `SltWhatsappClient`

Read:

- `LogController@index`

Update:

- not used

Delete:

- no custom delete flow

### 13.4 Users CRUD

Create:

- `AdminSeeder`
- manual tinker creation

Read:

- Laravel auth
- navigation UI
- log authorization
- lock ownership relationship

Update:

- profile update
- admin flag can be updated manually or by seeder rerun

Delete:

- profile delete flow

## 14. Blade files: what happens in this project and how to create them

Blade is Laravel's server-side templating engine.

Create a Blade file manually:

```text
resources/views/chats/index.blade.php
```

Return it from a controller like:

```php
return view('chats.index', compact('contacts'));
```

### 14.1 `layouts/app.blade.php`

Purpose:

- global shell for authenticated pages
- loads Vite assets
- includes navigation
- provides a notification system for errors

### 14.2 `layouts/navigation.blade.php`

Purpose:

- top navbar
- links to Chats and Logs
- user profile dropdown

### 14.3 `chats/index.blade.php`

Purpose:

- chat landing page when no conversation is selected
- displays chat list sidebar
- includes periodic sidebar refresh and recent-contact sync

### 14.4 `chats/show.blade.php`

Purpose:

- full chat page for one contact
- contains most of the client-side runtime behavior

Important behavior inside this Blade:

- initializes Alpine.js state with `chatApp(contactId)`
- polls for messages every 5 seconds
- refreshes sidebar every 30 seconds
- polls or refreshes lock state
- sends messages via fetch
- releases lock on page unload with `sendBeacon` where possible

So in this project, Blade is not only HTML templating. It also contains a lot of page-specific JavaScript logic.

### 14.5 `chats/partials/list-items.blade.php`

Purpose:

- render chat list rows
- reused by both full page load and AJAX sidebar refresh

Why partials help:

- no need to duplicate the same markup in several pages

### 14.6 `logs/index.blade.php`

Purpose:

- render API logs and application error logs
- shows different tables/cards based on active tab

## 15. Services plus Blade plus controller together: one real example

Example: opening a chat

1. Browser requests `/chats/5`
2. Route hits `ChatController@show`
3. Controller returns `chats.show`
4. Blade loads
5. Alpine `init()` runs
6. JS calls `/chats/5/lock`
7. JS calls `/chats/5/messages`
8. `ChatApiController@messages` calls `SltWhatsappClient->getMessages(...)`
9. Controller returns JSON
10. Blade JS renders bubbles

That is the real runtime loop for this project.

## 16. Console commands: part of the real application flow

Even though you did not ask specifically about commands, they are important because this app uses them as part of its architecture.

Create one with:

```bash
php artisan make:command WhatsappSyncContacts
```

### `WhatsappSyncContacts`

This is the active background-sync command.

Flow:

1. Receive `--limit`
2. Call `SltWhatsappClient->getRecentActiveMobiles()`
3. Create or update `Contact` rows
4. Print summary to terminal

### `WhatsappSync`

This is an older DB message sync command.

Flow:

1. Load contacts
2. Fetch messages from SLT
3. Map payload rows into `Message` rows
4. Update `last_synced_at`

But current project note:

- scheduler line for this command is disabled
- UI message view does not depend on this table for inbound history

## 17. Providers: what happens in this project and how to create them

Providers are where Laravel bootstraps services and bindings.

Create one with:

```bash
php artisan make:provider ChatServiceProvider
```

### `AppServiceProvider`

Current state in this project:

- `register()` is empty
- `boot()` is empty

That means:

- no custom service-container bindings
- no global view composers
- no custom boot logic here yet

### When you would use a provider in this project

Examples:

- bind an interface to a concrete WhatsApp client
- register a reusable singleton service
- add global pagination style
- share global view data

Example binding:

```php
public function register(): void
{
    $this->app->singleton(SltWhatsappClient::class, function () {
        return new SltWhatsappClient();
    });
}
```

Laravel already auto-resolves `SltWhatsappClient` here because it has no complex constructor dependencies beyond config calls, so a custom binding is not required right now.

## 18. Public folder: what happens in this project and how to create its contents

The `public/` folder is the web server document root.

### `public/index.php`

This is the HTTP entry point.

Flow:

1. define request start time
2. load maintenance mode file if present
3. load Composer autoloader
4. load `bootstrap/app.php`
5. capture request and hand it to Laravel

### `public/build/`

Contains Vite production build output:

- compiled CSS
- compiled JS
- manifest file

How it is created:

```bash
npm run build
```

### `public/images/`

Contains static assets such as `slt-logo.png`.

### `public/.htaccess`

Used by Apache to route requests into Laravel.

## 19. Bootstrap: what happens in this project and how to create its flow

### `bootstrap/app.php`

This is where Laravel 12 configures the application instance.

In this project it defines:

- web routes file
- console routes file
- health endpoint `/up`

This file is the bridge between the framework bootstrap and your application files.

### `bootstrap/providers.php`

Lists service providers to load.

In this project:

- only `App\Providers\AppServiceProvider::class`

### `bootstrap/cache/`

Contains framework-generated cached files such as services and packages lists.

You normally do not edit these manually.

## 20. How to create each custom layer in Laravel

### Controller

```bash
php artisan make:controller ChatController
```

### Model

```bash
php artisan make:model Contact
php artisan make:model Contact -m
```

### Migration

```bash
php artisan make:migration create_contacts_table --create=contacts
```

### Seeder

```bash
php artisan make:seeder AdminSeeder
```

### Command

```bash
php artisan make:command WhatsappSyncContacts
```

### Provider

```bash
php artisan make:provider ChatServiceProvider
```

### Blade view

Create manually under:

```text
resources/views/...
```

### Service

Create manually under:

```text
app/Services/...
```

### Config file

Create manually under:

```text
config/...
```

## 21. If you want to add a new feature in the same style as this project

Example: add "contact notes"

1. Create migration: add `notes` column to `contacts`.
2. Update `Contact` model `fillable`.
3. Add validation in `ContactController`.
4. Add route if needed.
5. Add form field in `chats/show.blade.php`.
6. If notes need external API behavior, add or extend a service.
7. If notes need scheduled sync, add a command and scheduler entry.

That is the normal Laravel extension path in this project.

## 22. Short summary of the real architecture of this app

This project is a Laravel web dashboard around one external API client.

The practical architecture is:

1. Blade renders the shell pages.
2. Alpine.js inside Blade calls JSON endpoints.
3. Controllers coordinate the request.
4. `SltWhatsappClient` talks to SLT API.
5. Models store local data for contacts, outgoing messages, API logs, users, and lock state.
6. Commands and the scheduler keep contacts fresh.
7. Config and `.env` drive runtime behavior like lock TTL, sync limits, timezone, DB, and API credentials.

The most important thing to understand is this:

- the UI is server-rendered first
- then it behaves like a lightweight polling frontend
- and the real business integration point is `SltWhatsappClient`

If you understand `routes/web.php`, the custom controllers, `SltWhatsappClient`, the `Contact` and `Message` models, and `chats/show.blade.php`, you understand most of this application.
