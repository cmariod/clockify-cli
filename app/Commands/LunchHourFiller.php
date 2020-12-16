<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use File;
use Illuminate\Support\Facades\Http;

class LunchHourFiller extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'time:insertlunch {startdate} {enddate}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Helps to fill in missing lunch hour';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      if (!File::exists(config('clockify.config_path')) || empty($config = json_decode(File::get(config('clockify.config_path')), true))) {
        $this->line('Invalid Configuration File. Please run `php clockify-cli configure`.');
        exit;
      }
      
      $params = [
        'page-size' => 100,
        'start' => '2020-11-01T00:00:00Z',
        'end' => '2020-11-07T00:00:00Z',
      ];
      $response = Http::withHeaders([
        'X-Api-Key' => $config['api_key']
      ])->get(config('clockify.api_base_url') . "/workspaces/{$config['workspace_id']}/user/{$config['user_id']}/time-entries?" . http_build_query($params));
      
      if ($response->failed()) {
        $this->line('Failed to connect to server.');
        exit;
      }
      
      $time_entries = json_decode($response->body(), true);
      $timezone = new \DateTimeZone(date_default_timezone_get());
      foreach($time_entries as $time_entry) {
        $start_local = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $time_entry['timeInterval']['start'])->setTimeZone($timezone)->format('Y-m-d H:i:s');
        $end_local = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $time_entry['timeInterval']['end'])->setTimeZone($timezone)->format('Y-m-d H:i:s');
        $this->line($time_entry['description'] . '|' . $time_entry['projectId'] . '(' . $start_local . '-' . $end_local . ')');
      }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
      // $schedule->command(static::class)->everyMinute();
    }
}
