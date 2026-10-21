<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class BrevoTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new \RuntimeException(
                'Brevo requiere un mensaje de correo válido.'
            );
        }

        $sender = $email->getFrom()[0] ?? null;

        if (! $sender instanceof Address) {
            throw new \RuntimeException(
                'El correo no tiene remitente.'
            );
        }

        $recipients = array_map(
            fn (Address $address) => [
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ],
            $message->getEnvelope()->getRecipients()
        );

        $payload = [
            'sender' => [
                'email' => $sender->getAddress(),
                'name' => $sender->getName(),
            ],
            'to' => $recipients,
            'subject' => $email->getSubject() ?? '',
        ];

        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        }

        if ($email->getTextBody() !== null) {
            $payload['textContent'] = $email->getTextBody();
        }

        $apiKey = config('services.brevo.key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new \RuntimeException(
                'BREVO_API_KEY no está configurada.'
            );
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
        ])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            $errorCode = $response->json('code');
            $errorMessage = $response->json('message');

            throw new \RuntimeException(
                'Brevo API error: HTTP ' . $response->status()
                . ' | Code: ' . (
                    is_string($errorCode) ? $errorCode : 'unknown'
                )
                . ' | Message: ' . (
                    is_string($errorMessage) ? $errorMessage : 'unknown'
                )
            );
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
