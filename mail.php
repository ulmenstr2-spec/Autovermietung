<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Anfragen erlaubt.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['name'], $data['email'], $data['pdf_base64'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fehlende Felder: name, email, pdf_base64 erforderlich.']);
    exit;
}

$name      = trim(strip_tags($data['name']));
$email     = trim($data['email']);
$pdf_b64   = $data['pdf_base64'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungueltige E-Mail-Adresse.']);
    exit;
}

// Strip data URI prefix if present
if (strpos($pdf_b64, ',') !== false) {
    $pdf_b64 = explode(',', $pdf_b64, 2)[1];
}

$pdf_bytes = base64_decode($pdf_b64);
if ($pdf_bytes === false || strlen($pdf_bytes) < 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungueltige PDF-Daten.']);
    exit;
}

$safe_name    = preg_replace('/[^a-z0-9\-]/i', '-', $name);
$filename     = 'mietvertrag-' . strtolower($safe_name) . '.pdf';
$from_address = 'noreply@rostock-auto-mieten.de';
$owner_email  = 'josefczerwi@t-online.de';
$date_str     = date('d.m.Y H:i');

// --- Build MIME message helper ---
function buildMimeMail($to, $from, $subject, $body_text, $pdf_bytes, $filename) {
    $boundary = '----=_Part_' . md5(uniqid(rand(), true));

    $headers  = "From: $from\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $body_text . "\r\n\r\n";

    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"$filename\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdf_bytes)) . "\r\n";
    $body .= "--$boundary--";

    return [$headers, $body];
}

// --- Mail 1: To Josef ---
$subject_owner = "Neuer Mietvertrag - $name";
$body_owner = "Guten Tag Josef,\n\n"
    . "ein neuer Mietvertrag wurde online ausgefuellt und unterschrieben.\n\n"
    . "Name des Mieters : $name\n"
    . "E-Mail           : $email\n"
    . "Eingegangen am   : $date_str Uhr\n\n"
    . "Der unterzeichnete Mietvertrag ist als PDF-Anhang beigefuegt.\n\n"
    . "Mit freundlichen Gruessen\nrostock-auto-mieten.de";

[$headers_owner, $body_mail_owner] = buildMimeMail(
    $owner_email, $from_address, $subject_owner, $body_owner, $pdf_bytes, $filename
);

$sent1 = mail($owner_email, $subject_owner, $body_mail_owner, $headers_owner);

// --- Mail 2: Confirmation to customer ---
$subject_customer = "Ihr Mietvertrag - rostock-auto-mieten.de";
$body_customer = "Guten Tag $name,\n\n"
    . "vielen Dank fuer Ihre Anfrage bei rostock-auto-mieten.de.\n\n"
    . "Ihr unterschriebener Mietvertrag ist als PDF-Anhang beigefuegt. Bitte pruefen Sie die Angaben.\n\n"
    . "Bei Fragen stehe ich Ihnen gerne zur Verfuegung:\n"
    . "  Josef Czerwinski\n"
    . "  Tel: +49 176 75192451\n"
    . "  E-Mail: josefczerwi@t-online.de\n"
    . "  Ulmenstrasse 2, 18057 Rostock\n\n"
    . "Mit freundlichen Gruessen\nJosef Czerwinski\nrostock-auto-mieten.de";

[$headers_customer, $body_mail_customer] = buildMimeMail(
    $email, $from_address, $subject_customer, $body_customer, $pdf_bytes, $filename
);

$sent2 = mail($email, $subject_customer, $body_mail_customer, $headers_customer);

if ($sent1 && $sent2) {
    echo json_encode(['success' => true]);
} elseif ($sent1) {
    echo json_encode(['success' => true, 'warning' => 'Bestaetigung an Kunden konnte nicht gesendet werden.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'E-Mail konnte nicht gesendet werden. Bitte kontaktieren Sie Josef direkt: josefczerwi@t-online.de']);
}
