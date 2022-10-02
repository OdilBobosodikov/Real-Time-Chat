<?php

require 'vendor/autoload.php';

use Pubnub\Pubnub;

$pubnub = new Pubnub(
    'pub-c-42baef6d-5af6-4196-a093-27d426826030',
    'sub-c-dc218e54-e106-4888-a7e3-c222d06e02f1',
    'sec-c-NjEwNWE4MzktMmFmNC00YjgwLWE5NTAtN2FkNmQ0YWU1YTUw',
    false
);

fwrite(STDOUT, 'Join ROOM: ');
$room = trim(fgets(STDIN));

$herenow = $pubnub->hereNow($room, false, true);
function connectAs()
{
    global $herenow;

    fwrite(STDOUT, "Connect as: ");

    $username = trim(fgets(STDIN));

    foreach($herenow['uuids'] as $user){
        if($user['state']['username'] === $username){
            fwrite(STDOUT, "Username taken\n");
            $username = connectAs();
            }
        }

    return $username;
};

$username = connectAs();



$pubnub->setState($room, ['username' => $username]);

fwrite(STDOUT, "\nConnected to {$room} as {$username}\n");

$pid = pcntl_fork();

if($pid == -1){
    exit(1);
}
elseif($pid){
    fwrite(STDOUT, "> ");

    while(true){
        $message = trim(fgets(STDIN));
        $pubnub->publish($room, [
            "body" => $message,
            "username" => $username
        ]);
    }

    
pcntl_wait($status);
}
else{
    $pubnub->subscribe($room, function($payload) use ($username){
        $timestamp = date('d-m-y H:i:s');

        if($username != $payload['message']['username'])
        {
            fwrite(STDOUT, "\r");
        }

        fwrite(STDOUT, "[{$timestamp}] <{$payload['message']['username']}> {$payload['message']['body']}\n");
        fwrite(STDOUT, "\r> ");
        return true;
    });
}
