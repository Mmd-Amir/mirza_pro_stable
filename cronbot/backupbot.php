<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../botapi.php';

$pdoInstance = getDatabaseConnection();
if (!($pdoInstance instanceof PDO) && function_exists('get_pdo_connection')) {
    $pdoInstance = get_pdo_connection();
}

function isExecAvailable()
{
    static $canExec;

    if ($canExec !== null) {
        return $canExec;
    }

    if (!function_exists('exec')) {
        $canExec = false;
        return $canExec;
    }

    $disabledFunctions = ini_get('disable_functions');
    if (!empty($disabledFunctions)) {
        $disabledList = array_map('trim', explode(',', $disabledFunctions));
        if (in_array('exec', $disabledList, true)) {
            $canExec = false;
            return $canExec;
        }
    }

    $canExec = true;
    return $canExec;
}

function createSqlDump(?PDO $pdo, $databaseName, $filePath)
{
    if (!($pdo instanceof PDO)) {
        error_log('createSqlDump: PDO connection is not available.');
        return false;
    }

    try {
        $handle = @fopen($filePath, 'w');
        if ($handle === false) {
            error_log('Unable to open dump file for writing: ' . $filePath);
            return false;
        }

        $header = sprintf("-- Database: `%s`\n-- Generated at: %s\n\nSET FOREIGN_KEY_CHECKS=0;\n\n", $databaseName, date('c'));
        fwrite($handle, $header);

        $tablesStmt = $pdo->query('SHOW TABLES');
        while ($tableRow = $tablesStmt->fetch(PDO::FETCH_NUM)) {
            $tableName = $tableRow[0];

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createData = $createStmt->fetch(PDO::FETCH_ASSOC);
            if (!isset($createData['Create Table'])) {
                continue;
            }

            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle, $createData['Create Table'] . ";\n\n");

            $dataStmt = $pdo->query("SELECT * FROM `{$tableName}`");
            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                $columns = [];
                $values = [];
                foreach ($row as $column => $value) {
                    $columns[] = '`' . str_replace('`', '``', $column) . '`';
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }

                $insertLine = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $tableName,
                    implode(', ', $columns),
                    implode(', ', $values)
                );
                fwrite($handle, $insertLine);
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return true;
    } catch (Throwable $throwable) {
        error_log('Failed to generate SQL dump via PDO: ' . $throwable->getMessage());
        return false;
    }
}

function addPathToZip(ZipArchive $zip, $path, $basePath)
{
    $normalizedBase = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (is_dir($path)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = (string) $file;
            $relativePath = ltrim(str_replace($normalizedBase, '', $filePath), DIRECTORY_SEPARATOR);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    } elseif (is_file($path)) {
        $relativePath = ltrim(str_replace($normalizedBase, '', $path), DIRECTORY_SEPARATOR);
        $zip->addFile($path, $relativePath);
    }
}

$reportbackup = select("topicid","idreport","report","backupfile","select")['idreport'];
$destination = __DIR__;
$setting = select("setting", "*");
$sourcefir = dirname($destination);
$botlist = select("botsaz","*",null,null,"fetchAll");
if ($botlist) {
    foreach ($botlist as $bot) {
        $folderName = $bot['id_user'] . $bot['username'];
        $botBasePath = $sourcefir . '/vpnbot/' . $folderName;
        $zipFilePath = $destination . '/file_' . $folderName . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $pathsToBackup = [
                $botBasePath . '/data',
                $botBasePath . '/product.json',
                $botBasePath . '/product_name.json',
            ];

            foreach ($pathsToBackup as $path) {
                if (file_exists($path)) {
                    addPathToZip($zip, $path, $botBasePath . '/');
                } else {
                    error_log('Backup path not found for bot archive: ' . $path);
                }
            }
            $zip->close();

            telegram('sendDocument', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $reportbackup,
                'document' => new CURLFile($zipFilePath),
                'caption' => "@{$bot['username']} | {$bot['id_user']}",
            ]);

            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        } else {
            error_log('Unable to create zip archive for bot directory: ' . $botBasePath);
        }
    }
}




$backup_file_name = 'backup_' . date("Y-m-d") . '.sql';
$zip_file_name = 'backup_' . date("Y-m-d") . '.zip';

$dumpCreated = false;
$command = "mysqldump -h localhost -u {$usernamedb} -p'{$passworddb}' --no-tablespaces {$dbname} > {$backup_file_name}";

if (isExecAvailable()) {
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    $dumpCreated = ($return_var === 0 && file_exists($backup_file_name));

    if (!$dumpCreated) {
        error_log('mysqldump command failed, attempting PDO-based export.');
    }
} else {
    error_log('exec function is not available; falling back to PDO-based database dump.');
}

if (!$dumpCreated) {
    $dumpCreated = createSqlDump($pdoInstance, $dbname, $backup_file_name);
}

if (!$dumpCreated) {
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $reportbackup,
        'text' => "❌❌❌❌❌❌خطا در بکاپ گرفتن لطفا به پشتیبانی اطلاع دهید",
    ]);
    return;
}

if (!file_exists($backup_file_name) || filesize($backup_file_name) === 0) {
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $reportbackup,
        'text' => "❌ فایل بکاپ ایجاد شده خالی است. لطفا بررسی شود.",
    ]);

    if (file_exists($backup_file_name)) {
        unlink($backup_file_name);
    }

    return;
}

if (!class_exists('ZipArchive')) {
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $reportbackup,
        'text' => "❌ ماژول ZipArchive در هاست فعال نیست و امکان ارسال بکاپ وجود ندارد.",
    ]);
    return;
}

$zip = new ZipArchive();
if ($zip->open($zip_file_name, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zip->addFile($backup_file_name, basename($backup_file_name));
    $zip->close();

    if (!file_exists($zip_file_name) || filesize($zip_file_name) === 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reportbackup,
            'text' => "❌ فایل فشرده‌ی بکاپ ایجاد نشد یا خالی است.",
        ]);

        if (file_exists($zip_file_name)) {
            unlink($zip_file_name);
        }
        if (file_exists($backup_file_name)) {
            unlink($backup_file_name);
        }

        return;
    }

    telegram('sendDocument', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $reportbackup,
        'document' => new CURLFile($zip_file_name),
        'caption' => "<b>📦 خروجی دیتابیس ربات اصلی\n\n\nاین پروژه حاصل کلی وقت، تست و دیباگ‌ های زیاد بوده تا همه‌ چیز دقیق و بدون خطا کار کنه 💪\n\n💫 حمایتت با دنبال‌کردن ما توی <a href=\"https://t.me/LumeTeam\">@LumeTeam</a> باعث میشه با انرژی بیشتری روی بهبود و آپدیت‌های بعدی کار کنیم 😁</b>",
        'parse_mode' => 'HTML',
    ]);
    if (file_exists($zip_file_name)) {
        unlink($zip_file_name);
    }
    if (file_exists($backup_file_name)) {
        unlink($backup_file_name);
    }
}
