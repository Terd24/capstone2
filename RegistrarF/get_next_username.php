<?php
session_start();
header('Content-Type: application/json');

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$domain = 'student.cci.edu.ph';

function letters_only($s) {
    return strtolower(preg_replace('/[^a-z]/i', '', (string)$s));
}
function digits_only($s) {
    return preg_replace('/\D+/', '', (string)$s);
}

try {
    // Inputs: last_name and either last6 or id_number
    $lastName = isset($_GET['last_name']) ? $_GET['last_name'] : ($_POST['last_name'] ?? '');
    $last6    = isset($_GET['last6']) ? $_GET['last6'] : ($_POST['last6'] ?? '');
    $idNumber = isset($_GET['id_number']) ? $_GET['id_number'] : ($_POST['id_number'] ?? '');

    $lastName = letters_only($lastName);
    $last6 = digits_only($last6);
    $idNumber = digits_only($idNumber);

    if ($last6 === '' && $idNumber !== '') {
        $last6 = substr($idNumber, -6);
    }

    if ($lastName === '' || strlen($last6) !== 6) {
        echo json_encode(['success' => false, 'error' => 'Missing last name or last6 digits']);
        exit;
    }

    $username = sprintf('%s%smuzon@%s', $lastName, $last6, $domain);
    echo json_encode(['success' => true, 'username' => $username]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
