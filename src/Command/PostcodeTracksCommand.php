<?php

namespace Correios\Scraper\Command;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class PostcodeTracksCommand extends Command
{
    protected static $defaultName = 'tracks:extract';

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statesOption = $input->getOption('states');
        
        foreach ($statesOption as $state) {
            $file = fopen(dirname(__DIR__) . "/../data/{$state}.json", 'w');

            $browser = new HttpBrowser(HttpClient::create());
            $crawler = $browser->request('POST', self::$url, ['UF' => $state]);

            $result = [];
            $result += $this->getDataFromPage($crawler);

            $hasNextPage = $this->hasNextPage($crawler);

            while($hasNextPage) {
                $form = $crawler->filter('form[name=Proxima]')->form();
                $crawler = $browser->submit($form);

                $result += $this->getDataFromPage($crawler);

                $hasNextPage = $this->hasNextPage($crawler);
            }

            fwrite($file, json_encode(array_values($result), JSON_UNESCAPED_UNICODE));
            fclose($file);
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
     * @return array
     */
    private function getDataFromPage(Crawler $crawler)
    {
        $return = [];

        $table = $crawler->filter('table.tmptabela')->last();
        $table->filter('tr')->each(function (Crawler $row) use (&$return) {
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
            }
        });

        return $return;
    }
}
