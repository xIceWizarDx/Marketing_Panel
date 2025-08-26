# Marketing Panel

Projeto privado desenvolvido em **Laravel 12** com **React** e **Inertia.js**, com o objetivo de criar um painel de marketing integrado a múltiplas redes sociais. O sistema oferece autenticação nativa, gerenciamento de usuários e estrutura preparada para expansão com novas funcionalidades.

## 🚀 Tecnologias Utilizadas

- **Laravel 12** – Backend em PHP
- **React + Inertia.js** – Frontend dinâmico sem necessidade de API REST
- **SQLite** – Banco de dados padrão para desenvolvimento (configurável via `.env`)
- **Pest** – Framework moderno para testes automatizados
- **Vite** – Build e hot reload do frontend
- **Tailwind CSS** – Estilização rápida e responsiva

## 📁 Estrutura do Projeto

```
marketing_panel/
├── app/               # Backend (Laravel)
├── database/          # Migrations e SQLite
├── public/            # Assets e build do Vite
├── resources/
│   ├── js/            # Frontend (React + Inertia)
│   │   ├── Components # Componentes reutilizáveis
│   │   └── Pages      # Páginas (Dashboard, Login, etc.)
│   └── views/         # Views Blade utilizadas pelo Inertia
├── routes/
│   └── web.php        # Rotas principais
├── tests/             # Testes com Pest
└── .env               # Configurações de ambiente
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
