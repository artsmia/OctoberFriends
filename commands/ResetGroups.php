<?php
namespace DMA\Friends\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use DMA\Friends\Models\UserGroup;
use DMA\Friends\Models\Settings;
use Carbon\Carbon;

/**
 * Set a cron task that will reset groups 
 *
 * @package DMA\Friends\Commands
 * @author Kristen Arnold, Carlos Arroyo
 */
class ResetGroups extends ScheduledCommand
{
    /**
     * @var string The console command name.
     */
    protected $name = 'friends:reset-groups';

    /**
     * When a command should run
     * @param Scheduler $scheduler
     * @return \Indatus\Dispatcher\Scheduling\Schedulable
     */
    public function schedule(Schedulable $scheduler)
    {
        // Run every 5 minutes
        return $scheduler->everyMinutes(5);
    }

    /**
     * Read and process incomming data from listenable channels
     * @return void
     */
    public function fire()
    {
        
        $today = Carbon::today();
        
        $lastRun = Settings::get('reset_group_last_run');
        if ($lastRun != $today->toDateTimeString()) {
            
            $day = strtolower($today->format('l'));
            $reset_groups_every_day = Settings::get('reset_groups_every_day');
            $reset_groups_time = Settings::get('reset_groups_time');
            
            if (in_array($day, $reset_groups_every_day)) {
                $reset_at = Carbon::parse($reset_groups_time);

                if ( $reset_at->lte(Carbon::now()) ) {
                    
                    UserGroup::markInactiveGroups();
                    Settings::set('reset_group_last_run', $today->toDateTimeString());

                }
                
            }
            
            
        }else{
           // \Log::info('Has already run');
        }

    }
}