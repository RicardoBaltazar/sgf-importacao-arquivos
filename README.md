# Sistema de Gerenciamento Financeiro com Importação de Arquivos

Sistema MVP para gerenciamento de dados financeiros que permite a importação e processamento de arquivos CSV e Excel. O projeto visa desenvolver uma plataforma que suporte o processamento de grandes volumes de dados, com capacidade para arquivos de até 1 milhão de linhas.

O sistema foi projetado para processar dados de forma assíncrona, garantindo eficiência mesmo com arquivos grandes. Futuramente, serão implementadas funcionalidades para consultas aos dados importados, geração de relatórios e visualizações através de dashboards.

Este é um projeto em desenvolvimento, criado como estudo e hobby, utilizando Laravel, Filament e técnicas de processamento assíncrono.

## Funcionalidades Implementadas

- **Autenticação Completa**: Login, registro e recuperação de senha
- **Painel Administrativo**: Interface moderna com Filament
- **Importação de Arquivos Financeiros**: Suporte para arquivos CSV e Excel com dados financeiros
- **Validação de Dados**: Verificação dos campos obrigatórios (data, descrição, categoria, valor, tipo)
- **Processamento Assíncrono**: Processamento de arquivos em background usando filas
- **Notificações por Email**: Envio automático de confirmação quando a importação é concluída
- **Armazenamento Seguro**: Dados financeiros vinculados ao usuário autenticado
- **Listagem de Transações**: Visualização paginada de todas as transações do usuário com busca e ordenação
- **Remoção de Transações**: Exclusão individual ou em lote de transações com atualização automática dos relatórios
- **Relatórios Financeiros**: Visualização de estatísticas financeiras por usuário com filtros por ano, mês, categoria e tipo
- **Monitoramento de Filas**: Dashboard do Laravel Horizon para acompanhar processamento de tarefas
- **Formatação Brasileira**: Valores monetários formatados em Real (R$ 1.000,00)

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
git clone https://github.com/RicardoBaltazar/sgf-importacao-arquivos.git sgf
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

### 8. Inicie o processamento de filas

#### Opção 1: Laravel Horizon (Recomendado)
```bash
./vendor/bin/sail artisan horizon
```
#### Opção 2: Worker Tradicional

```bash
./vendor/bin/sail artisan queue:work
```
Nota: Mantenha um destes comandos rodando em um terminal separado. Eles são responsáveis por processar as importações de arquivos e gerar as estatísticas financeiras em background.

Diferenças:

Horizon: Oferece dashboard visual, métricas em tempo real e melhor gerenciamento

Queue:work: Opção mais simples, sem interface gráfica

## Acessando o Sistema

- **Aplicação**: [http://localhost](http://localhost)
- **Painel Administrativo**: [http://localhost/admin](http://localhost/admin)
- **Dashboard Horizon**: [http://localhost/horizon](http://localhost/horizon)


## Configuração de Email (Desenvolvimento)

O sistema está configurado para usar o Mailtrap para testes de email. Para configurar:

1. Crie uma conta no [Mailtrap](https://mailtrap.io/)
2. Obtenha suas credenciais SMTP
3. Atualize as variáveis MAIL_* no arquivo .env

## Executando Testes

```bash
./vendor/bin/sail artisan test
```

## Contribuição

Este é um projeto de estudo e hobby. Contribuições são bem-vindas!

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).