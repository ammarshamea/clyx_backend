<?php

namespace App\Console\Commands;

use App\Mail\TaskNotificationMail;
use App\Services\TaskNotificationMailBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestTaskMailCommand extends Command
{
    protected $signature = 'clyx:test-task-mail {email : Recipient email address}';

    protected $description = 'Send a test task notification email (verify SMTP in .env)';

    public function handle(TaskNotificationMailBuilder $builder): int
    {
        $email = $this->argument('email');

        $this->info('Mailer: ' . config('mail.default'));
        $this->info('Host: ' . config('mail.mailers.smtp.host'));
        $this->info('From: ' . config('mail.from.address'));

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log — emails are only logged, not sent. Set MAIL_MAILER=smtp in .env');

            return self::FAILURE;
        }

        try {
            Mail::to($email)->send(new TaskNotificationMail(
                mailSubject: 'اختبار بريد Clyx',
                mailBody: "هذه رسالة تجريبية من نظام إدارة المهام.\nإذا وصلتك، إعداد SMTP على السيرفر صحيح.",
                eventLabel: $builder->eventLabel('task_assigned'),
                recipientName: 'مستخدم تجريبي',
                taskLink: config('clyx.frontend_url') . '/#/dashboard/staff',
            ));

            $this->info("Test email sent to {$email}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
