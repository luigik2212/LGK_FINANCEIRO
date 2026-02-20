# LGK Financeiro - Base SaaS em PHP

Primeira etapa do SaaS multiusuário com:

- Cadastro de usuário
- Login com sessão
- Dashboard privado com resumo das contas
- Página de contas para cadastrar e listar despesas domésticas
- Envio de e-mail de boas-vindas ao concluir cadastro

## Como executar

```bash
php -S 0.0.0.0:8000
```

Acesse `http://localhost:8000`.

## Observações

- O banco é SQLite em `storage/database.sqlite` e é criado automaticamente.
- O envio de e-mail usa `mail()` do PHP. Se o servidor SMTP local não estiver configurado, a falha é registrada em `storage/logs/emails.log`.
