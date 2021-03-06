<?php


namespace Akeneo\Pim\Enrichment\Bundle\Command;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Symfony\Component\Console\Helper\ProgressBar;

class BackoffElasticSearchStateHandler
{
    public const RETRY_COUNTER = 10;
    public const INITIAL_WAIT_DELAY = 10;
    public const BACKOFF_LOGARITHMIC_INCREMENT = 2;

    public function doIndex(iterable $chunkedCodes, ProgressBar $progressBar, \Closure $codesEsHandler): int
    {
        $indexedCount = 0;

        $progressBar->start();
        foreach ($chunkedCodes as $productModelCodes) {
            $treatedBachSize= sizeof($productModelCodes);
            $batchSize = sizeof($productModelCodes);
            $backOverheat = false;
            $retryCounter = self::RETRY_COUNTER;
            $waitDelay = self::INITIAL_WAIT_DELAY;
            $batchEsCodes = $productModelCodes;
            do {
                if ($backOverheat) {
                    echo("Sleeping before retry due to back pressure {$waitDelay} seconds, with batch size of {$batchSize} \n");
                    sleep($waitDelay);
                    $batchEsCodes = array_slice($productModelCodes, 0, $batchSize);
                }
                try {
                    $codesEsHandler($batchEsCodes);
                    array_splice($productModelCodes, 0, $batchSize);
                    $backOverheat = false;
                    $retryCounter = self::RETRY_COUNTER;
                    $waitDelay = self::INITIAL_WAIT_DELAY;
                    $indexedCount += count($batchEsCodes);
                } catch (BadRequest400Exception $e) {
                    if ($e->getCode() == 429) {
                        $backOverheat = true;
                        $waitDelay = $waitDelay + self::INITIAL_WAIT_DELAY; //Heuristic: linear increment
                        $retryCounter--;
                        $batchSize = intdiv($batchSize, self::BACKOFF_LOGARITHMIC_INCREMENT); //Heuristic: logarithmics decrement
                    }
                }
            } while (($backOverheat && $retryCounter) || sizeof($productModelCodes));

            if ($backOverheat && isset($e)) {
                throw $e;
            }

            $progressBar->advance($treatedBachSize);
        }
        $progressBar->finish();

        return $indexedCount;
    }
}
