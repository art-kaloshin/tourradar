<?php


namespace App\Classes;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TourImport
{
    const PARAMS_TYPES = [
        'ARRAY' => 'array',
        'INT' => 'integer',
        'FLOAT' => 'numeric',
        'STRING' => 'string',
        'URL' => 'url',
    ];

    const FIELDS = [
        'tour.id' => [
            'id' => 'id',
            'type' => self::PARAMS_TYPES['INT'],
            'required' => 'Y'
        ],
        'tour.name' => [
            'id' => 'name',
            'type' => self::PARAMS_TYPES['STRING'],
            'required' => 'Y'
        ],
        'tour.description' => [
            'id' => 'description',
            'type' => self::PARAMS_TYPES['STRING'],
            'required' => 'Y'
        ],
        'tour.price' => [
            'id' => 'price',
            'type' => self::PARAMS_TYPES['FLOAT'],
            'required' => 'Y'
        ],
        'tour.assets.images' => [
            'id' => 'images',
            'type' => self::PARAMS_TYPES['ARRAY'],
            'subType' => self::PARAMS_TYPES['URL']
        ],
        'tour.assets.pdf' => [
            'id' => 'pdf',
            'type' => self::PARAMS_TYPES['ARRAY'],
            'subType' => self::PARAMS_TYPES['URL']
        ],
    ];

    /**
     * Import name
     */
    const IMPORT_ID = 'Operator_import_1';

    /**
     * @var string url of tours json list
     */
    private string $jsonUrl = '';

    /**
     * @var string access token
     */
    private string $token = '';


    /**
     * @var string base url for assets
     */
    private string $assetUrl = '';

    public function __constructor()
    {
        $this->token = env('TOKEN');
        $this->assetUrl = env('ASSET_URL');
        $this->jsonUrl = env('JSON_URL');

        if (empty($this->token)) {
            throw new RuntimeException(self::IMPORT_ID . ' Empty token!');
        }
        if (empty($this->assetUrl)) {
            throw new RuntimeException(self::IMPORT_ID . ' Empty assetUrl!');
        }
        if (empty($this->jsonUrl)) {
            throw new RuntimeException(self::IMPORT_ID . ' Empty jsonUrl!');
        }
    }

    /**
     * Get parsed tour list from operator
     *
     * @return array
     */
    public function getTourList(): array
    {
        $tourList = $this->getTourJson();
        if (empty($tourList)) {
            throw new RuntimeException(self::IMPORT_ID . ' Empty tour list!');
        }

        return $this->parseTourList($tourList);
    }

    /**
     * Parse tour list data
     * public made for the external tests
     *
     * @param  array  $tourList
     * @return array
     */
    public function parseTourList(array $tourList): array
    {
        $result = [];
        foreach ($tourList as $tour) {
            if ($this->validateTour($tour)) {
                $result[] = $this->parseTour($tour);
            } else {
                Log::warning(self::IMPORT_ID . ' found tour data error!', ['data' => $tour]);
            }
        }

        return $result;
    }

    /**
     * Load json tours list
     *
     * @return array
     */
    private function getTourJson(): array
    {
        $result = Http::withToken($this->token)->get($this->jsonUrl);
        if ($result->failed()) {
            throw new RuntimeException(self::IMPORT_ID . ' failed to get tours data!');
        }

        return $result->json();
    }

    /**
     * Convert tour to internal format
     *
     * @param  array  $tour
     * @return array
     */
    private function parseTour(array $tour): array
    {
        $resultTour = [
            'operatorId' => self::IMPORT_ID
        ];
        foreach (self::FIELDS as $field => $params) {
            $value = data_get($tour, $field);

            if ($params['type'] == self::PARAMS_TYPES['ARRAY']) {
                switch ($params['subType']) {
                    case self::PARAMS_TYPES['URL']:
                        $value = $this->processUrlList(Arr::wrap($value));
                        break;
                    default:
                        $value = Arr::wrap($value);
                }
            }

            $resultTour[$params['id']] = $value;
        }

        return $resultTour;
    }

    /**
     * Add every url prefix
     *
     * @param  array  $urlList
     * @return array
     */
    private function processUrlList(array $urlList): array
    {
        $result = [];
        foreach ($urlList as $item) {
            $result[] = Str::contains($item, $this->assetUrl) ? $item : $this->assetUrl . $item;
        }
        return $result;
    }

    /**
     * Validate tour values
     *
     * @param $tour
     * @return bool
     */
    private function validateTour($tour): bool
    {
        $rules = [];
        foreach (self::FIELDS as $field => $params) {
            $rules[$field] = (!empty($params['required']) ? 'required|' : '') . $params['type'];
        }
        return !validator($tour, $rules)->fails();
    }

}
