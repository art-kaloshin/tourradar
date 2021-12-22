<?php

namespace App\Console\Commands;

use App\Classes\TourImport;
use App\Jobs\TourQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class TourImporter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tour:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tout from operator';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $importer = new TourImport();
            $tourList = $importer->getTourList();
            foreach ($tourList as $tour) {
                dispatch(new TourQueue($tour['operatorId'] . '::' . $tour['id'], $tour));
            }
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
        }

        return 0;
    }
}
