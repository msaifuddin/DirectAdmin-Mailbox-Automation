# DirectAdmin Mailbox Automation
The PHP script is used to automate mailbox and forwarder creation using DirectAdmin API, intended for self-service account setup.

## Features

- Create mailboxes and assign random password with a simple form submission.
- Option to add email forwarding while creating a mailbox.
- Submission control using an Auth Key to prevent unauthorized usage.

## DirectAdmin API Calls

This script uses the following DirectAdmin API commands:

1. `CMD_API_POP`: Used for creating, deleting, and listing mailboxes.
2. `CMD_API_EMAIL_FORWARDERS`: Used for creating, deleting, and listing email forwarders.

Please ensure that these API commands are allowed for the DirectAdmin login key used in the script.

## Usage

1. Open `create_email.php` and replace the placeholders with your respective values:
    - `<LOGINKEY>`: Your DirectAdmin login key.
    - `<ADMIN>`: Your DirectAdmin username.
    - `<AUTHKEY>`: The authorization code you want to use to secure the script.
    - `https://admin.dapanel.tld:2222`: Your DirectAdmin URL.
2. Update the `$allowed_domains` array with the domains you want to allow for email creation.
3. Change the other mailbox properties to fit your environment.
    - `"quota" => 100"`: Sets the mailbox's quota in MB. In this case, the quota is set to 100 MB.
    - `"limit" => 30"`: Sets the hourly send limit for the mailbox. In this case, the limit is set to 30 emails per hour.

## License

This project is licensed under the MIT License. See the `LICENSE` file for more details.
