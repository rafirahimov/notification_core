<?php

namespace Modules\Mail\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Modules\Core\Traits\ApiResponse;
use Modules\Mail\Requests\MailRequest;
use Modules\Notification\Services\KafkaService;

class MailController
{

    use ApiResponse;

    public function __construct(protected KafkaService $kafkaService)
    {}

    public function sendAllUser(Request $request)
    {
        //
    }

    public function sendUser($userId, MailRequest $request): JsonResponse
    {

        $user = DB::table('people.person')->where('id', $userId)->first();

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $mailId = 'mail_' . Str::ulid();

        $sendMailData = [
            'mail_id' => $mailId,
            'sender' => [
                'name' => 'Support',
                'email' => 'info@tofun.app',
            ],
            'to' => [
                [
                    'email' => $user->email,
                    'name' => $user->fname . ' ' . $user->lname,
                ],
            ],
            'subject' => $request->title,
            'htmlContent' => '<html><head></head><body><p>Hello ' . $user->fname . ',</p><p>' . $request->body . '</p></body></html>',
        ];


        $sent = $this->kafkaService->sendMail($sendMailData);

        if (!$sent) {
            return $this->error('Failed to queue push notification', 500);
        }

        return response()->json([
            'mail_id' => $mailId,
        ], 202);

    }

}