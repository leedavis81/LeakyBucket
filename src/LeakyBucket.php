<?php
/**
 * A leaky bucket is used for rate limiting / throttle for a rolling period (rather than a fixed block)
 * Bucket continually empties at a set rate, if the bucket is full then rates have been exceeded.
 * This is useful to ensure API / Network traffic requesting limiting
 * @author Lee
 */
class LeakyBucket
{
    /**
     * The maximim capacity of the bucket
     * @var integer $capacity
     */
    protected $capacity;

    /**
     * The current volume of the of the bucket (starts empty)
     * @var integer $volume
     */
    protected $volume = 0;

    /**
     * The rate at which the bucket empties per second
     * @var float $emptyRate
     */
    protected $empty_rate;

    /**
     * The time the last update was applied to the bucket
     * @var DateTime $last_update
     */
    protected $last_update;

    /**
     * Create a leaky bucket object by defining the number of requests acceptable in a timeframe (seconds)
     * @param integer $capacity - number of request allowed in the bucket before its full
     * @param integer $timeframe - the time it would take a full bucket to empty (seconds)
     * @return System_LeakyBucket $leakyBucket
     */
    public function __construct($capacity, $timeframe)
    {
        $this->capacity = (int) $capacity;
        $this->empty_rate = ($capacity / (int) $timeframe);

        $this->last_update = new \DateTime('now');
    }

    /**
     * Load up the buckets current state (this would be collected from storage)
     * @param integer $requests - set the previous requests done within $timeframe
     * @param \DateTime $last_request - set the time of the last request
     * @return System_LeakyBucket $leakyBucket
     */
    public function load($requests, DateTime $last_update)
    {
        $this->fill((int) $requests);
        $this->last_update = $last_update;
        return $this;
    }

    /**
     * Fill the bucket with a number of requests
     * @param intefer $requests
     */
    public function fill($requests = 1)
    {
        $requests = (int) $requests;
        if ($requests > 0)
        {
            $this->volume += $requests;
        }
    }

    /**
     * Update the current bucket volume and check we have capacity
     * @param boolean $block - If set to true the function call blocks until the bucket has capacity
     * @return boolean $response
     */
    public function hasCapacity($block = false)
    {
        $this->updateBucket();
        if ($block)
        {
            while (!$this->hasCapacity()){}
        }
        return (floor($this->volume) < $this->capacity);
    }

    /**
     * Get the current volume of the bucket
     * @return integer $volume
     */
    public function getVolume()
    {
        $this->updateBucket();
        return $this->volume;
    }

    /**
     * Update the bucket volume using the difference time of last modification and now
     */
    public function updateBucket()
    {
        // get the difference in seconds
        $now = new DateTime('now');
        $diff = $this->last_update->diff($now)->format('%s');

        if ($diff > 0)
        {
            // reduce the bucket volume by seconds * empty_rate
            $this->volume -= ($diff * $this->empty_rate);
            echo "Decreasing by " . ($diff * $this->empty_rate) . PHP_EOL;
            $this->last_update = $now;
        }
    }

    /**
     * Static call to create an instance of a leaky bucket
     * @param integer $capacity - number of request allowed in the bucket before its full
     * @param integer $timeframe - the time it would take a full bucket to empty (seconds)
     * @return \System_LeakyBucket $leakyBucket
     */
    public static function create($capacity, $timeframe)
    {
        return new self($capacity, $timeframe);
    }
}