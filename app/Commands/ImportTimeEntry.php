<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use File;
use Storage;
use Illuminate\Support\Facades\Http;

class ImportTimeEntry extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'time:import {--ratelimiter=10} {--F|file=import.csv}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Import data from csv';

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
      
      if (
        !File::exists(Storage::path($this->option('file'))) || 
        empty($raw_time_entries = array_map('str_getcsv', file(Storage::path($this->option('file')))))
      ) {
        $this->line('Invalid import file location.');
        exit;
      }
      
      $time_entries = [];
      foreach($raw_time_entries as $raw_time_entry) {
        $time_entry = array_combine($raw_time_entries[0], $raw_time_entry);
        if ($time_entry['lunchhour'] == 'lunchhour') continue;
        
        if (empty($time_entry['lunchhour'])) {
          $time_entries[] = $time_entry;
        } else {
          $prelunch = $time_entry;
          $postlunch = $time_entry;
          $prelunch['end'] = $time_entry['lunchhour'];
          $postlunch['start'] = \DateTime::createFromFormat('Y-m-d H:i:s', $time_entry['lunchhour'])->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');
          $time_entries[] = $prelunch;
          $time_entries[] = $postlunch;
        }
      }
      
      $timezone = new \DateTimeZone(date_default_timezone_get());
      $utc = new \DateTimeZone('UTC');
      foreach($time_entries as $time_entry) {
        $start_utc = \DateTime::createFromFormat('Y-m-d H:i:s', $time_entry['start'], $timezone)->setTimeZone($utc)->format('Y-m-d\TH:i:s\Z');
        $end_utc = \DateTime::createFromFormat('Y-m-d H:i:s', $time_entry['end'], $timezone)->setTimeZone($utc)->format('Y-m-d\TH:i:s\Z');

        $post_data = [
          'description' => $time_entry['description'],
          'projectId' => $time_entry['projectId'],
          'start' => $start_utc,
          'end' => $end_utc,
        ];
        $response = Http::withHeaders([
          'X-Api-Key' => $config['api_key']
        ])->post(config('clockify.api_base_url') . "/workspaces/{$config['workspace_id']}/user/{$config['user_id']}/time-entries", $post_data);
      
        if ($response->failed()) {
          $this->line('Failed to connect to server.');
          $this->line($response->body());
          exit;
        }
        
        usleep(1000000 / $this->option('ratelimiter')); // ratelimiter request per second
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
