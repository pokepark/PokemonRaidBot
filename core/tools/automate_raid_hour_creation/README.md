#### What is this
To make life easier for admins, you can automate raid hour creation for every week with these scripts. They work almost every time 8-)

#### What does it do
raid_hour_creator.php creates raid hour raids in bot's db by default every wednesday.
If there are some other raid hours scheduled for different days in https://raw.githubusercontent.com/ccev/pogoinfo/v2/active/events.json, they are also added.

share_event_raids.php shares unshared raid hour polls, that are scheduled for that day, to the one chat specified in config.

#### How do I make it do stuff
Create the config file from example file and fill in the stuff

`cp config.json.example config.json`

Somehow automate the running of both files, for example with cron

`0 9 * * * php raid_hour_creator.php; php share_event_raids.php`