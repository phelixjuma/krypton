<?php
/**
 * This is script handles random strings
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Classes;

/**
 * Class for handling random string generation
 * @package Kuza\Krypton
 */
class Randomstring {

    /** @var string $alphabet The alphabet from which to pick the random numbers */
    protected $alphabet;

    /** @var int $alphabetLength The length of the random string to generate  */
    protected $alphabetLength;

    /**
     * @param string $uniqueString The generated string
     */
    public $uniqueString;

    /**
     * Class construct
     * Here, we set the alphabet and generate the unique keys.
     * @param int $length
     * @param string $alphabet
     */
    public function __construct($length="", $alphabet = "") {
        //set alphabet
        if ($alphabet !== "") {
            $this->setAlphabet($alphabet);
        } else {
            $this->setAlphabet(
                    implode(range('a', 'z'))
                    . implode(range('A', 'Z'))
                    . implode(range(0, 9))
            );
        }
        $length = empty($length) ? 10 : $length;

        //generate unique string
        $this->generateUniqueKeys($length);
    }

    /**
     * Sets the alphabet i.e the characters to use
     * @param string $alphabet
     */
    public function setAlphabet($alphabet) {
        $this->alphabet = $alphabet;
        $this->alphabetLength = strlen($alphabet);
    }

    /**
     * The function to generate the keys
     * @param int $length
     * @return string
     */
    public function generateUniqueKeys($length) {
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomKey = $this->getRandomInteger(0, $this->alphabetLength);
            $token .= $this->alphabet[$randomKey];
        }
        $this->uniqueString = $token;
    }

    /**
     * Function to get a random integer
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function getRandomInteger($min, $max) {
        $range = ($max - $min);

        if ($range < 0) {
            // Not so random...
            return $min;
        }

        $log = log($range, 2);

        // Length in bytes.
        $bytes = (int) ($log / 8) + 1;

        // Length in bits.
        $bits = (int) $log + 1;

        // Set all lower bits to 1.
        $filter = (int) (1 << $bits) - 1;

        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));

            // Discard irrelevant bits.
            $rnd = $rnd & $filter;
        } while ($rnd >= $range);

        return ($min + $rnd);
    }

}
