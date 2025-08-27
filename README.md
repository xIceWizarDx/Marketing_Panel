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
├── app/ # Backend (Controllers, Middleware, Models, Requests)
├── bootstrap/ # Inicialização do framework
├── config/ # Arquivos de configuração
├── database/ # Migrations, seeders e factories
├── MarketingPanel-main/ # Aplicação React independente (base antiga)
├── public/ # Assets públicos e build do Vite
├── resources/
│ ├── css/ # Estilos com Tailwind
│ ├── js/ # Frontend (React + Inertia)
│ │ ├── actions/ # Camada de actions para rotas tipadas
│ │ ├── components/ # Componentes reutilizáveis
│ │ ├── hooks/ # Hooks customizados
│ │ ├── layouts/ # Layouts (auth, app, settings)
│ │ ├── lib/ # Funções utilitárias
│ │ ├── pages/ # Páginas (auth, dashboard, settings, etc.)
│ │ ├── routes/ # Rotas tipadas geradas pelo Wayfinder
│ │ ├── types/ # Tipos TypeScript
│ │ ├── app.tsx # Entrada do React
│ │ └── ssr.tsx # Suporte a Server-Side Rendering
│ └── views/ # Views Blade (shell do Inertia)
├── routes/
│ ├── web.php # Rotas web principais
│ ├── api.php # Rotas de API
│ ├── console.php # Comandos Artisan
│ └── channels.php # Canais de broadcast
├── storage/ # Logs e arquivos gerados
├── tests/ # Testes com Pest
├── .github/ # Workflows de CI/CD
├── composer.json # Dependências PHP
├── package.json # Dependências Node
├── tsconfig.json # Configuração TypeScript
└── vite.config.ts # Configuração do Vite
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
