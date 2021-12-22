<?php

namespace App\Console\Commands;

use App\Classes\TourImport;
use App\Jobs\TourQueue;
use Illuminate\Console\Command;

class TestParser extends Command
{
    const DATA = '
        [
            {
                "tour": {
                    "id": 1,
                    "name": "Paris",
                    "description": "Summer Paris is amazing!",
                    "price": 0
                },
                "assets": {
                    "images": [
                        "img.jpg",
                        "img2.jpg",
                        "img3.jpg"
                    ],
                    "pdf": [
                        "file1.pdf",
                        "file2.pdf",
                        "file3.pdf"
                    ]
                }
            },
            {
                "tour": {
                    "id": 2,
                    "name": "Paris",
                    "description": "Summer Paris is amazing!",
                    "price": 0
                },
                "assets": {
                    "images": [
                        "img.jpg",
                        "img2.jpg",
                        "img3.jpg"
                    ],
                    "pdf": [
                        "file1.pdf",
                        "file2.pdf",
                        "file3.pdf"
                    ]
                }
            }
        ]
    ';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:parser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test tour parser';

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
        $parser = new TourImport();
        $tourList = $parser->parseTourList(json_decode(self::DATA, true));

        foreach ($tourList as $tour) {
            dispatch(new TourQueue($tour['operatorId'] . '::' . $tour['id'], $tour));
        }

        return 0;
    }
}
