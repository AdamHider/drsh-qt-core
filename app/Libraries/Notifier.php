<?php

namespace App\Libraries;

use Config\Services;

class Notifier
{
    protected $email;
    
    private $messages = [
        'user_registered' => [
            'subject' => 'Новая регистрация: ({name})',
            'body'    => 'Пользователь {name} ({username}) только что зарегистрировался.'
        ],
        'user_logged' => [
            'subject' => 'Новая вход: ({name})',
            'body'    => 'Пользователь {name} ({username}) только что вошёл снова.'
        ],
        

        'password_reset' => [
            'subject' => 'Сброс пароля',
            'body'    => 'Пользователь {email} запросил сброс пароля.'
        ],

        'quest_completed' => [
            'subject' => 'Квест завершён!',
            'body'    => 'Игрок {name} ({username}) завершил квест: {quest_title}.'
        ],
        
        'lesson_completed' => [
            'subject' => 'Планета исследована! ({name})',
            'body'    => 'Пользователь {name} завершил исследование планеты {title}.'
        ],
        'lesson_started' => [
            'subject' => 'Планета {title} исследуется! ({name})',
            'body'    => 'Пользователь {name} начал исследовать планету {title}.'
        ],
        'lesson_restarted' => [
            'subject' => '(Рестарт) Планета {title} исследуется заново! ({name})',
            'body'    => 'Пользователь {name} начал заново исследовать планету {title}.'
        ],
        
        
        'user_email_verification' => [
            'subject' => 'Подтверждение почты на Mektepium',
            'body'    => '{name}, для подтверждения почты перейдите по ссылке: https://app.mektepium.com/email-verification-{code}'
        ],
        
    ];
    
    public function __construct()
    {
        $this->email = Services::email();
    }

    public function send(string $code, string $to, array $params = [], array $options = []): bool
    {
        $template = $this->messages[$code] ?? null;

        if (!$template) {
            log_message('error', "Не найден шаблон уведомления: {$code}");
            return false;
        }

        $subject = $this->replacePlaceholders($template['subject'], $params);
        $body    = $this->replacePlaceholders($template['body'], $params);

        $fromEmail = $options['fromEmail'] ?? getenv('mail.fromEmail');
        $fromName  = $options['fromName'] ?? getenv('mail.fromName');

        $this->email->setFrom($fromEmail, $fromName);
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        return $this->email->send();
    }

    protected function replacePlaceholders(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace("{" . $key . "}", $value, $text);
        }
        return $text;
    }
}
