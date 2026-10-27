<?php

namespace App\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Envoi des mails du site par un serveur SMTP, avec la bibliothèque PHPMailer.
// En local : Mailtrap (les mails sont captés, personne ne les reçoit vraiment).
// En production : le SMTP de Gmail. Seul le fichier .env change.
class Mailer
{
    // Envoie un mail en texte brut. Renvoie true si le serveur SMTP l'a accepté, false sinon :
    // l'erreur est alors journalisée, et la page continue de s'afficher normalement.
    public function envoyer(string $destinataire, string $sujet, string $texte): bool
    {
        // true : PHPMailer signale ses erreurs par des exceptions
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->Port = (int) $_ENV['MAIL_PORT'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            // STARTTLS : la connexion avec le serveur SMTP est chiffrée (port 587)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            // 10 secondes au plus : si le serveur ne répond pas, la page ne reste pas bloquée
            $mail->Timeout = 10;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($destinataire);
            $mail->Subject = $sujet;
            $mail->Body = $texte;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // ErrorInfo décrit l'erreur sans recopier le mot de passe SMTP
            error_log('Échec de l\'envoi d\'un mail : ' . $mail->ErrorInfo);
            return false;
        }
    }
}
