<?php

namespace Correios\Scraper\Util;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SpinnerProgress
{
    private const CHARS = ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'];

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
        $this->progressBar->setRedrawFrequency(100);
        $this->progressBar->maxSecondsBetweenRedraws(0.1);
        $this->progressBar->minSecondsBetweenRedraws(0.05);

        $this->step = 0;
    }

    public function start()
    {
        $this->progressBar->start();
    }

    public function advance(int $step = 1)
    {
        $this->step += $step;
        $this->progressBar->setProgressCharacter(self::CHARS[$this->step % count(self::CHARS)]);
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
