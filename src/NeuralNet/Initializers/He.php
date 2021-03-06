<?php

namespace Rubix\ML\NeuralNet\Initializers;

use Rubix\Tensor\Matrix;

/**
 * He
 *
 * The He initializer was designed for hidden layers that feed into rectified
 * linear layers such ReLU, Leaky ReLU, ELU, and SELU. It draws from a uniform
 * distribution with limits defined as +/- (6 / (fanIn + fanOut)) **
 * (1. / sqrt(2)).
 *
 * References:
 * [1] K. He et al. (2015). Delving Deep into Rectifiers: Surpassing Human-Level
 * Performance on ImageNet Classification.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class He implements Initializer
{
    const ETA = 0.70710678118;
    
    /**
     * Initialize a weight matrix W in the dimensions fan in x fan out.
     *
     * @param  int  $fanIn
     * @param  int  $fanOut
     * @return \Rubix\Tensor\Matrix
     */
    public function init(int $fanIn, int $fanOut) : Matrix
    {
        $scale = (6. / ($fanIn + $fanOut)) ** self::ETA;

        return Matrix::uniform($fanOut, $fanIn)
            ->multiply($scale);
    }
}
