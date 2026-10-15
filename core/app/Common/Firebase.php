<?php

namespace App\Http\Common;

use App\Token;
use App\Model\User;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
use Illuminate\Support\Facades\Log;


class Firebase
{
    /**
     * MjavadAmirbeiki - 09902551512
     * @param string $title
     * @param string $body
     * @param string $sound
     * @param array $data
     * @param array $tokens
     * @return array
     */
    static function push($title = 'my title', $body = 'Hello world', $sound = 'default', $data = ['a_data' => 'my_data'], $tokens = [])
    {
        $optionBuilder = new OptionsBuilder();
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body)
            ->setSound($sound);
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($data);
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        $downstreamResponse = FCM::sendTo($tokens, null, $notification, $data);
        $result = [
            'number_success' => $downstreamResponse->numberSuccess(),
            'number_failure' => $downstreamResponse->numberFailure(),
            'number_modification' => $downstreamResponse->numberModification(),
        ];
        return $result;
//         return Array - you must remove all this tokens in your database
        $removeds = $downstreamResponse->tokensToDelete();
        foreach ($removeds as $removed) {
            $token = Token::where('token', $removed)->first();
            $token->delete();
        }
        // return Array (key:token, value:error) - in production you should remove from your database the tokens present in this array
//        $downstreamResponse->tokensWithError();
        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $olds = $downstreamResponse->tokensToModify();
        foreach ($olds as $old => $new) {
            $token = Token::where('token', $old)->first();
            $token->token = $new;
            $token->save();
        }
        // return Array - you should try to resend the message to the tokens in the array
//        $downstreamResponse->tokensToRetry();
        return $result;
    }

    /**
     * MjavadAmirbeiki - 09902551512
     * @param $user_id
     * @param $fcm_token
     * @return bool
     */
    static function TokenStore($user_id, $fcm_token)
    {
                 Log::info("fcm2...");
         Log::info($user_id);

        $user = User::findOrFail($user_id);
        Log::info("fcm3...");
         Log::info($user);
        $fcm = Token::where('token', $fcm_token)->first();
        if ($fcm && $user_id != $fcm->user_id) {
            $fcm->user_id = $user->id;
            $fcm->save();
        } elseif (!$fcm) {
            $new = new Token();
            $new->user_id = $user_id;
            $new->token = $fcm_token;
            $new->save();
        }
    }

    static function push_one($title = 'my title', $body = 'Hello world', $sound = 'default', $data = ['a_data' => 'my_data'], $token = null)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder('my title');
        $notificationBuilder->setBody('Hello world')
            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $token = "a_registration_from_your_database";

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);


        $result = [
            'number_success' => $downstreamResponse->numberSuccess(),
            'number_failure' => $downstreamResponse->numberFailure(),
            'number_modification' => $downstreamResponse->numberModification(),
        ];
        $removeds = $downstreamResponse->tokensToDelete();
        foreach ($removeds as $removed) {
            $token = Token::where('token', $removed)->first();
            $token->delete();
        }
        // return Array (key:token, value:error) - in production you should remove from your database the tokens present in this array
//        $downstreamResponse->tokensWithError();
        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $olds = $downstreamResponse->tokensToModify();
        foreach ($olds as $old => $new) {
            $token = Token::where('token', $old)->first();
            $token->token = $new;
            $token->save();
        }
        // return Array - you should try to resend the message to the tokens in the array
//        $downstreamResponse->tokensToRetry();
        return $result;    }
}