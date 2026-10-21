<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class BrevoTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new \RuntimeException('Brevo requiere un mensaje de correo válido.');
        }

        $sender = $email->getFrom()[0] ?? null;

        if (! $sender instanceof Address) {
            throw new \RuntimeException('El correo no tiene remitente.');
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

        $response = Http::withHeaders([
            'api-key' => config('services.brevo.key'),
            'accept' => 'application/json',
        ])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Brevo API error: HTTP ' . $response->status()
            );
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
