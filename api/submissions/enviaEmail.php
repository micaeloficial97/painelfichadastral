<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config.php';

$base = dirname(__DIR__, 2) . '/PHPMailer/src/';
require $base . 'Exception.php';
require $base . 'PHPMailer.php';
require $base . 'SMTP.php';

$mail = new PHPMailer(true);
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$status = isset($payload['status']) ? trim($payload['status']) : null;
$obs = trim($payload['obs'] ?? '');

try {
    $mail->isSMTP();
    $mail->Host = env_value('MAIL_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = env_value('MAIL_USERNAME', '');
    $mail->Password = env_value('MAIL_PASSWORD', '');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) env_value('MAIL_PORT', '587');

    $mailFrom = env_value('MAIL_FROM', (string) $mail->Username);

    if ($status === 'Reprovado_Financeiro') {
        $mail->setFrom($mailFrom);
        $mail->addAddress('danielaramos@kazzapersianas.com.br');
        $mail->addAddress('comercial@kazzapersianas.com.br');
        $mail->isHTML(false);
        $mail->Subject = 'Cadastro Reprovado pelo Financeiro';
        $mail->Body = "Cadastro reprovado pelo Financeiro. Motivo: $obs.\n\n"
            . "Por favor, verifique os detalhes no painel.\n\n"
            . "Painel: https://fichacadastral.kazzapersianas.com.br/painel/\n\n"
            . "Email gerado automaticamente, favor nao responder.";
    } else {
        $mail->setFrom($mailFrom);
        $mail->addAddress('financeiro@kazzapersianas.com.br');
        $mail->addAddress('financeiro2@kazzapersianas.com.br');
        $mail->addAddress('administrativo@kazzapersianas.com.br');
        $mail->isHTML(false);
        $mail->Subject = 'Cadastro aguardando etapa do financeiro';
        $mail->Body = "Ola!\n\n"
            . "Ha um cadastro pendente que precisa da sua analise.\n"
            . "Por favor, acesse o painel e conclua a etapa:\n"
            . "https://fichacadastral.kazzapersianas.com.br/painel/\n\n"
            . "Email gerado automaticamente, favor nao responder.";
    }

    $mail->send();

    echo json_encode([
        'ok' => true,
        'msg' => 'Email enviado com sucesso!'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'erro' => $mail->ErrorInfo
    ]);
}
