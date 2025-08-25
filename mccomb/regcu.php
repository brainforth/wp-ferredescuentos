<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

date_default_timezone_set('America/Mexico_City');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["message" => "Datos inválidos."]);
    exit;
}

$nombre = $input['nombre'] ?? '';
$apellidos = $input['apellidos'] ?? '';
$correo = $input['correo'] ?? '';
$telefono = $input['telefono'] ?? '';
$recaptchaToken = $input['recaptchaToken'] ?? '';

if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre) || strlen($nombre) > 52 ||
    !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellidos) || strlen($apellidos) > 52 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 52 ||
    !preg_match('/^\d+$/', $telefono) || strlen($telefono) > 15) {
    http_response_code(400);
    echo json_encode(["message" => "Datos no válidos."]);
    exit;
}

$secretKey = '6LdtGr8qAAAAABY7vlr1BaXNHNMKkDqpWtRNo6kz';
$recaptchaResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaToken");
$recaptchaData = json_decode($recaptchaResponse, true);

if (!$recaptchaData['success']) {
    http_response_code(400);
    echo json_encode(["message" => "reCAPTCHA no válido."]);
    exit;
}

try {
    $db = new PDO('mysql:host=journeay.iad1-mysql-e2-12b.dreamhost.com;dbname=w049g8jh', 'neg09482utnimg', 'vcfb!DXn@9b3v$C#');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT COUNT(*) FROM chimps WHERE correo = :correo");
    $stmt->bindParam(':correo', $correo);
    $stmt->execute();
    $emailCount = $stmt->fetchColumn();

    if ($emailCount > 0) {
        $isEmailInDb = true;
    } else {
        $isEmailInDb = false;
        $ip = $_SERVER['REMOTE_ADDR'];
        $fecha = date('dmYHis');

        $stmt = $db->prepare("INSERT INTO chimps (nombre, apellido, correo, telefono, fecha, ip) VALUES (:nombre, :apellido, :correo, :telefono, :fecha, :ip)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellidos);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al conectar con la base de datos: " . $e->getMessage()]);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'contacto@ferredescuentos.com';
    $mail->Password = 'bzqi chdq fwex gati';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('contacto@ferredescuentos.com', 'Ferredescuentos.com');
    $mail->addAddress('contacto@ferredescuentos.com');
    $mail->addAddress('yearim@ioncom.com.mx');
    $mail->isHTML(true);
    $mail->Subject = 'Nuevo registro | LP Combos';
    $mail->CharSet = 'UTF-8';
    $mail->Body = "
        <h1>Nuevo registro</h1>
        <p><strong>Nombre:</strong> $nombre</p>
        <p><strong>Apellidos:</strong> $apellidos</p>
        <p><strong>Correo:</strong> $correo</p>
        <p><strong>Teléfono:</strong> $telefono</p>
    ";

    $mail->send();

    $mail->clearAddresses();
    $mail->addAddress($correo);
    $mail->Subject = 'Gracias por registrarte | Ferredescuentos.com';
    $mail->CharSet = 'UTF-8';
    $mail->Body = "
        <div style='background-color:#f0f0f0;border-radius:10px;padding: 60px 40px;max-width:540px;margin:0 auto;min-height: 475px;box-sizing: border-box;'>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='text-align: center;'>
                <tr>
                    <td style='padding-bottom: 15px;'>
                        <img src='https://ferredescuentos.com/wp-content/uploads/2024/01/LogoRecurso-46@300x-1024x168-402x66.png' alt='Logo' width='200'>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h2 style='color:#333;font-size: 24px;line-height: 100%;margin-bottom: 0;'>¡Gracias por registrarte, $nombre!</h2>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style='color:#555;font-size: 19px;'>En tu primera compra recibirás un regalo sorpresa.</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style='color:#555;font-size: 19px;'>Somos una tienda en línea creada pensando en ti, para que tengas todas las herramientas que necesitas en tu trabajo diario ¡en funcionales combos! que consideran:</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='text-align: center;'>
                            <tr>
                                <td style='padding: 10px;'>
                                    <img src='https://ferredescuentos.com/mccomb/marcas.png' width='auto' height='56'>
                                    <p style='font-weight:bold;font-size:19px;margin-top: 0;'>Las mejores marcas</p>
                                </td>
                                <td style='padding: 10px;'>
                                    <img src='https://ferredescuentos.com/mccomb/precios.png' width='auto' height='56'>
                                    <p style='font-weight:bold;font-size:19px;margin-top: 0;'>A los mejores precios</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style='color:#555;font-size: 21px;margin-bottom: 0;'>¡Recuerda elegir tus mejores combos en <a href='https://ferredescuentos.com' target='_blank' style='color:#000;text-decoration:none;'><span style='font-weight:bold;color:#000;'>ferredescuentos.com</span></a>!</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style='font-size:15px;margin-top:60px;font-weight:bold;'>Síguenos en:</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' align='center'>
                            <tr>
                                <td style='padding-right: 11px;'>
                                    <a href='https://www.facebook.com/Ferredescuentosmex' target='_blank'>
                                        <img src='https://ferredescuentos.com/mccomb/facebook.png' height='33'>
                                    </a>
                                </td>
                                <td style='padding-right: 11px;'>
                                    <a href='https://www.instagram.com/ferredescuentosmex' target='_blank'>
                                        <img src='https://ferredescuentos.com/mccomb/instagram.png' height='33'>
                                    </a>
                                </td>
                                <td>
                                    <a href='https://www.tiktok.com/@ferredescuentos' target='_blank'>
                                        <img src='https://ferredescuentos.com/mccomb/tiktok.png' height='33'>
                                    </a>
                                </td>
                                <td style='padding-right: 11px;'>
                                    <a href='https://www.youtube.com/@Ferredescuentos' target='_blank'>
                                        <img src='https://ferredescuentos.com/mccomb/youtube.png' height='33'>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    ";

    $mail->send();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al enviar el correo: " . $mail->ErrorInfo]);
    exit;
}

$api_key = "28fd4678befa132d3890b5eb3be34ddf-us17";
$list_id = "6de73f5441";
$server_prefix = "us17";

$member_id = md5(strtolower($correo));
$url = "https://$server_prefix.api.mailchimp.com/3.0/lists/$list_id/members/$member_id";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, "user:$api_key");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    
    if ($data['status'] === 'subscribed') {
        http_response_code(400);
        echo json_encode(["message" => "Este usuario ya está registrado."]);
        exit;
    }

    if (in_array($data['status'], ['archived', 'unsubscribed'])) {
        $data['status'] = 'subscribed';
        $data['merge_fields'] = [
            "FNAME" => $nombre,
            "LNAME" => $apellidos,
            "PHONE" => $telefono,
        ];

        $json_data = json_encode($data);

        $ch = curl_init("https://$server_prefix.api.mailchimp.com/3.0/lists/$list_id/members/$member_id");
        curl_setopt($ch, CURLOPT_USERPWD, "user:$api_key");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            http_response_code(200);
            echo json_encode(["message" => "Tu suscripción ha sido reactivada."]);
        } else {
            $response_data = json_decode($response, true);
            http_response_code(500);
            echo json_encode(["message" => $response_data['detail'] ?? "Error al actualizar el registro en Mailchimp."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Este usuario tiene un estado desconocido en Mailchimp."]);
        exit;
    }
} else {
    $data = [
        "email_address" => $correo,
        "status" => "subscribed",
        "merge_fields" => [
            "FNAME" => $nombre,
            "LNAME" => $apellidos,
            "PHONE" => $telefono,
        ],
    ];

    $json_data = json_encode($data);

    $ch = curl_init("https://$server_prefix.api.mailchimp.com/3.0/lists/$list_id/members/");
    curl_setopt($ch, CURLOPT_USERPWD, "user:$api_key");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        http_response_code(200);
        echo json_encode(["message" => "Pronto comenzarás a recibir nuestras promociones."]);
    } else {
        $response_data = json_decode($response, true);
        http_response_code(500);
        echo json_encode(["message" => $response_data['detail'] ?? "Error al registrar en Mailchimp."]);
    }
}
?>