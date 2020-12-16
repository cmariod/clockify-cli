CLI (Command Line Interface) to simplify your life with clockify (online time tracker).

## Features
- Configuration wizard
- Get list of projects
- Insert time entry
- And more to come

## Requirements
- PHP 7.3+, open terminal and type `php -v` to check, this should be by default installed in MacOS X Catalina, or go and google on how to install on your environment ( windows / linux / older mac osx)
- Composer 1+, open terminal and type `composer --version` to check or visit [Install Instruction](https://getcomposer.org/download/) page

## Installation
- Download / Clone this repo and open that directory in terminal
- Install composer dependencies by executing `composer install`
- Find out your API Key
  - login to your clockify via browser
  - open [Settings Page](https://clockify.me/user/settings)
  - scroll all the way to the bottom and you'll see API Section
  - generate if you have none or copy if it's already there
- Configure credentials by executing `php clockify-cli configure` and follow instructions

## Usage

#### Get List of projects

execute `php clockify-cli list:projects` and either note down / copy / memorise the result

#### Import Time Entry

Prepare `import.csv` in the project root directory, csv file should at least contains header line: `description,projectId,start,end,lunchhour`. Refer to example file at `import-example.csv`.

data format:

- description: string
- projectId: string, taken from project list url
- start: datetime (YYYY-MM-DD HH:MM:SS) in local time
- end: datetime (YYYY-MM-DD HH:MM:SS) in local time
- lunchhour: (optional) datetime (YYYY-MM-DD HH:MM:SS) in local time. the start of lunch hour, leave it empty if no lunch in between time entry

once import.csv in place, execute `php clockify-cli time:import` and voila

## Framework

Built on top of laravel-zero, visit [laravel-zero.com](https://laravel-zero.com/) for documentation.

## License

Open-sourced under the MIT license.