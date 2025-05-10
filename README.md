# Sistema de Gerenciamento Financeiro com Importação de Arquivos

MVP de um Sistema de Gerenciamento Financeiro para importação e análise de dados a partir de arquivos CSV/Excel. Projeto desenvolvido como estudo e hobby utilizando Laravel, Filament e processamento assíncrono de dados.

## Funcionalidades Implementadas

- **Autenticação Completa**: Login, registro e recuperação de senha
- **Painel Administrativo**: Interface moderna com Filament
- **Processamento Assíncrono**: Configuração de filas para processamento de tarefas em background
- **Configuração de Email**: Integração com Mailtrap para testes de email

## Tecnologias Utilizadas

- **Laravel 12**: Framework PHP moderno
- **Laravel Sail**: Ambiente de desenvolvimento Docker
- **Filament 3**: Framework de administração
- **PostgreSQL**: Banco de dados relacional
- **Redis**: Cache e filas
- **Mailtrap**: Serviço para testes de email

## Requisitos

- Docker
- Docker Compose
- Git

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/RicardoBaltazar/Sistema-de-Gerenciamento-Financeiro-com-Importa-o-de-Arquivos.git sgf
cd sgf
```

### 2. Configure o ambiente

Copie o arquivo de ambiente de exemplo:

```bash
cp .env.example .env
```

### 3. Inicie o Laravel Sail

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Inicie os containers

```bash
./vendor/bin/sail up -d
```

### 5. Gere a chave da aplicação

```bash
./vendor/bin/sail artisan key:generate
```

### 6. Execute as migrações

```bash
./vendor/bin/sail artisan migrate
```

### 7. Publique os assets do Filament

```bash
./vendor/bin/sail artisan filament:assets
```

### 8. Inicie o worker de filas

```bash
./vendor/bin/sail artisan queue:work
```

## Acessando o Sistema

- **Aplicação**: [http://localhost](http://localhost)
- **Painel Administrativo**: [http://localhost/admin](http://localhost/admin)

## Configuração de Email (Desenvolvimento)

O sistema está configurado para usar o Mailtrap para testes de email. Para configurar:

1. Crie uma conta no [Mailtrap](https://mailtrap.io/)
2. Obtenha suas credenciais SMTP
3. Atualize as variáveis MAIL_* no arquivo .env

## Executando Testes

```bash
./vendor/bin/sail artisan test
```

## Comandos Úteis

- **Iniciar os containers**: `./vendor/bin/sail up -d`
- **Parar os containers**: `./vendor/bin/sail down`
- **Executar comandos Artisan**: `./vendor/bin/sail artisan [comando]`
- **Executar Composer**: `./vendor/bin/sail composer [comando]`
- **Executar NPM**: `./vendor/bin/sail npm [comando]`
- **Acessar o shell**: `./vendor/bin/sail shell`
- **Acessar o banco de dados**: `./vendor/bin/sail pgsql`

## Próximos Passos

- Implementação do módulo de importação de arquivos CSV/Excel
- Desenvolvimento de dashboard para visualização de dados
- Criação de relatórios financeiros
- Implementação de permissões de usuário

## Contribuição

Este é um projeto de estudo e hobby. Contribuições são bem-vindas!

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).