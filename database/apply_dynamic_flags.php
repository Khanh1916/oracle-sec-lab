#!/usr/bin/env php
<?php
/**
 * OracleSecLab - per-instance (dynamic) flag generator.
 *
 * Purpose: give every running instance its own flags, so sharing a repo or a
 * container image does not leak the answers and teams in a CTF cannot copy each
 * other. The challenge MECHANICS are unchanged - only the flag VALUES differ.
 *
 * Behaviour:
 *   - Off by default. Nothing happens unless RANDOMIZE_FLAGS=1 (or true).
 *   - On first activation it generates three flags, rewrites the Challenge 1
 *     fragments in the database (hex / reversed / base64, exactly as seeded),
 *     stores the Challenge 2 flag and the Challenge 3 halves in CONFIG_STORE,
 *     and rewrites the baseline partner manifest's flag_part.
 *   - Generation runs once; the values persist in the database (and therefore
 *     survive restarts and container recreation while the volume lives).
 *   - On every boot it prints shell `export` lines for the values that live in
 *     PHP source (FLAG2, FLAG3_SUFFIX_HEX) so the entrypoint can hand them to
 *     Apache. store.php / admin.php read them via getenv() with the original
 *     literals as fallback, so with randomization off they are unchanged.
 *
 * stdout: shell export lines ONLY (eval'd by entrypoint).
 * stderr: human-readable diagnostics.
 */

function note(string $m): void { fwrite(STDERR, "[dynamic-flags] $m\n"); }

$tns = sprintf(
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SERVICE_NAME=%s)))',
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_PORT') ?: '1539',
    getenv('DB_SERVICE') ?: 'XEPDB1'
);
$conn = @oci_connect(getenv('DB_USER') ?: 'dbs401_user', getenv('DB_PASS') ?: 'dbs401_pass', $tns, 'AL32UTF8');
if (!$conn) {
    note('database connection failed; leaving default flags in place');
    exit(0);
}

$manifestFile = getenv('PARTNER_MANIFEST') ?: '/var/www/partner-api/manifest.json';

function cfg_get($conn, string $key): ?string {
    $s = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = :k");
    oci_bind_by_name($s, ':k', $key);
    oci_execute($s);
    $r = oci_fetch_assoc($s);
    return $r ? (string)$r['CONFIG_VALUE'] : null;
}

function cfg_put($conn, string $key, string $val): void {
    $u = oci_parse($conn, "UPDATE CONFIG_STORE SET config_value = :v WHERE config_key = :k");
    oci_bind_by_name($u, ':v', $val);
    oci_bind_by_name($u, ':k', $key);
    oci_execute($u);
    if (oci_num_rows($u) === 0) {
        $i = oci_parse($conn, "INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES (:k, :v, 0)");
        oci_bind_by_name($i, ':k', $key);
        oci_bind_by_name($i, ':v', $val);
        oci_execute($i);
    }
}

function rand_token(int $len): string {
    // Unambiguous alnum only: no quotes, braces or look-alikes.
    $alphabet = 'abcdefghjkmnpqrstuvwxyzACDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

function write_manifest(string $file, string $flagPartHex): void {
    $data = ['version' => '3.0.5', 'status' => 'stable', 'checksum' => 'a8b9c1'];
    if (is_file($file)) {
        $existing = json_decode((string)file_get_contents($file), true);
        if (is_array($existing)) { $data = $existing; }
    }
    $data['flag_part'] = $flagPartHex;
    @file_put_contents($file, json_encode($data));
}

$want = getenv('RANDOMIZE_FLAGS');
$want = ($want === '1' || strtolower((string)$want) === 'true');
$done = cfg_get($conn, 'runtime_flags_done') === '1';

if ($want && !$done) {
    note('generating per-instance flags');

    // ---- Challenge 1: DBS401{...}, split across three tables/encodings ------
    $inner1 = rand_token(6) . '_' . rand_token(6) . '_' . rand_token(6);
    $flag1  = 'DBS401{' . $inner1 . '}';
    $L = strlen($inner1);
    $x = intdiv($L, 3);
    $y = intdiv(2 * $L, 3);
    $partA = 'DBS401{' . substr($inner1, 0, $x);   // -> hex in FLAGS
    $partB = substr($inner1, $x, $y - $x);          // -> reversed in AUDIT_LOGS
    $partC = substr($inner1, $y) . '}';             // -> base64 in CONFIG_STORE

    $u = oci_parse($conn, "UPDATE FLAGS SET flag_part = :v WHERE flag_code = 'FL1_PART_A'");
    $hexA = bin2hex($partA);
    oci_bind_by_name($u, ':v', $hexA);
    oci_execute($u);

    $meta = json_encode([
        'sys_version' => 'v2.1',
        'fragment'    => strrev($partB),
        'note'        => 'reverse_for_context',
        'ref'         => 'FLAG_COMPONENT_B',
    ]);
    $u = oci_parse($conn, "UPDATE AUDIT_LOGS SET metadata_note = :v WHERE action = 'SYSTEM_AUDIT_CHECK'");
    oci_bind_by_name($u, ':v', $meta);
    oci_execute($u);

    $b64C = base64_encode($partC);
    cfg_put($conn, 'sys_alpha_marker', $b64C);

    // ---- Challenge 2: DBS401{...}, shown by store.php ----------------------
    $flag2 = 'DBS401{' . rand_token(5) . '_' . rand_token(5) . '_' . rand_token(4) . '}';

    // ---- Challenge 3: DBS401{...}, manifest half + server suffix half -------
    $flag3 = 'DBS401{' . rand_token(6) . '_' . rand_token(8) . '}';
    $half  = intdiv(strlen($flag3), 2);
    $manifestHex = bin2hex(substr($flag3, 0, $half));
    $suffixHex   = bin2hex(substr($flag3, $half));

    // ---- Challenge 4: DBS401{...}, stored in PLSQL_VAULT --------------------
    // Read only via PL/SQL injection in FN_COURSE_LOOKUP, so it lives in the DB
    // and needs no environment plumbing. Update the CHALLENGE_4 row in place.
    $flag4 = 'DBS401{' . rand_token(6) . '_' . rand_token(7) . '}';
    $u = oci_parse($conn, "UPDATE PLSQL_VAULT SET secret_flag = :v WHERE label = 'CHALLENGE_4'");
    oci_bind_by_name($u, ':v', $flag4);
    oci_execute($u);

    cfg_put($conn, 'runtime_flag1', $flag1);
    cfg_put($conn, 'runtime_flag2', $flag2);
    cfg_put($conn, 'runtime_flag3', $flag3);
    cfg_put($conn, 'runtime_flag3_manifest_hex', $manifestHex);
    cfg_put($conn, 'runtime_flag3_suffix_hex', $suffixHex);
    cfg_put($conn, 'runtime_flag4', $flag4);
    cfg_put($conn, 'runtime_flags_done', '1');
    oci_commit($conn);
    note('flags generated and persisted');
}

// ---- Every boot: emit runtime values so Apache picks them up ---------------
$flag2 = cfg_get($conn, 'runtime_flag2');
$suffix = cfg_get($conn, 'runtime_flag3_suffix_hex');
$manifestHex = cfg_get($conn, 'runtime_flag3_manifest_hex');

if ($flag2 !== null && $flag2 !== '') {
    echo "export FLAG2='" . $flag2 . "'\n";
}
if ($suffix !== null && $suffix !== '') {
    echo "export FLAG3_SUFFIX_HEX='" . $suffix . "'\n";
}
if ($manifestHex !== null && $manifestHex !== '') {
    write_manifest($manifestFile, $manifestHex);
    note('baseline manifest flag_part updated for this instance');
}
