<?php

namespace Rubix\ML\AnomalyDetectors;

use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use Rubix\ML\Datasets\Dataset;
use Rubix\ML\Datasets\DataFrame;
use Rubix\ML\Other\Helpers\Stats;
use InvalidArgumentException;
use RuntimeException;

/**
 * Robust Z Score
 *
 * A quick global anomaly Detector, Robust Z Score uses a threshold to detect
 * outliers within a Dataset. The modified Z score consists of taking the median
 * and median absolute deviation (MAD) instead of the mean and standard
 * deviation thus making the statistic more robust to training sets that may
 * already contain outliers.
 *
 * References:
 * [1] P. J. Rousseeuw et al. (2017). Anomaly Detection by Robust Statistics.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class RobustZScore implements Learner, Persistable
{
    const LAMBDA = 0.6745;

    /**
     * The average z score to tolerate before a sample is considered an outlier.
     *
     * @var float
     */
    protected $tolerance;

    /**
     * The threshold z score of a individual feature to consider the entire
     * sample an outlier.
     *
     * @var float
     */
    protected $threshold;

    /**
     * The median of each feature column in the training set.
     *
     * @var array|null
     */
    protected $medians;

    /**
     * The median absolute deviation of each feature column.
     *
     * @var array|null
     */
    protected $mads;

    /**
     * @param  float  $tolerance
     * @param  float  $threshold
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $tolerance = 3.0, float $threshold = 3.5)
    {
        if ($tolerance < 0.) {
            throw new InvalidArgumentException("Score tolerance must be"
                . " 0 or greater, $tolerance given.");
        }

        if ($threshold < 0.) {
            throw new InvalidArgumentException("Score threshold must be"
                . " 0 or greater, $threshold given.");
        }

        $this->tolerance = $tolerance;
        $this->threshold = $threshold;
    }

    /**
     * Return the integer encoded type of estimator this is.
     *
     * @return int
     */
    public function type() : int
    {
        return self::DETECTOR;
    }

    /**
     * Return the array of computed feature column medians.
     *
     * @return array|null
     */
    public function medians() : ?array
    {
        return $this->medians;
    }

    /**
     * Return the array of computed feature column median absolute deviations.
     *
     * @return array|null
     */
    public function mads() : ?array
    {
        return $this->mads;
    }

    /**
     * Compute the median and median absolute deviations of each feature in
     * the training set.
     *
     * @param  \Rubix\ML\Datasets\Dataset  $dataset
     * @throws \InvalidArgumentException
     * @return void
     */
    public function train(Dataset $dataset) : void
    {
        if (in_array(DataFrame::CATEGORICAL, $dataset->types())) {
            throw new InvalidArgumentException('This estimator only works with'
                . ' continuous features.');
        }

        $this->medians = $this->mads = [];

        foreach ($dataset->rotate() as $column => $values) {
            list($median, $mad) = Stats::medMad($values);

            $this->medians[$column] = $median;
            $this->mads[$column] = $mad;
        }
    }

    /**
     * Compute the per feature z score and compare the average and max values
     * to a tolerance and threshold respectively.
     *
     * @param  \Rubix\ML\Datasets\Dataset  $dataset
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return array
     */
    public function predict(Dataset $dataset) : array
    {
        if (in_array(DataFrame::CATEGORICAL, $dataset->types())) {
            throw new InvalidArgumentException('This estimator only works with'
                . ' continuous features.');
        }

        if (empty($this->medians) or empty($this->mads)) {
            throw new RuntimeException('Estimator has not been trained.');
        }

        $n = $dataset->numColumns();

        $predictions = [];

        foreach ($dataset as $sample) {
            $score = 0.;

            foreach ($sample as $column => $feature) {
                $median = $this->medians[$column];
                $mad = $this->mads[$column];

                $z = (self::LAMBDA * ($feature - $median)) / $mad;

                if ($z > $this->threshold) {
                    $predictions[] = 1;

                    continue 2;
                }

                $score += $z;
            }

            $score /= $n;

            $predictions[] = $score > $this->tolerance ? 1 : 0;
        }

        return $predictions;
    }
}
