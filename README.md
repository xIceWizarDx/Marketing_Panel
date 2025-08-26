# Marketing Panel

Projeto privado desenvolvido em Laravel 12 com React e Inertia.js, com o objetivo de criar um painel de marketing integrado a múltiplas redes sociais. O sistema oferece autenticação nativa, gerenciamento de usuários e estrutura pronta para expansão com novas funcionalidades.

## Tecnologias

- **Laravel 12** – Backend em PHP
- **React + Inertia.js** – Frontend dinâmico sem necessidade de API REST inicial
- **SQLite** – Banco de dados padrão para desenvolvimento (ajustável no `.env`)
- **Pest** – Framework de testes automatizados
- **Vite** – Build e hot reload do frontend
- **Tailwind CSS** – Estilização rápida e responsiva

## Estrutura

```
marketing_panel/
├── app/               # Backend (Laravel)
├── database/          # Migrations e SQLite
├── public/            # Assets e build do Vite
├── resources/
│   ├── js/            # Frontend (React + Inertia)
│   │   ├── Components # Componentes reutilizáveis
│   │   └── Pages      # Páginas (Dashboard, Login, etc.)
│   └── views/         # Views Blade usadas pelo Inertia
├── routes/
│   └── web.php        # Rotas principais
├── tests/             # Testes com Pest
└── .env               # Configurações de ambiente
```

## Instalação

Clonar o projeto:

```bash
git clone <repositorio-privado>
cd marketing_panel
```

Instalar dependências PHP:

```bash
composer install
```

Instalar dependências Node:

```bash
npm install
```

Configurar `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Migrar banco:

```bash
php artisan migrate
```

## Execução

### Backend (Laravel)

```bash
php artisan serve
```

Acesse <http://127.0.0.1:8000>

### Frontend (React + Vite)

```bash
npm run dev
```

O Vite cuidará do hot reload automaticamente.

## Autenticação

O projeto já vem com autenticação completa:

- Registro
- Login
- Recuperação de senha
- Perfil de usuário

## Testes

Rodar os testes:

```bash
composer test
# ou
./vendor/bin/pest
```

