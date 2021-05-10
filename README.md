# Athos-Foundation
Athos-Foundation is a lightweight PHP library to help you easily set up a basic web application with features including:

- Session management
- Database management
- User authentication
- Template rendering

## Installation
You can download the source code directly, or install using composer:

`composer require mobles/athos-foundation`

### Smarty
Athos-Foundation utilizes Smarty to render templates. If you install Athos-Foundation through composer, it will automatically install Smarty, otherwise, you can manually install Smarty:

`composer require smarty/smarty "^3.1"`

## Setup
The `samples` directory contains a basic setup for your web app, including a demo module `home`. Copy these files and directory to the root of your web app and you should be good to go!

Additionally, `sample_database.sql` contains a sample database structure as used for user accounts and sessions.

Once you've set up your web application, you can remove the `samples` directory.

## License
Athos-Foundation is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
