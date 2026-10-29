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
        if ($this->estAdresseDeDemoEnProduction($destinataire)) {
            error_log('domaine réservé example.com, aucun envoi');
            return false;
        }

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

    // true si on est en production et que l'adresse est en @example.com (comptes de démo).
    // Les mails et pseudos des comptes de démo sont publics (manuel d'utilisation), et ce domaine
    // est réservé : personne ne reçoit ces mails. Gmail accepterait le mail puis le renverrait
    // plus tard ; « mot de passe oublié » changerait alors le mot de passe d'un compte de démo
    // sans que personne ne le reçoive. En local, Mailtrap capte tout : rien n'est bloqué.
    private function estAdresseDeDemoEnProduction(string $destinataire): bool
    {
        // Même règle que le reste du site : tout ce qui n'est pas « dev » est la production
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            return false;
        }

        return str_ends_with(mb_strtolower($destinataire), '@example.com');
    }
}
