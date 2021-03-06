<?php namespace SS\Queue;

use Pheanstalk_Job;
use Pheanstalk_Pheanstalk as Pheanstalk;

class PheanstalkQueue implements QueueInterface {

    /**
     * The Pheanstalk instance.
     *
     * @var Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The name of the default tube.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new Beanstalkd queue instance.
     *
     * @param  Pheanstalk  $pheanstalk
     * @param  string  $default
     * @return void
     */
    public function __construct(Pheanstalk $pheanstalk, $default)
    {
        $this->default = $default;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return void
     */
    public function push($job, $data = '', $queue = null)
    {
        $payload = $this->parseJobData($job, $data);

        $this->pheanstalk->useTube($this->getQueue($queue))->put($payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  int     $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = $this->parseJobData($job, $data);

        $pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

        $pheanstalk->put($payload, Pheanstalk::DEFAULT_PRIORITY, $delay);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return Pheanstalk_Job|null
     */
    public function pop($queue = null)
    {
        $job = $this->pheanstalk->watchOnly($this->getQueue($queue))->reserve(0);
        return $job;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying Pheanstalk instance.
     *
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }

    /**
     * Enconde job data.
     *
     * @return string JSON
     */
    public function parseJobData($job, $data){
        $task = array(
            'job' => $job,
            'data' => $data,
        );

        return json_encode($task);
    }

    public function deleteJob($job)
    {
        if (!$job instanceof Pheanstalk_Job) {
            throw new InvalidArgumentException("This isn't a valid Job");
        }

        $this->pheanstalk->delete($job);
    }

}
