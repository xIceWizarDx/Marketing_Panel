# Marketing Panel

Projeto privado desenvolvido em **Laravel 12** com **React** e **Inertia.js**, com o objetivo de criar um painel de marketing integrado a múltiplas redes sociais. O sistema oferece autenticação nativa, gerenciamento de usuários e estrutura preparada para expansão com novas funcionalidades.

## 🚀 Tecnologias Utilizadas

- **Laravel 12** – Backend em PHP
- **React + Inertia.js** – Frontend dinâmico sem necessidade de API REST
- **SQLite** – Banco de dados padrão para desenvolvimento (configurável via `.env`)
- **Pest** – Framework moderno para testes automatizados
- **Vite** – Build e hot reload do frontend
- **Tailwind CSS** – Estilização rápida e responsiva

## 📁 Estrutura do Projeto com 

```
Get-ChildItem -Force -Recurse | % { $_.FullName } | Out-File projeto-tree-completo.txt -Encoding utf8
```

```
marketing_panel/
app\Http
app\Models
app\Providers
app\Http\Controllers
app\Http\Middleware
app\Http\Requests
app\Http\Controllers\Auth
app\Http\Controllers\Settings
app\Http\Controllers\Controller.php
app\Http\Controllers\Auth\AuthenticatedSessionController.php
app\Http\Controllers\Auth\ConfirmablePasswordController.php
app\Http\Controllers\Auth\EmailVerificationNotificationController.php
app\Http\Controllers\Auth\EmailVerificationPromptController.php
app\Http\Controllers\Auth\NewPasswordController.php
app\Http\Controllers\Auth\PasswordResetLinkController.php
app\Http\Controllers\Auth\RegisteredUserController.php
app\Http\Controllers\Auth\VerifyEmailController.php
app\Http\Controllers\Settings\PasswordController.php
app\Http\Controllers\Settings\ProfileController.php
app\Http\Middleware\HandleAppearance.php
app\Http\Middleware\HandleInertiaRequests.php
app\Http\Requests\Auth
app\Http\Requests\Settings
app\Http\Requests\Auth\LoginRequest.php
app\Http\Requests\Settings\ProfileUpdateRequest.php
app\Models\User.php
app\Providers\AppServiceProvider.php
bootstrap\cache
bootstrap\app.php
bootstrap\providers.php
bootstrap\cache\.gitignore
config\app.php
config\auth.php
config\cache.php
config\database.php
config\filesystems.php
config\inertia.php
config\logging.php
config\mail.php
config\queue.php
config\services.php
config\session.php
database\factories
database\migrations
database\seeders
database\.gitignore
database\factories\UserFactory.php
database\migrations\0001_01_01_000000_create_users_table.php
database\migrations\0001_01_01_000001_create_cache_table.php
database\migrations\0001_01_01_000002_create_jobs_table.php
database\seeders\DatabaseSeeder.php
public\.htaccess
public\apple-touch-icon.png
public\favicon.ico
public\favicon.svg
public\index.php
public\logo.svg
public\robots.txt
resources\css
resources\js
resources\views
resources\css\app.css
resources\js\components
resources\js\hooks
resources\js\layouts
resources\js\lib
resources\js\pages
resources\js\types
resources\js\app.tsx
resources\js\ssr.tsx
resources\js\components\ui
resources\js\components\app-content.tsx
resources\js\components\app-header.tsx
resources\js\components\app-logo-icon.tsx
resources\js\components\app-logo.tsx
resources\js\components\app-shell.tsx
resources\js\components\app-sidebar-header.tsx
resources\js\components\app-sidebar.tsx
resources\js\components\appearance-dropdown.tsx
resources\js\components\appearance-tabs.tsx
resources\js\components\breadcrumbs.tsx
resources\js\components\delete-user.tsx
resources\js\components\heading-small.tsx
resources\js\components\heading.tsx
resources\js\components\icon.tsx
resources\js\components\input-error.tsx
resources\js\components\nav-footer.tsx
resources\js\components\nav-main.tsx
resources\js\components\nav-user.tsx
resources\js\components\text-link.tsx
resources\js\components\user-info.tsx
resources\js\components\user-menu-content.tsx
resources\js\components\ui\alert.tsx
resources\js\components\ui\avatar.tsx
resources\js\components\ui\badge.tsx
resources\js\components\ui\breadcrumb.tsx
resources\js\components\ui\button.tsx
resources\js\components\ui\card.tsx
resources\js\components\ui\checkbox.tsx
resources\js\components\ui\collapsible.tsx
resources\js\components\ui\dialog.tsx
resources\js\components\ui\dropdown-menu.tsx
resources\js\components\ui\icon.tsx
resources\js\components\ui\input.tsx
resources\js\components\ui\label.tsx
resources\js\components\ui\navigation-menu.tsx
resources\js\components\ui\placeholder-pattern.tsx
resources\js\components\ui\select.tsx
resources\js\components\ui\separator.tsx
resources\js\components\ui\sheet.tsx
resources\js\components\ui\sidebar.tsx
resources\js\components\ui\skeleton.tsx
resources\js\components\ui\toggle-group.tsx
resources\js\components\ui\toggle.tsx
resources\js\components\ui\tooltip.tsx
resources\js\hooks\use-appearance.tsx
resources\js\hooks\use-initials.tsx
resources\js\hooks\use-mobile-navigation.ts
resources\js\hooks\use-mobile.tsx
resources\js\layouts\app
resources\js\layouts\auth
resources\js\layouts\settings
resources\js\layouts\app-layout.tsx
resources\js\layouts\auth-layout.tsx
resources\js\layouts\app\app-header-layout.tsx
resources\js\layouts\app\app-sidebar-layout.tsx
resources\js\layouts\auth\auth-card-layout.tsx
resources\js\layouts\auth\auth-simple-layout.tsx
resources\js\layouts\auth\auth-split-layout.tsx
resources\js\layouts\settings\layout.tsx
resources\js\lib\utils.ts
resources\js\pages\auth
resources\js\pages\settings
resources\js\pages\dashboard.tsx
resources\js\pages\welcome.tsx
resources\js\pages\auth\confirm-password.tsx
resources\js\pages\auth\forgot-password.tsx
resources\js\pages\auth\login.tsx
resources\js\pages\auth\register.tsx
resources\js\pages\auth\reset-password.tsx
resources\js\pages\auth\verify-email.tsx
resources\js\pages\settings\appearance.tsx
resources\js\pages\settings\password.tsx
resources\js\pages\settings\profile.tsx
resources\js\types\index.d.ts
resources\js\types\vite-env.d.ts
resources\views\app.blade.php
routes\auth.php
routes\console.php
routes\settings.php
routes\web.php
storage\app
storage\framework
storage\logs
storage\app\private
storage\app\public
storage\app\.gitignore
storage\app\private\.gitignore
storage\app\public\.gitignore
storage\framework\cache
storage\framework\sessions
storage\framework\testing
storage\framework\views
storage\framework\.gitignore
storage\framework\cache\data
storage\framework\cache\.gitignore
storage\framework\cache\data\.gitignore
storage\framework\sessions\.gitignore
storage\framework\testing\.gitignore
storage\framework\views\.gitignore
storage\logs\.gitignore
tests\Feature
tests\Unit
tests\Pest.php
tests\TestCase.php
tests\Feature\Auth
tests\Feature\Settings
tests\Feature\DashboardTest.php
tests\Feature\ExampleTest.php
tests\Feature\Auth\AuthenticationTest.php
tests\Feature\Auth\EmailVerificationTest.php
tests\Feature\Auth\PasswordConfirmationTest.php
tests\Feature\Auth\PasswordResetTest.php
tests\Feature\Auth\RegistrationTest.php
tests\Feature\Settings\PasswordUpdateTest.php
tests\Feature\Settings\ProfileUpdateTest.php
tests\Unit\ExampleTest.php
```

## ⚙️ Instalação

Clone o repositório:

```bash
git clone <url-do-repositorio>
cd marketing_panel
```

Instale as dependências PHP:

```bash
composer install
```

Instale as dependências Node:

```bash
npm install
```

Configure o ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

Execute as migrations:

```bash
php artisan migrate
```

## 🖥️ Execução

### Backend (Laravel)

```bash
php artisan serve
```

Acesse em: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Frontend (Vite + React)

```bash
npm run dev
```

> O Vite cuidará do hot reload automaticamente.

## 🔐 Autenticação

O projeto já vem com autenticação completa, incluindo:

- Registro de usuários
- Login
- Recuperação de senha
- Perfil do usuário

## ✅ Testes

Para rodar os testes:

```bash
composer test
# ou
./vendor/bin/pest
```

para o VSCode identificar o .tsx
```bash
npm i -D typescript @types/react @types/react-dom
```

