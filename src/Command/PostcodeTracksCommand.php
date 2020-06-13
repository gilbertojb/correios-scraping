<?php

namespace Correios\Scraper\Command;

use Correios\Scraper\Util\SpinnerProgress;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class PostcodeTracksCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'tracks:extract';

    /** @var string */
    protected static $url = 'http://www.buscacep.correios.com.br/sistemas/buscacep/ResultadoBuscaFaixaCEP.cfm';

    /** @var array */
    protected static $outputFormats = ['json', 'csv'];

    protected function configure()
    {
        $this->setDescription('Extract the postcode tracks based a given state')
             ->addOption(
                 'states',
                 's',
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'States to extract the tracks',
                 [
                     'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
                     'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'
                 ]
             )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Generate file as a given output format',
                'json'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $statesOption = $input->getOption('states');
        $outputOption = $input->getOption('output');

        if (false === in_array($outputOption, self::$outputFormats)) {
            $io->error("The \"{$outputOption}\" output format is not available!");
            return Command::FAILURE;
        }

        $browser = new HttpBrowser(HttpClient::create());

        shuffle($statesOption);

        foreach ($statesOption as $state) {
            $spinner = new SpinnerProgress($output);
            $spinner->setMessage("Extracting data from {$state}");

            $spinner->start();

            $crawler = $browser->request('POST', self::$url, ['UF' => $state]);

            $result = [];
            $result += $this->getDataFromPage($crawler, $spinner, $state);

            $hasNextPage = $this->hasNextPage($crawler);

            while($hasNextPage) {
                $form = $crawler->filter('form[name=Proxima]')->form();
                $crawler = $browser->submit($form);

                $result += $this->getDataFromPage($crawler, $spinner, $state);

                $hasNextPage = $this->hasNextPage($crawler);
            }

            $this->saveFile($result, $state, $outputOption);

            $spinner->finish();

            sleep(rand(2, 4));
        }

        return Command::SUCCESS;
    }

    /**
     * @param Crawler $crawler
     * @return bool
     */
    private function hasNextPage(Crawler $crawler)
    {
        return (bool) $crawler->filter('form[name=Proxima]')->count();
    }

    /**
     * @param Crawler $crawler
     * @param SpinnerProgress $spinner
     * @param string $state
     * @return array
     */
    private function getDataFromPage(Crawler $crawler, SpinnerProgress $spinner, $state)
    {
        $return = [];

        $table = $crawler->filter('table.tmptabela')->last();
        $table->filter('tr')->each(function (Crawler $row) use (&$return, $spinner, $state) {
            $cell = $row->filter('td');

            if ($cell->count()) {
                $locality = trim($cell->eq(0)->text());
                $track    = trim($cell->eq(1)->text());
                $type     = trim($cell->eq(3)->text());

                list($start, $end) = explode(' a ', $track);

                $identifier = md5($track);

                $return[$identifier] = [
                    'state'    => $state,
                    'locality' => $locality,
                    'start'    => str_replace('-', '', $start),
                    'end'      => str_replace('-', '', $end),
                    'type'     => $type,
                ];

                $spinner->advance();
            }
        });

        return $return;
    }

    /**
     * @param $data
     * @param $fileName
     * @param $outputOption
     */
    private function saveFile($data, $fileName, $outputOption)
    {
        if ('json' === strtolower($outputOption)) {
            $file = fopen(dirname(__DIR__) . "/../data/{$fileName}.json", 'w');
            fwrite($file, json_encode(array_values($data), JSON_UNESCAPED_UNICODE));
            fclose($file);
        }

        if ('csv' === strtolower($outputOption)) {
            $file = fopen(dirname(__DIR__) . "/../data/{$fileName}.csv", 'w');

            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // make UTF-8 from excel
            fwrite($file, implode(',', ['state', 'locality', 'start', 'end', 'type']) . PHP_EOL); // header

            foreach (array_values($data) as $row) {
                fwrite($file, implode(',', $row) . PHP_EOL);
            }

            fclose($file);
        }
    }
}
