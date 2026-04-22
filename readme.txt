Backup — Versão 3 do painel (V251103)

Este backup corresponde à terceira versão do painel com as seguintes alterações e detalhes:

1. Envio automático de e-mails por status
   - Reprovado (Comercial): ao definir o status como "Reprovado", o sistema envia e-mail automaticamente para o Comercial informando a reprovação.
   - Aprovado pelo Comercial → Financeiro: quando o Comercial aprova, o sistema envia e-mail ao Financeiro.
   - Assunto (Financeiro): "Cadastro Pendente".
   - Mensagem: informa que há um cadastro pendente e inclui o link de acesso ao painel:
       https://fichacadastral.kazzapersianas.com.br/painel/
   - Rodapé: "Email gerado automaticamente, favor não responder.".
   - Destinatários Financeiro configurados: financeiro@kazzapersianas.com.br; financeiro2@kazzapersianas.com.br; administrativo@kazzapersianas.com.br.
   - Envio via PHPMailer (SMTP Gmail, STARTTLS, porta 587).

2. Filtros por perfil
   - Checkboxes de filtro com status pré‑selecionados conforme perfil (Comercial/Financeiro).

3. Atualização de cache de CSS
   - Inclusão do sufixo ?v=3 no arquivo CSS para forçar atualização em todos os navegadores.
