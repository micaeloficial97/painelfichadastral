# Painel Ficha Cadastral

Painel administrativo para acompanhar cadastros de parceiros interessados em comprar com a empresa.

## Configuracao

1. Copie `.env.example` para `.env`.
2. Preencha as credenciais de banco de dados e SMTP no `.env`.
3. Publique os arquivos em um ambiente PHP com acesso ao banco MySQL.

O arquivo `.env` nao deve ser enviado ao GitHub.

## Estrutura principal

- `index.php`: tela principal do painel.
- `login.php` e `auth.php`: autenticacao.
- `api/submissions/`: endpoints de atualizacao, detalhe, historico e envio de e-mail.
- `assets/`: CSS e JavaScript do painel.
- `cron_sync.php`: sincronizacao de arquivos de cadastro.
