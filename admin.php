<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$input = json_input();
$action = $input['action'] ?? '';
$db = read_db();

if ($action === 'create_post') {
    $type = trim((string)($input['type'] ?? 'Announcement'));
    $title = trim((string)($input['title'] ?? ''));
    $body = trim((string)($input['body'] ?? ''));

    if ($title === '' || $body === '') {
        respond(['ok' => false, 'error' => 'Title and message are required.'], 400);
    }

    $post = [
        'id' => 'post_' . bin2hex(random_bytes(4)),
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'date' => date('Y-m-d')
    ];
    array_unshift($db['posts'], $post);
    write_db($db);
    respond(['ok' => true, 'post' => $post], 201);
}

if ($action === 'delete_post') {
    $id = (string)($input['id'] ?? '');
    $db['posts'] = array_values(array_filter($db['posts'], fn($p) => $p['id'] !== $id));
    write_db($db);
    respond(['ok' => true]);
}

if ($action === 'member_status') {
    $id = (string)($input['id'] ?? '');
    $status = (string)($input['status'] ?? '');
    if (!in_array($status, ['verified', 'pending', 'suspended'], true)) {
        respond(['ok' => false, 'error' => 'Invalid member status.'], 400);
    }

    foreach ($db['users'] as &$u) {
        if ($u['id'] === $id && $u['role'] === 'member') {
            $u['status'] = $status;
            write_db($db);
            respond(['ok' => true, 'user' => sanitize_user($u)]);
        }
    }
    respond(['ok' => false, 'error' => 'Member not found.'], 404);
}

if ($action === 'delete_member') {
    $id = (string)($input['id'] ?? '');
    $db['users'] = array_values(array_filter($db['users'], fn($u) => !($u['id'] === $id && $u['role'] === 'member')));
    write_db($db);
    respond(['ok' => true]);
}

respond(['ok' => false, 'error' => 'Unsupported admin action.'], 400);
