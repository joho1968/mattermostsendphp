## mattermostsendphp
Simple CLI utility for posting text to a Mattermost Incoming Webhook, written in PHP.

## Usage

`mmsend mmsend [<section>|none] [test|send] [<url>|config] [<text>]`

## Background

Simply to start building (yet another) PHP codebase for talking to Mattermost :)

## Installation

Put mmsend.php and mmsend.sample.ini somewhere, in the same place. Copy mmsend.sample.ini to mmsend.ini. Edit as needed. Set execute rights on mmsend.php (not required if you manually use PHP CLI binary), and off you go. Oh, you need to create an Incoming Webhook in your Mattermost as well.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
