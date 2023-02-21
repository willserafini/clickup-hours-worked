# Clickup Hours Worked
Get CSV files of the worked hours of your user using the Clickup Public API (https://clickup.com/api/).
The data that will show it's of the current month.

Steps:
1. Create your on .env file, you can copy the .env.example and put your credentials. To get your `TEAM_ID`, you can just check on the URL of your clickup workspace, for example: https://app.clickup.com/0004249/home, in this case, the TEAM_ID is **0004249**. To get the `TOKEN` you need to go to your APP Settings account and generate an token if you don't have one yet.

2. Run `composer install`
3. Run `php index.php`

You will se 2 CSV files on the hours folder.
