<?php

namespace Correios\Scraper\Util;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SpinnerProgress
{
    private const DOTS = ['◐', '◓', '◑', '◒'];

    /** @var ProgressBar */
    private $progressBar;

    /** @var int */
    private $step;

    public function __construct(OutputInterface $output, int $max = 0)
    {
        $this->progressBar = new ProgressBar($output->section(), $max);
        $this->progressBar->setBarCharacter('<fg=green>✔</>');
        $this->progressBar->setFormat('<fg=yellow>%bar%</>  %message%');
        $this->progressBar->setBarWidth(1);
        $this->progressBar->setRedrawFrequency(31);

        $this->step = 0;
    }

    public function advance(int $step = 1)
    {
        $this->step += $step;
        $this->progressBar->setProgressCharacter(self::DOTS[$this->step % count(self::DOTS)]);
        $this->progressBar->advance($step);
    }

    public function setMessage(string $message)
    {
        $this->progressBar->setMessage($message, 'message');
    }

    public function finish()
    {
        $this->progressBar->finish();
    }
}
