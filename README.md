# Twitch-TS3-Subscriber-bridge

Things you will need:

1) Your account id (set DEBUG to TRUE in index.php a call a script with $_GET['nick'] parameter. (For example http://localhost?nick=my_super_twitch_channel). Then paste it in config and set DEBUG to FALSE.

2) Client ID, Client Secret and Redirect URI; Obtain from https://www.twitch.tv/kraken/oauth2/clients/new

3) Own teamspeak server with permissions

4) SQLite3 php module

For stream owners: You need to log in twice to remove no longer subscribed people from TS3 Server
