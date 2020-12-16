<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use File;
use Storage;
use Illuminate\Support\Facades\Http;

class ListProjects extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list:projects';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all available projects';

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
      
      $this->line('Retrieving projects ...');
      $response = Http::withHeaders([
        'X-Api-Key' => $config['api_key']
      ])->get(config('clockify.api_base_url') . "/workspaces/{$config['workspace_id']}/projects");
      
      if ($response->failed()) {
        $this->line('Failed to connect to server.');
        exit;
      }
      
      $this->line('Retrieving projects ... DONE');
      
      $this->line('');
      $this->line(str_pad('projectId', 24, ' ', STR_PAD_RIGHT) . ' | ' . 'name');
      $this->line(str_pad('', 75, '-', STR_PAD_RIGHT));
      foreach ($response->json() as $project) {
        $this->line(str_pad($project['id'], 24, ' ', STR_PAD_RIGHT) . ' | ' . $project['name']);
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
