# Sistema de Gerenciamento Financeiro com Importação de Arquivos

Sistema MVP para gerenciamento de dados financeiros que permite a importação e processamento de arquivos CSV e Excel. O projeto suporta o processamento de arquivos grandes, com capacidade testada para até 250 mil registros de transações no mesmo arquivo.

O sistema processa dados de forma assíncrona, garantindo eficiência mesmo com arquivos grandes. Inclui funcionalidades para consultas aos dados importados, geração de relatórios e visualizações através de dashboards, além de integrações com IA via MCP (Model Context Protocol).

Este é um projeto criado como estudo e hobby, utilizando Laravel, Filament e técnicas de processamento assíncrono.

## Funcionalidades Implementadas

- **Autenticação Completa**: Login, registro e recuperação de senha
- **Painel Administrativo**: Interface com Filament
- **Importação de Arquivos Financeiros**: Suporte para arquivos CSV e Excel com dados financeiros
- **Validação de Dados**: Verificação dos campos obrigatórios (data, descrição, categoria, valor, tipo)
- **Processamento Assíncrono**: Processamento de arquivos em background usando filas
- **Notificações por Email**: Envio automático de confirmação quando a importação é concluída
- **Armazenamento Seguro**: Dados financeiros vinculados ao usuário autenticado
- **Listagem de Transações**: Visualização paginada de todas as transações do usuário com busca e ordenação
- **Remoção de Transações**: Exclusão de transações com atualização automática dos relatórios
- **Relatórios Financeiros**: Visualização de estatísticas financeiras por usuário com filtros por ano, mês, categoria e tipo
- **Monitoramento de Filas**: Dashboard do Laravel Horizon para acompanhar processamento de tarefas
- **Formatação Brasileira**: Valores monetários formatados em Real (R$ 1.000,00)
- **Integração com IA**: Suporte ao Model Context Protocol (MCP) para análises e consultas inteligentes dos dados financeiros com envio de relatórios por email

## Tecnologias Utilizadas

- **Laravel 12**: Framework PHP
- **Laravel Sail**: Ambiente de desenvolvimento Docker
- **Filament 3**: Framework de administração
- **PostgreSQL**: Banco de dados relacional
- **Redis**: Cache e filas
- **Mailtrap**: Serviço para testes de email
- **Laravel Horizon**: Monitoramento e gerenciamento de filas em tempo real
- **Laravel Telescope**: Monitoramento e debug da aplicação (desenvolvimento)

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
**Importante**: Mantenha este comando rodando em um terminal separado. O Laravel Horizon é obrigatório para o funcionamento correto do sistema, pois gerencia tanto o processamento de importações quanto a atualização de estatísticas financeiras.

**Por que usar o Horizon?**

O sistema possui filas que executam em tempos específicos, como a atualização de estatísticas financeiras, que não ocorre necessariamente após cada lançamento. Existe uma regra de atualização inteligente para evitar processamentos duplicados e otimizar a performance.

**O Horizon oferece**:

- Monitoramento robusto e contínuo das filas
- Dashboard visual para acompanhar o processamento
- Gerenciamento automático de workers
- Métricas em tempo real
- Reinicialização automática em caso de falhas

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

## Exemplo de Logs de Importação

Abaixo um exemplo simplificado dos logs gerados durante uma importação bem-sucedida de arquivo CSV/Excel:

```log
local.INFO: Iniciando processamento do arquivo para usuário 123: /app/storage/uploads/arquivo.csv
local.INFO: Armazenando chunk de 1000 transações
local.INFO: Chunk armazenado com sucesso
local.INFO: Armazenando chunk de 500 transações
local.INFO: Chunk armazenado com sucesso
local.INFO: Processamento concluído: 1500 transações
local.INFO: E-mail enviado para usuario@exemplo.com
local.INFO: Iniciando processamento de estatísticas financeiras para usuário: 123
local.INFO: Estatísticas atualizadas com sucesso: 180 registros
local.INFO: Processamento de estatísticas concluído para usuário: 123
```

## Contribuição

Este é um projeto de estudo e hobby. Contribuições são bem-vindas!

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).