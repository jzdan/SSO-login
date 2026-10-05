<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Link verifikasi untuk salah satu email pengguna (email Google atau email BPS).
 * Hash alamat email ikut ditandatangani, jadi link otomatis tidak berlaku bila email diganti.
 */
class VerifyEmailAddress extends Notification
{
    public const EXPIRE_MINUTES = 60;

    public function __construct(public User $user, public string $column)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(self::EXPIRE_MINUTES), [
            'user' => $this->user->id,
            'type' => $this->column,
            'hash' => sha1($this->user->{$this->column}),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->column === 'email_bps' ? 'email BPS' : 'email';

        return (new MailMessage)
            ->subject('Verifikasi '.$label.' akun '.config('app.name'))
            ->greeting('Halo '.$this->user->name.',')
            ->line('Klik tombol di bawah untuk memverifikasi '.$label.' '.$this->user->{$this->column}.'.')
            ->line('Akun hanya bisa dipakai login setelah email diverifikasi.')
            ->action('Verifikasi Email', $this->verificationUrl())
            ->line('Link ini berlaku '.self::EXPIRE_MINUTES.' menit. Abaikan email ini jika Anda tidak merasa membuat atau mengubah akun.');
    }
}
