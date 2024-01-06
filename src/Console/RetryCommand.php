<?php

namespace VantukhKolya\RabbitMqClient\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Arr;

class RetryCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:retry
                            {id?* : The ID of the failed job or "all" to retry all jobs}
                            {--queue= : Retry all of the failed jobs for the specified queue}
                            {--range=* : Range of job IDs (numeric) to be retried}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry a failed queue job';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->getJobIds() as $id) {
            $job = $this->laravel['queue.failer']->find($id);

            if (is_null($job)) {
                $this->error("Unable to find failed job with ID [{$id}].");
            } else {
                $this->laravel['events']->dispatch(new JobRetryRequested($job));

                $this->retryJob($job);

                $this->info("The failed job [{$id}] has been pushed back onto the queue!");

                $this->laravel['queue.failer']->forget($id);
            }
        }
    }

    /**
     * Get the job IDs to be retried.
     *
     * @return array
     */
    protected function getJobIds()
    {
        $ids = (array)$this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            return Arr::pluck($this->laravel['queue.failer']->all(), 'id');
        }

        if ($queue = $this->option('queue')) {
            return $this->getJobIdsByQueue($queue);
        }

        if ($ranges = (array)$this->option('range')) {
            $ids = array_merge($ids, $this->getJobIdsByRanges($ranges));
        }

        return array_values(array_filter(array_unique($ids)));
    }

    /**
     * Get the job IDs by queue, if applicable.
     *
     * @param string $queue
     * @return array
     */
    protected function getJobIdsByQueue($queue)
    {
        $ids = collect($this->laravel['queue.failer']->all())
            ->where('queue', $queue)
            ->pluck('id')
            ->toArray();

        if (count($ids) === 0) {
            $this->error("Unable to find failed jobs for queue [{$queue}].");
        }

        return $ids;
    }

    /**
     * Get the job IDs ranges, if applicable.
     *
     * @param array $ranges
     * @return array
     */
    protected function getJobIdsByRanges(array $ranges)
    {
        $ids = [];

        foreach ($ranges as $range) {
            if (preg_match('/^[0-9]+\-[0-9]+$/', $range)) {
                $ids = array_merge($ids, range(...explode('-', $range)));
            }
        }

        return $ids;
    }

    /**
     * Retry the queue job.
     *
     * @param \stdClass $job
     * @return void
     */
    protected function retryJob($job)
    {
        $this->laravel['queue']->connection($job->connection)->pushRaw(
            $this->resetAttempts($job->payload),
            $job->queue,
            $this->getExchangeData($job->payload)
        );
    }

    protected function getExchangeData(string $payload): array
    {
        $payload = json_decode($payload, true);
        if (!empty($payload['exchange']) && !empty($payload['exchange_type']) && !empty($payload['exchange_routing_key'])) {
            return [
                'exchange' => $payload['exchange'],
                'exchange_type' => $payload['exchange_type'],
                'exchange_routing_key' => $payload['exchange_routing_key'],
            ];
        }
        return [];
    }

    /**
     * Reset the payload attempts.
     *
     * Applicable to Redis and other jobs which store attempts in their payload.
     *
     * @param string $payload
     * @return string
     */
    protected function resetAttempts($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
    }
}
