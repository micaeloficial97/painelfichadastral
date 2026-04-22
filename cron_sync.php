<?php
declare(strict_types=1);

// Ajusta se o caminho do db.php for diferente
require __DIR__ . '/db.php';

// OPCIONAL: se quiser travar acesso via web e só rodar pelo CRON interno/CLI, descomente:
/*
if (php_sapi_name() !== 'cli') {
    // se quiser proteger, pode exigir um token no GET:
    if (($_GET['token'] ?? '') !== 'SUA_CHAVE_SECRETA_AQUI') {
        http_response_code(403);
        exit('forbidden');
    }
}
*/

$logFile = __DIR__ . '/cron_sync.log';

try {

    // Vamos rodar tudo dentro de uma transação só pra ficar consistente.
    // Se sua tabela usa MyISAM isso não vai ter efeito; se usa InnoDB, ajuda.
    $pdo->beginTransaction();

    // documentosocio1path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT
          pc.id,
          SUBSTRING_INDEX(pc.documentosocio1path,'/',-1),
          SUBSTRING_INDEX(pc.documentosocio1path,'/',-1),
          CASE LOWER(SUBSTRING_INDEX(pc.documentosocio1path,'.',-1))
            WHEN 'jpg'  THEN 'image/jpeg'
            WHEN 'jpeg' THEN 'image/jpeg'
            WHEN 'png'  THEN 'image/png'
            WHEN 'webp' THEN 'image/webp'
            WHEN 'gif'  THEN 'image/gif'
            WHEN 'svg'  THEN 'image/svg+xml'
            WHEN 'pdf'  THEN 'application/pdf'
            ELSE NULL
          END,
          NULL,
          NOW()
        FROM pre_cadastro pc
        WHERE pc.documentosocio1path IS NOT NULL AND pc.documentosocio1path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.documentosocio1path,'/',-1)
          )
    ");

    // cpfsocio1path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cpfsocio1path,'/',-1),
               SUBSTRING_INDEX(pc.cpfsocio1path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cpfsocio1path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cpfsocio1path IS NOT NULL AND pc.cpfsocio1path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cpfsocio1path,'/',-1)
          )
    ");

    // documentosocio2path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.documentosocio2path,'/',-1),
               SUBSTRING_INDEX(pc.documentosocio2path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.documentosocio2path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.documentosocio2path IS NOT NULL AND pc.documentosocio2path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.documentosocio2path,'/',-1)
          )
    ");

    // cpfsocio2path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cpfsocio2path,'/',-1),
               SUBSTRING_INDEX(pc.cpfsocio2path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cpfsocio2path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cpfsocio2path IS NOT NULL AND pc.cpfsocio2path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cpfsocio2path,'/',-1)
          )
    ");

    // documentosocio3path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.documentosocio3path,'/',-1),
               SUBSTRING_INDEX(pc.documentosocio3path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.documentosocio3path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.documentosocio3path IS NOT NULL AND pc.documentosocio3path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.documentosocio3path,'/',-1)
          )
    ");

    // cpfsocio3path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cpfsocio3path,'/',-1),
               SUBSTRING_INDEX(pc.cpfsocio3path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cpfsocio3path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cpfsocio3path IS NOT NULL AND pc.cpfsocio3path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cpfsocio3path,'/',-1)
          )
    ");

    // documentosocio4path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.documentosocio4path,'/',-1),
               SUBSTRING_INDEX(pc.documentosocio4path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.documentosocio4path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.documentosocio4path IS NOT NULL AND pc.documentosocio4path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.documentosocio4path,'/',-1)
          )
    ");

    // cpfsocio4path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cpfsocio4path,'/',-1),
               SUBSTRING_INDEX(pc.cpfsocio4path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cpfsocio4path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cpfsocio4path IS NOT NULL AND pc.cpfsocio4path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cpfsocio4path,'/',-1)
          )
    ");

    // documentosocio5path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.documentosocio5path,'/',-1),
               SUBSTRING_INDEX(pc.documentosocio5path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.documentosocio5path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.documentosocio5path IS NOT NULL AND pc.documentosocio5path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.documentosocio5path,'/',-1)
          )
    ");

    // cpfsocio5path
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cpfsocio5path,'/',-1),
               SUBSTRING_INDEX(pc.cpfsocio5path,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cpfsocio5path,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cpfsocio5path IS NOT NULL AND pc.cpfsocio5path <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cpfsocio5path,'/',-1)
          )
    ");

    // contratosocialpath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.contratosocialpath,'/',-1),
               SUBSTRING_INDEX(pc.contratosocialpath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.contratosocialpath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.contratosocialpath IS NOT NULL AND pc.contratosocialpath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.contratosocialpath,'/',-1)
          )
    ");

    // cartaocnpjpath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cartaocnpjpath,'/',-1),
               SUBSTRING_INDEX(pc.cartaocnpjpath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cartaocnpjpath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cartaocnpjpath IS NOT NULL AND pc.cartaocnpjpath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cartaocnpjpath,'/',-1)
          )
    ");

    // cartaoinscricaoestadualpath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.cartaoinscricaoestadualpath,'/',-1),
               SUBSTRING_INDEX(pc.cartaoinscricaoestadualpath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.cartaoinscricaoestadualpath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.cartaoinscricaoestadualpath IS NOT NULL AND pc.cartaoinscricaoestadualpath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.cartaoinscricaoestadualpath,'/',-1)
          )
    ");

    // fotofachadadalojapath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.fotofachadadalojapath,'/',-1),
               SUBSTRING_INDEX(pc.fotofachadadalojapath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.fotofachadadalojapath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.fotofachadadalojapath IS NOT NULL AND pc.fotofachadadalojapath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.fotofachadadalojapath,'/',-1)
          )
    ");

    // fotointernadalojaopath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.fotointernadalojapath,'/',-1),
               SUBSTRING_INDEX(pc.fotointernadalojapath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.fotointernadalojapath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.fotointernadalojapath IS NOT NULL AND pc.fotointernadalojapath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.fotointernadalojapath,'/',-1)
          )
    ");

    // comprovanteenderecopath
    $pdo->exec("
        INSERT IGNORE INTO submission_files (pre_id, orig_name, stored_name, mime, size, uploaded_at)
        SELECT pc.id,
               SUBSTRING_INDEX(pc.comprovanteenderecopath,'/',-1),
               SUBSTRING_INDEX(pc.comprovanteenderecopath,'/',-1),
               CASE LOWER(SUBSTRING_INDEX(pc.comprovanteenderecopath,'.',-1))
                 WHEN 'jpg'  THEN 'image/jpeg'
                 WHEN 'jpeg' THEN 'image/jpeg'
                 WHEN 'png'  THEN 'image/png'
                 WHEN 'webp' THEN 'image/webp'
                 WHEN 'gif'  THEN 'image/gif'
                 WHEN 'svg'  THEN 'image/svg+xml'
                 WHEN 'pdf'  THEN 'application/pdf'
                 ELSE NULL
               END,
               NULL, NOW()
        FROM pre_cadastro pc
        WHERE pc.comprovanteenderecopath IS NOT NULL AND pc.comprovanteenderecopath <> ''
          AND NOT EXISTS (
            SELECT 1 FROM submission_files sf
             WHERE sf.pre_id = pc.id
               AND sf.stored_name = SUBSTRING_INDEX(pc.comprovanteenderecopath,'/',-1)
          )
    ");

    // fecha transação
    $pdo->commit();

    // loga sucesso
    $msg = date('Y-m-d H:i:s') . " - cron_sync OK\n";
    file_put_contents($logFile, $msg, FILE_APPEND);

} catch (Throwable $e) {

    // se algo falhar, rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $err = date('Y-m-d H:i:s') . " - ERRO cron_sync: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $err, FILE_APPEND);

    // Se rodar via navegador, você pode querer ver erro:
    // echo "Erro: " . htmlspecialchars($e->getMessage());
    // mas pro CRON silencioso, nem precisa ecoar nada.
}

