<?php

namespace App\Domain\Workspace\Mail;

use App\Domain\Workspace\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $workspaceName,
        public string $inviterName,
        public string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Voce foi convidado para o workspace {$this->workspaceName} no Zentra",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="margin:0;padding:0;background:#07090d;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <div style="max-width:520px;margin:40px auto;background:#161b22;border-radius:16px;border:1px solid #2d333b;overflow:hidden;">
                <div style="background:linear-gradient(135deg,#6366f1,#818cf8);padding:32px;text-align:center;">
                    <h1 style="margin:0;color:#fff;font-size:24px;font-weight:700;">Zentra</h1>
                </div>
                <div style="padding:32px;">
                    <h2 style="margin:0 0 8px;color:#e6edf3;font-size:20px;">Voce foi convidado!</h2>
                    <p style="color:#8b949e;font-size:14px;line-height:1.6;margin:0 0 24px;">
                        <strong style="color:#e6edf3;">{$this->inviterName}</strong> convidou voce para participar do workspace
                        <strong style="color:#e6edf3;">{$this->workspaceName}</strong> no Zentra.
                    </p>
                    <a href="{$this->acceptUrl}" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 32px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;">
                        Aceitar convite
                    </a>
                    <p style="color:#6e7681;font-size:12px;margin:24px 0 0;">
                        Este convite expira em 7 dias. Se voce nao esperava este email, pode ignora-lo.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
