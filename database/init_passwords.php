#!/usr/bin/env php
<?php
/**
 * DBS401 - Group 02
 * init_passwords.php
 * Run ONCE after importing schema.sql and seed.sql.
 * Updates password_hash for all demo users using PHP password_hash().
 *
 * Usage: php init_passwords.php
 */

$tns = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XE)))';
$dbUser = 'dbs401_user';
$dbPass = 'dbs401_pass';

$conn = oci_connect($dbUser, $dbPass, $tns, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message'] . "\n");
}

$accounts = [
    'admin'    => 'Admin@DBS401!2024',
    'teacher1' => 'Teacher@123',
    'student1' => 'Student@123',
    'student2' => 'Student@123',
    'student3' => 'Student@123',
];

foreach ($accounts as $username => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $sql  = "UPDATE USERS SET password_hash = :h WHERE username = :u";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':h', $hash);
    oci_bind_by_name($stmt, ':u', $username);
    oci_execute($stmt);
    echo "Updated password for: $username\n";
}

oci_commit($conn);
oci_close($conn);
echo "Done. All passwords updated.\n";
