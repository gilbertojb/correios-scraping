<?php

namespace Correios\Scraper\Command;

use Correios\Scraper\Util\SpinnerProgress;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class PostcodeTracksCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'tracks:extract';

    /** @var string */
    protected static $url = 'http://www.buscacep.correios.com.br/sistemas/buscacep/ResultadoBuscaFaixaCEP.cfm';

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
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statesOption = $input->getOption('states');

        $browser = new HttpBrowser(HttpClient::create());

        foreach ($statesOption as $state) {
            $spinner = new SpinnerProgress($output);
            $spinner->setMessage("Extracting data from {$state}");

            $spinner->start();

            $crawler = $browser->request('POST', self::$url, ['UF' => $state]);

            $result = [];
            $result += $this->getDataFromPage($crawler, $spinner);

            $hasNextPage = $this->hasNextPage($crawler);

            while($hasNextPage) {
                $form = $crawler->filter('form[name=Proxima]')->form();
                $crawler = $browser->submit($form);

                $result += $this->getDataFromPage($crawler, $spinner);

                $hasNextPage = $this->hasNextPage($crawler);
            }

            $file = fopen(dirname(__DIR__) . "/../data/{$state}.json", 'w');
            fwrite($file, json_encode(array_values($result), JSON_UNESCAPED_UNICODE));
            fclose($file);

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
     * @return array
     */
    private function getDataFromPage(Crawler $crawler, SpinnerProgress $spinner)
    {
        $return = [];

        $table = $crawler->filter('table.tmptabela')->last();
        $table->filter('tr')->each(function (Crawler $row) use (&$return, $spinner) {
            $cell = $row->filter('td');

            if ($cell->count()) {
                $place = trim($cell->eq(0)->text());
                $track = trim($cell->eq(1)->text());

                list($start, $finish) = explode(' a ', $track);

                $identifier = md5($track);

                $return[$identifier] = [
                    'place'  => $place,
                    'start'  => str_replace('-', '', $start),
                    'finish' => str_replace('-', '', $finish),
                ];

                $spinner->advance();
            }
        });

        return $return;
    }
}
