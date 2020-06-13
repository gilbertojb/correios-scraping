# 🤖️ Correios Scraping

## Installation

Clone this repository
```bash
$ git clone git@github.com:TiendaNube/correios-scraping.git
```

Install the dependencies
```bash
$ composer install
```

## Commands Available

### Extract Postcode Tracks
Extract data from all states:
```bash
$ bin/console tracks:extract
```

Set the output file format: Default: `json`.
```bash
$ bin/console tracks:extract -o csv
```

Extract data from all states:
```bash
$ bin/console tracks:extract -s SP
```

#### Demo
![Demo](https://github.com/tiendanube/correios-scraping/blob/master/docs/assets/extract_from_all.gif)

## Create new commands

Create a new class for your command:
```php
<?php

namespace Correios\Scraper\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class YourNewCommand extends Command
{
    protected static $defaultName = 'command_name_here';

    protected function configure()
    {
        // Put the command configuration here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Put your logic here

        return Command::SUCCESS;
    }
}
```

Add the new command to the application. Edit the `bin/console` file;
````php
<?php

use Correios\Scraper\Command\YourNewCommand;

// ...

$application->add(new YourNewCommand());
````

## License

The gem is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
