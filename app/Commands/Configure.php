<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use File;
use Illuminate\Support\Facades\Http;

class Configure extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'configure {--p|print}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set basic configuration parameters';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $config_path = config('clockify.config_path');
      $file_exists = File::exists($config_path);
      if ($file_exists) {
        $config = json_decode(File::get($config_path), true);
        $this->line('API Key: ' . $config['api_key']);
        $this->line('User ID: ' . $config['user_id']);
        $this->line('Workspace ID: ' . $config['workspace_id']);
        
        if ($this->option('print')) exit;
        
        if (!$this->confirm('Do you wish to enter new values?')) {
          exit;
        }
      } else {
        $this->line('No configuration found');
        
        if ($this->option('print')) exit;
      }
      
      $api_key = $this->ask('Enter your API Key?');
      
      $user_id = 0;
      
      $this->line('Retrieving user ...');
      $response = Http::withHeaders([
        'X-Api-Key' => $api_key
      ])->get(config('clockify.api_base_url') . "/user");
      
      if ($response->failed()) {
        $this->line('Failed to connect to server.');
        exit;
      }
      
      $user_id = $response['id'];
      $this->line('Retrieving user ... DONE');
      
      $this->line('Retrieving workspace ...');
      $response = Http::withHeaders([
        'X-Api-Key' => $api_key
      ])->get(config('clockify.api_base_url') . "/workspaces");
      
      if ($response->failed()) {
        $this->line('Failed to connect to server.');
        exit;
      }
      
      $workspace_json = $response->json();
      if (empty($workspace_json)) {
        $this->line('No workspace found.');
        exit;
      }
      
      $this->line('Retrieving workspace ... DONE');
      
      $this->line('');
      $this->line('Workspaces:');
      $choices = [];
      foreach($workspace_json as $k=>$v) {
        $this->line(str_pad($k+1, 3, ' ', STR_PAD_LEFT) . ' : ' . $v['name'] . ' [' . $v['id'] . ']');
        $choices[] = $k+1;
      }
      
      do {
        $workspace_choice = $this->anticipate('Choose your workspace number', $choices);
      } while (!($workspace_choice > 0 && $workspace_choice <= count($workspace_json)));
      $workspace_id = $workspace_json[$workspace_choice-1]['id'];
      
      $this->task("Writing into config file", function () use ($config_path, $api_key, $workspace_id, $user_id) {
        return File::put($config_path, json_encode(['api_key' => $api_key, 'workspace_id' => $workspace_id, 'user_id' => $user_id]));
      });
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
