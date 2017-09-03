<form method="post">
<input type="submit" value="Am I Subcriber?">
</form>

<?php
require_once('vendor/autoload.php');
require_once('config.php');

session_start();

use TeamSpeak3\TeamSpeak3;
use TwitchApi\TwitchApi;
use Config\Config;
use Config\TSConfig;
use Database\Database;

if (isset($_SESSION['streamer'])) {
    echo 'Yikes, I am a streamer!';

    $twitch = new TwitchApi([
        'client_id' => Config::client_id,
        'client_secret' => Config::client_secret,
        'redirect_uri' => Config::redirect_uri,
        'scope' => ['channel_subscriptions']
    ]);
} else {
    $twitch = new TwitchApi([
        'client_id' => Config::client_id,
        'client_secret' => Config::client_secret,
        'redirect_uri' => Config::redirect_uri,
        'scope' => ['user_subscriptions']
    ]);
}

if (DEBUG) {
    if (isset($_GET['nick'])) {
        echo ($_GET['nick'] . "'s ID: " . $twitch->getUserByUsername($_GET['nick'])['users'][0]['_id']);
    }
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = Helper::getRandomToken();
    $_SESSION['state'] = $code;
    header("Location: " . $twitch->getAuthenticationUrl($code, true));
}

if (isset($_GET['code']) && isset($_GET['state']) && isset($_SESSION['state']) && $_GET['state'] == $_SESSION['state']) {
    $_SESSION['state'] = null;
    if (isset($_SESSION['streamer'])) {
        $oauth = $twitch->getAccessCredentials($_GET['code'])['access_token'];
        $sub = $twitch->getChannelSubscribers(Config::streamer_id, $oauth, 1, 0);
        $count = $sub['_total'];
        $subs = [];
        for ($i = 0; $i < ceil($count / 100); $i++) {
            $sub_data = $twitch->getChannelSubscribers(Config::streamer_id, $oauth, 100, $i);
            foreach ($sub_data['subscriptions'] as $sub_client) {
                $subs[$sub_client['user']['_id']] = $sub_client['user']['_id'];
            }
        }
        $db = new Database();
        $subs_db = $db->getAll();
        $ts3_VirtualServer = TeamSpeak3::factory("serverquery://". TSConfig::LOGIN .":" . TSConfig::PASSWORD . "@" . TSConfig::IP . ":". TSConfig::QPORT . "/?server_port=" . TSConfig::SPORT);
        $ts3_VirtualServer->selfUpdate(['client_nickname' => TSConfig::Name . rand(0, 100)]);
        foreach ($subs_db as $sub) {
            if (!array_key_exists($sub['id'], $subs)) {
                try {
                    $ts3_VirtualServer->serverGroupGetById(TSConfig::server_group)->clientDel($sub['uid']);
                    $db->delete($sub['id']);
                } catch (Exception $ex) {
                    echo 'Something went wrong with deletion! Please try again later';
                }
            }
        }
        return;
    }
    $arr = $twitch->getAccessCredentials($_GET['code']);
    $id = $twitch->validateAccessToken($arr['access_token'])['token']['user_id'];
    $response = $twitch->checkUserSubscriptionToChannel($id, Config::streamer_id, $arr['access_token']);
    if ($id == Config::streamer_id) {
        $_SESSION['streamer'] = true;
        header('Location: ' . Config::redirect_uri);
    }
    if (isset($response['error'])) {
        echo 'Error ' . $response['status'] . ': ' . $response['message'];
    } else {
        $db = new Database();
        if (!$db->get($id)) {
            $ts3_VirtualServer = TeamSpeak3::factory("serverquery://". TSConfig::LOGIN .":" . TSConfig::PASSWORD . "@" . TSConfig::IP . ":". TSConfig::QPORT . "/?server_port=" . TSConfig::SPORT);
            $ts3_VirtualServer->selfUpdate(['client_nickname' => TSConfig::Name . rand(0, 100)]);
            $clientList = $ts3_VirtualServer->clientList(['connection_client_ip' => $_SERVER['REMOTE_ADDR']]);
            foreach ($clientList as $client) {
                if ($client['client_type'] == 1)
                    continue;

                try {
                    $client = reset($clientList);
                    $client->addServerGroup(TSConfig::server_group);
                    $db->add($id, $client['client_database_id']);
                    echo 'You have been successfully added to the Subscriber group!';
                } catch (\Exception $ex) {
                    if ($ex->getCode() == 2561) {
                            $db->add($id, $client['client_database_id']);
                            echo 'You already have a subscriber icon!';
                            return;
                    }
                    echo 'Error ' . $ex->getCode() . ': ' . $ex->getMessage();
                }
                break;
            }
        } else {
            echo 'You already have a subscriber icon!';
        }
    }
}

class Helper
{
    public static function getRandomToken()
    {
        return sha1(md5(rand(-10000000, 100000000) . rand(-5000000, 5000000))) . chr(rand(33, 122));
    }
}

//MADE BY NAJSR :)
