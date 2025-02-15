<?php

namespace App\Notifications;

use App\Channels\SmsMessage;
use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUser extends Notification {
	use Queueable;

	private $invite;
	private $template;
	private $replace = [];

	/**
	 * Create a new notification instance.
	 *
	 * @return void
	 */
	public function __construct($invite) {
		$this->invite = $invite;
		$this->template = EmailTemplate::where('slug', 'INVITE_USER')->first();
		Overrider::loadBusinessSettings($this->invite->business_id);

		$this->replace['businessName'] = $this->invite->business->name;
		$this->replace['roleName'] = $this->invite->role->name;
		$this->replace['message'] = $this->invite->message;
		$this->replace['actionUrl'] = route('system_users.accept_invitation', encrypt($this->invite->id));
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function via($notifiable) {
		$channels = [];
		if ($this->template != null && $this->template->email_status == 1) {
			array_push($channels, 'mail');
		}
		if ($this->template != null && $this->template->sms_status == 1) {
			array_push($channels, \App\Channels\SMS::class);
		}
		if ($this->template != null && $this->template->notification_status == 1) {
			array_push($channels, 'database');
		}
		return $channels;
	}

	/**
	 * Get the mail representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return \Illuminate\Notifications\Messages\MailMessage
	 */
	public function toMail($notifiable) {
		$message = processShortCode($this->template->email_body, $this->replace);

		return (new MailMessage)
			->subject($this->template->subject)
			->markdown('email.notification-business', ['message' => $message]);
	}

	/**
	 * Get the sms representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return \Illuminate\Notifications\Messages\MailMessage
	 */
	public function toSMS($notifiable) {
		$message = processShortCode($this->template->sms_body, $this->replace);

		return (new SmsMessage())
			->setContent($message)
			->setRecipient($notifiable->country_code . $notifiable->phone);
	}

	/**
	 * Get the array representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function toArray($notifiable) {
		$message = processShortCode($this->template->notification_body, $this->replace);
		return ['message' => $message];
	}
}